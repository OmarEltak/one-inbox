<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\AiConfig;
use App\Models\ProductMedia;
use Illuminate\Support\Collection;

/**
 * Central handler for AI → product-media interactions:
 *
 *  1. Builds the "available media" block injected into the system prompt
 *     so the AI knows what it can attach to a reply.
 *
 *  2. Parses [[SEND_MEDIA:...]] markers out of the model's reply text,
 *     resolves them to ProductMedia rows, and returns the stripped text
 *     + resolved attachments for SendAiResponse to dispatch per platform.
 *
 *  3. Matches a customer's inbound image (described by vision) against
 *     the team's product catalog media, producing a short textual hint
 *     the AI can use to answer "is this your burgundy tee?"-style asks.
 *
 * MVP constraints (deliberate — will revisit when customer usage justifies):
 *   - Max 4 media per AI reply (hard cap after marker parsing).
 *   - Marker format: [[SEND_MEDIA:product_id=<uuid>]] or with ,variant=<label>.
 *     product_id must match a product in the ai_config.product_catalog JSON
 *     AND have at least one ProductMedia row that allows auto-send.
 *   - Variant matching is case-insensitive substring on ProductMedia.variant_label.
 */
class AiMediaResolver
{
    public const MAX_ATTACHMENTS_PER_REPLY = 8;
    public const MAX_PER_MARKER = 5;

    private const MARKER_PATTERN = '/\[\[\s*SEND_MEDIA\s*:\s*([^\]]+?)\s*\]\]/i';

    /**
     * Build the catalog snippet injected into the system prompt. Empty string
     * when the config has no product_media — the caller should skip the
     * section rather than append a hollow header.
     */
    public function catalogSnippet(AiConfig $config): string
    {
        $mediaByProduct = ProductMedia::where('ai_config_id', $config->id)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->groupBy('product_key');

        if ($mediaByProduct->isEmpty()) {
            return '';
        }

        $catalog = (array) ($config->product_catalog ?? []);
        $productsById = [];
        foreach ($catalog as $product) {
            $id = $product['id'] ?? null;
            if ($id) {
                $productsById[$id] = $product;
            }
        }

        $lines = [];
        foreach ($mediaByProduct as $productId => $items) {
            $product = $productsById[$productId] ?? null;
            if (! $product) {
                continue; // orphaned media for a deleted product — skip
            }

            $autoSendable = $items->filter(fn ($m) => (bool) $m->allow_ai_autosend);
            if ($autoSendable->isEmpty()) {
                continue; // nothing the AI is authorised to send
            }

            $variants = $autoSendable
                ->pluck('variant_label')
                ->filter(fn ($v) => $v !== null && $v !== '')
                ->values()
                ->all();

            $name = trim((string) ($product['name'] ?? ''));
            if ($name === '') {
                $name = 'Product ' . substr($productId, 0, 8);
            }

            $variantHint = empty($variants)
                ? 'primary image available'
                : 'variants available: ' . implode(', ', $variants);

            $lines[] = sprintf('- "%s" (product_id=%s) — %s', $name, $productId, $variantHint);
        }

        if (empty($lines)) {
            return '';
        }

        return "══ PRODUCT MEDIA YOU CAN ATTACH ══\n"
            . "When the customer asks to see a product, or when showing images would clearly help close the sale, add a marker on its own line in your reply:\n\n"
            . "  [[SEND_MEDIA:product_id=<id>]]                        ← sends up to " . self::MAX_PER_MARKER . " images for that product\n"
            . "  [[SEND_MEDIA:product_id=<id>,variant=<label>]]        ← sends ONE specific variant/view\n"
            . "  [[SEND_MEDIA:product_id=<id>,primary]]                ← sends ONLY the primary image\n\n"
            . "Rules:\n"
            . "  • When the customer asks 'صور' / 'can I see images' / 'show me' / 'كل الصور' / 'all images' — use the FIRST form.\n"
            . "  • When the customer asks about TWO OR MORE products at once (e.g. 'images of both courses' / 'صور الكورسين' / 'all products') — emit ONE marker per product on separate lines. Do NOT try to combine them.\n"
            . "  • When the customer asks for a specific color/view (e.g. 'the burgundy one') — use the VARIANT form.\n"
            . "  • Hard caps enforced by the system: " . self::MAX_PER_MARKER . " images per single marker, " . self::MAX_ATTACHMENTS_PER_REPLY . " images per reply total. Images beyond the limits are silently dropped.\n"
            . "  • The marker is stripped from the visible text — write your reply as usual, then add the marker(s) at the end.\n"
            . "  • If the product_id is not in the list below, do NOT invent one. Ask the customer for more detail instead.\n\n"
            . "Available:\n"
            . implode("\n", $lines);
    }

    /**
     * Extract [[SEND_MEDIA:...]] markers from AI reply text. Returns an array
     * shaped: ['text' => <stripped body>, 'attachments' => Collection<ProductMedia>].
     *
     * The returned attachment collection is already capped at MAX_ATTACHMENTS_PER_REPLY
     * and deduplicated by media id, in the order the AI emitted them.
     */
    public function extractMarkers(string $text, AiConfig $config): array
    {
        if (! preg_match_all(self::MARKER_PATTERN, $text, $matches, PREG_SET_ORDER)) {
            return ['text' => trim($text), 'attachments' => collect()];
        }

        $validProductIds = collect((array) ($config->product_catalog ?? []))
            ->pluck('id')
            ->filter()
            ->values()
            ->all();

        /** @var Collection<int, ProductMedia> $attachments */
        $attachments = collect();
        $seenMediaIds = [];

        foreach ($matches as $match) {
            [$fullMarker, $argString] = $match;

            $args = $this->parseMarkerArgs($argString);
            $productId = $args['product_id'] ?? null;
            if (! $productId || ! in_array($productId, $validProductIds, true)) {
                continue;
            }

            $resolved = $this->resolveMedia($config, $productId, $args);
            // Per-marker cap: one product can't eat the entire per-reply budget.
            // Without this, a customer asking for "both courses" lost all the
            // second product's images because marker #1 consumed the full cap.
            $takenFromThisMarker = 0;
            foreach ($resolved as $media) {
                if (in_array($media->id, $seenMediaIds, true)) {
                    continue;
                }
                $seenMediaIds[] = $media->id;
                $attachments->push($media);
                $takenFromThisMarker++;

                if ($attachments->count() >= self::MAX_ATTACHMENTS_PER_REPLY) {
                    break 2;
                }
                if ($takenFromThisMarker >= self::MAX_PER_MARKER) {
                    break;
                }
            }
        }

        // Strip ALL markers from the visible text, even ones we couldn't
        // resolve — the customer must never see raw [[SEND_MEDIA:...]] tokens.
        $stripped = preg_replace(self::MARKER_PATTERN, '', $text) ?? $text;

        // Collapse any blank runs left behind by stripped marker lines.
        $stripped = preg_replace("/\n{3,}/", "\n\n", (string) $stripped);

        return [
            'text'        => trim((string) $stripped),
            'attachments' => $attachments,
        ];
    }

    /**
     * Match a customer-sent image (described by the vision pipeline) against
     * the team's product media catalog, returning a short English hint like:
     *   "Possibly matches: 'Classic Tee' (burgundy variant)"
     * or an empty string if no plausible match.
     *
     * MVP: lightweight text overlap between vision description + product name
     * + variant labels. Good enough for clothing shops where the vision
     * caption mentions color/garment. A pixel-embedding version can replace
     * this later without changing the caller contract.
     */
    public function matchCustomerImageToProducts(string $visionDescription, AiConfig $config): string
    {
        $desc = mb_strtolower(trim($visionDescription));
        if ($desc === '') {
            return '';
        }

        $catalog = (array) ($config->product_catalog ?? []);
        if (empty($catalog)) {
            return '';
        }

        $allMedia = ProductMedia::where('ai_config_id', $config->id)->get()->groupBy('product_key');
        if ($allMedia->isEmpty()) {
            return '';
        }

        $bestScore = 0;
        $bestHint = '';

        foreach ($catalog as $product) {
            $productId = $product['id'] ?? null;
            $name = trim((string) ($product['name'] ?? ''));
            if (! $productId || $name === '' || ! $allMedia->has($productId)) {
                continue;
            }

            $score = 0;
            $matchedVariant = null;

            // Product name tokens (words >= 3 chars) in the description.
            $nameTokens = array_filter(
                preg_split('/[\s,\/\-]+/', mb_strtolower($name)) ?: [],
                fn ($t) => mb_strlen($t) >= 3,
            );
            foreach ($nameTokens as $token) {
                if (str_contains($desc, $token)) {
                    $score += 2;
                }
            }

            foreach ($allMedia[$productId] as $media) {
                $variant = mb_strtolower(trim((string) $media->variant_label));
                if ($variant !== '' && str_contains($desc, $variant)) {
                    $score += 3;
                    $matchedVariant = $media->variant_label;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestHint = $matchedVariant
                    ? sprintf('Possibly matches "%s" (variant: %s)', $name, $matchedVariant)
                    : sprintf('Possibly matches "%s"', $name);
            }
        }

        return $bestScore >= 2 ? $bestHint : '';
    }

    /**
     * Resolve a marker to zero or more ProductMedia rows.
     *
     * Behaviour per marker shape:
     *   - variant=<label>  → single row matching that variant (substring,
     *                         case-insensitive). Falls back to primary if no
     *                         variant hit, so the AI still sends *something*.
     *   - primary (flag)   → single row where is_primary=true, else first.
     *   - no qualifier     → ALL autosendable rows for the product, ordered
     *                         by display_order (then id). Customers asking
     *                         'can I see images?' expect every view, not one.
     *
     * Only rows with allow_ai_autosend=true are ever returned.
     */
    protected function resolveMedia(AiConfig $config, string $productId, array $args): \Illuminate\Support\Collection
    {
        $base = ProductMedia::where('ai_config_id', $config->id)
            ->where('product_key', $productId)
            ->where('allow_ai_autosend', true)
            ->orderBy('display_order')
            ->orderBy('id');

        $variant = $args['variant'] ?? null;
        if ($variant !== null && trim($variant) !== '') {
            $wanted = trim($variant);
            $all = $base->get();
            foreach ($all as $media) {
                $label = (string) $media->variant_label;
                if ($label !== '' && stripos($label, $wanted) !== false) {
                    return collect([$media]);
                }
            }
            // No variant match — fall back to primary so the customer sees something.
            $primary = $all->firstWhere('is_primary', true) ?? $all->first();
            return $primary ? collect([$primary]) : collect();
        }

        $primaryOnly = array_key_exists('primary', $args)
            || ($args['primary'] ?? null) === 'true'
            || ($args['primary'] ?? null) === '1';

        if ($primaryOnly) {
            $primary = (clone $base)->where('is_primary', true)->first() ?? (clone $base)->first();
            return $primary ? collect([$primary]) : collect();
        }

        // Default: return ALL sendable rows for the product. The outer loop
        // in extractMarkers applies the per-reply cap after dedupe.
        return $base->get();
    }

    /**
     * Parse "product_id=xyz,variant=burgundy" → ['product_id' => 'xyz', 'variant' => 'burgundy'].
     * Tolerant of whitespace and optional surrounding quotes.
     */
    protected function parseMarkerArgs(string $argString): array
    {
        $parts = preg_split('/\s*,\s*/', $argString) ?: [];
        $args = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            // Flag-style token with no '=' → treat as presence flag (value = true).
            // Lets the AI write ',primary' as documented without needing ',primary=true'.
            if (! str_contains($part, '=')) {
                $args[strtolower($part)] = true;
                continue;
            }
            [$k, $v] = explode('=', $part, 2);
            $k = strtolower(trim($k));
            $v = trim($v, " \t\n\r\0\x0B\"'");
            if ($k !== '' && $v !== '') {
                $args[$k] = $v;
            }
        }

        return $args;
    }
}
