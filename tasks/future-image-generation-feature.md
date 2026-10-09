# Future Feature — AI Image Generation

**Captured 2026-10-09.** Build after the NaraRouter self-healing chain + Deep Analysis caching work settles.

## The idea

Add AI image generation to the OT1-Pro product. Customer paste a prompt → we generate an image. Use cases:
- Social media post graphics generated inside the inbox before sending to Instagram
- Product-mockup previews for e-commerce customers answering "what does the blue version look like?"
- Marketing campaign hero images generated from a brief
- Comment-reply decorative images for IG comment AI

## Models the NaraRouter API exposes (verified via webapp 2026-10-09)

The webapp has an "Image generation models" section separate from the text/vision models. These are billed per image by (size × quality) not per token:

| Model | 1K image | 2K image | 4K image |
|---|---|---|---|
| `agnes-image-2.0-flash` | Rp10 ($0) | Rp30 ($0) | Rp50 ($0) |
| `agnes-image-2.1-flash` | Rp20 ($0) | Rp40 ($0) | Rp60 ($0) |
| `gpt-image-2` | Rp50 ($0) | Rp50 ($0) | Rp50 ($0) |

The "$0" shown is because they round down below $0.01 at these IDR amounts — in practice the images are effectively free on the Free plan, same semantic as `agnes-2.5-flash` for text (nonzero IDR but zero USD-rounded).

## What this would need in our stack

- **New `/v1/images` endpoint call** on NaraRouter (OpenAI-compatible — standard `prompt`, `size`, `quality`, `n` params).
- **Credit cost per image** in `config/ai_costs.php` (image gen is coarser-grained than text — probably 10-50 credits per image depending on size).
- **New `generated_images` table**: `id, team_id, user_id, model, prompt, size, quality, url, cost_credits, status, created_at`. Images stored on R2 or similar.
- **New Livewire component** `app/Livewire/Images/Generate.php` — a prompt input + model picker + size picker + generation history grid.
- **Integration points**:
    - Inbox composer: "Generate an image for this reply" button → pre-fills the prompt from context, inserts the generated image as the message attachment.
    - IG comment AI: optional "attach image to reply" setting in `config.comment_settings` for brands that want their comment replies to include a decorative image.
    - AI chat operator surface: "generate an image for X" prompt detection, same dispatch-with-confirmation pattern we use for Deep Analysis.
- **Storage**: images are not tiny. 4K images are ~5-10 MB. Need either R2/S3 integration or disk-storage with CDN. The latter is cheaper initially.
- **Content moderation**: NaraRouter likely does their own moderation, but we should log + rate-limit at our layer too.

## Pricing / plan tier placement

Given the per-image cost pattern, image gen fits naturally as:
- **Free plan**: 10 images/month, 1K size only
- **Starter**: 50 images/month, up to 2K
- **Pro**: 500 images/month, up to 4K
- **Business**: 5,000 images/month, all sizes

Or alternatively, bill per-image at a fixed credit cost (e.g. 5 credits per 1K, 15 per 2K, 50 per 4K) and let the existing AI credit economy handle it — no per-plan quota needed. The second is simpler and reuses the credit ledger infra we already shipped.

## Dependencies before this ships

- NaraRouter self-healing chain refresh (should auto-discover these 3 image models and categorize them as `image` chain)
- Decision on storage (R2 vs disk + CDN)
- UI design for the generation surface (dashboard tab? inbox popover? both?)

## Related existing pieces

- `app/Services/Media/*` — our image handling for inbound customer images
- `app/Jobs/DescribeImage.php` — we already call vision models for inbound images; outbound generation is the mirror image
- `config/ai_costs.php` — would get a new `image_generation` cost family

## Rough effort estimate

- Backend (API client + storage + models + cost): ~3 days
- Livewire UI: ~2 days
- Inbox composer integration: ~1 day
- IG comment AI integration: ~1 day
- Marketing / blog post about the feature: ~1 day

Total: ~1.5 weeks of focused work. Non-blocking — ship after Deep Analysis caching + self-healing chain are live.
