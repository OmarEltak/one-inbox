<?php

declare(strict_types=1);

namespace App\Services\Email;

use App\Models\CampaignRecipient;
use Illuminate\Support\Facades\URL;

/**
 * Render a campaign subject + body for a single recipient.
 *
 * Supports {{name}}, {{email}}, and {{custom_field_key}} substitution.
 * Appends an unsubscribe footer and a tracking pixel to the body automatically.
 */
class TemplateRenderer
{
    /**
     * @return array{subject: string, body: string}
     */
    public function render(string $subject, string $body, CampaignRecipient $recipient): array
    {
        $vars = $this->buildVars($recipient);

        return [
            'subject' => $this->substitute($subject, $vars),
            'body'    => $this->appendFooter(
                $this->substitute($body, $vars),
                $recipient
            ),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function buildVars(CampaignRecipient $recipient): array
    {
        $vars = [
            'name'  => $recipient->name ?? $this->guessNameFromEmail($recipient->email),
            'email' => $recipient->email,
        ];

        foreach ($recipient->custom_fields ?? [] as $key => $value) {
            $vars[$key] = is_scalar($value) ? (string) $value : '';
        }

        return $vars;
    }

    private function substitute(string $template, array $vars): string
    {
        return preg_replace_callback(
            '/\{\{\s*([A-Za-z0-9_\-\. ]+)\s*\}\}/',
            static function (array $m) use ($vars): string {
                $key = trim($m[1]);
                if (array_key_exists($key, $vars)) {
                    return $vars[$key];
                }
                // Case-insensitive fallback for column header variance.
                foreach ($vars as $vk => $vv) {
                    if (strcasecmp($vk, $key) === 0) {
                        return $vv;
                    }
                }
                return '';
            },
            $template
        ) ?? $template;
    }

    private function appendFooter(string $body, CampaignRecipient $recipient): string
    {
        $unsubUrl = URL::signedRoute(
            'email.unsubscribe.show',
            ['recipient' => $recipient->id]
        );

        $pixelUrl = URL::signedRoute(
            'email.track.open',
            ['recipient' => $recipient->id]
        );

        $footer  = "\n\n---\n";
        $footer .= "If you no longer wish to receive emails, click here to unsubscribe:\n";
        $footer .= $unsubUrl;
        $footer .= "\n\n<img src=\"{$pixelUrl}\" width=\"1\" height=\"1\" alt=\"\" style=\"display:none\" />";

        return $body.$footer;
    }

    private function guessNameFromEmail(string $email): string
    {
        $local = strstr($email, '@', true);
        return $local !== false ? $local : '';
    }
}
