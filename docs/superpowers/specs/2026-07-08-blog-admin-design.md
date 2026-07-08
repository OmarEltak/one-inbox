# Blog Admin & SEO Enhancement — Design Spec
**Date:** 2026-07-08
**Author:** Omar Eltak
**Status:** Approved

---

## Goal

Build a personal blog writing interface accessible only to the super-admin, add language support to posts (Arabic/English/other), and maximise SEO + LLM citation signals on the public blog.

---

## Section 1 — Data Layer

### Migration

Add two columns to the `posts` table:

| Column | Type | Default | Purpose |
|--------|------|---------|---------|
| `language` | `string` | `'en'` | ISO 639-1 language code (`en`, `ar`, `fr`, etc.) |
| `is_rtl` | `boolean` | `false` | Stored flag — avoids lookup table on render |

### Post Model changes

- Add `language` and `is_rtl` to `$fillable`
- Add `scopeForLanguage($query, string $lang)` — filters published posts by language
- Add `static languages(): array` — returns distinct language values from published posts (used to build the filter UI dynamically)

### State machine (existing — no change)

The existing `published_at` nullable column handles all states:
- `null` → draft (invisible to public)
- future timestamp → scheduled (invisible until that time)
- past/present timestamp → published (live)

---

## Section 2 — Blog Admin UI

### Access

Route group: existing `super-admin` middleware (`/super-admin/*`). No new auth logic needed — `is_super_admin` on the User model already gates this area.

### Routes

```
GET  /super-admin/blog              → SuperAdmin\Blog\Index
GET  /super-admin/blog/create       → SuperAdmin\Blog\Editor (new)
GET  /super-admin/blog/{post}/edit  → SuperAdmin\Blog\Editor (edit)
DELETE /super-admin/blog/{post}     → SuperAdmin\Blog\Index (inline action)
```

### `App\Livewire\SuperAdmin\Blog\Index`

Responsibilities:
- Table of all posts (draft + scheduled + published) sorted by `updated_at` desc
- Columns: status badge, language badge, title, published_at, actions
- Status badge colours: grey=draft, amber=scheduled, green=published
- "New Post" button → navigates to `/super-admin/blog/create`
- Edit action → navigates to `/super-admin/blog/{post}/edit`
- Delete action → soft-confirm inline (Flux modal), then hard-delete

### `App\Livewire\SuperAdmin\Blog\Editor`

Responsibilities:
- Bound to a `Post` model (new or existing)
- Fields:
  - `title` (text input) — triggers auto-slug generation
  - `slug` (text input, editable, unique validation)
  - `excerpt` (textarea, max 300 chars, char counter)
  - `language` (select: `en` English, `ar` Arabic — extensible)
  - `category` (text input, default `general`)
  - `reading_time` (text input, default `5 min read`)
  - `meta_title` (text input, max 60 chars, char counter)
  - `meta_description` (textarea, max 160 chars, char counter)
  - TipTap WYSIWYG editor (see below)
- Three publish actions:
  - **Save draft** — sets `published_at = null`, saves
  - **Publish now** — sets `published_at = now()`, saves
  - **Schedule** — opens date-time picker, sets `published_at` to chosen future time, saves
- RTL: when `language === 'ar'`, the TipTap editor container gets `dir="rtl"`

### TipTap Editor

Extensions enabled (minimal set):
- `StarterKit` (Bold, Italic, Strike, Paragraph, Headings H2/H3, BulletList, OrderedList, Blockquote, HardBreak, History)
- `Color` + `TextStyle` — inline text colour picker
- `Link` — insert/edit hyperlinks with `href`, `target="_blank"`, `rel="noopener noreferrer"`
- `Image` — two input modes:
  - **URL tab**: paste an external image URL
  - **Upload tab**: file picker → Livewire `$file` upload → stored at `storage/app/public/blog/images/` → inserted as `/storage/blog/images/{filename}`

Content stored as HTML in `posts.content` (existing `longText` column).

---

## Section 3 — Public Blog Changes

### Language filter on `/blog`

- Filter bar above post grid: **All** | **English** | **Arabic** | *(any other language with ≥1 published post)*
- Languages shown dynamically via `Post::languages()`
- Active filter passed as query param: `/blog?lang=ar`
- `BlogController::index()` applies `scopeForLanguage()` when `lang` param is present
- No JS routing — plain GET links

### Per-post view (`/blog/{slug}`)

#### HTML lang + dir

```html
<html lang="{{ $post->language }}" @if($post->is_rtl) dir="rtl" @endif>
```

Post content wrapper:
```html
<div class="prose" @if($post->is_rtl) dir="rtl" @endif>
    {!! $post->content !!}
</div>
```

#### Schema.org `BlogPosting` (full, replaces current lightweight version)

```json
{
  "@context": "https://schema.org",
  "@type": "BlogPosting",
  "headline": "{{ $post->title }}",
  "description": "{{ $post->excerpt }}",
  "url": "{{ route('blog.show', $post->slug) }}",
  "datePublished": "{{ $post->published_at->toIso8601String() }}",
  "dateModified": "{{ $post->updated_at->toIso8601String() }}",
  "inLanguage": "{{ $post->language }}",
  "wordCount": {{ preg_match_all('/\S+/u', strip_tags($post->content), $m) ? count($m[0]) : 0 }},
  "author": {
    "@type": "Person",
    "name": "Omar Eltak",
    "url": "https://ot1-pro.com/about"
  },
  "publisher": {
    "@type": "Organization",
    "name": "OT1-Pro",
    "url": "https://ot1-pro.com",
    "logo": { "@type": "ImageObject", "url": "https://ot1-pro.com/logo.png" }
  },
  "image": "https://ot1-pro.com/og-image.png",
  "mainEntityOfPage": { "@type": "WebPage", "@id": "{{ route('blog.show', $post->slug) }}" }
}
```

#### `BreadcrumbList` schema on every post

```json
{
  "@type": "BreadcrumbList",
  "itemListElement": [
    { "@type": "ListItem", "position": 1, "name": "Home", "item": "/" },
    { "@type": "ListItem", "position": 2, "name": "Blog", "item": "/blog" },
    { "@type": "ListItem", "position": 3, "name": "{{ $post->title }}", "item": "{{ route('blog.show', $post->slug) }}" }
  ]
}
```

#### Open Graph tags

```html
<meta property="og:title" content="{{ $post->meta_title }}">
<meta property="og:description" content="{{ $post->meta_description }}">
<meta property="og:image" content="https://ot1-pro.com/og-image.png">
<meta property="og:type" content="article">
<meta property="article:published_time" content="{{ $post->published_at->toIso8601String() }}">
<meta property="article:modified_time" content="{{ $post->updated_at->toIso8601String() }}">
<meta property="article:author" content="Omar Eltak">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $post->meta_title }}">
<meta name="twitter:description" content="{{ $post->meta_description }}">
```

#### Canonical tag

```html
<link rel="canonical" href="{{ route('blog.show', $post->slug) }}">
```

### Sitemap update

Switch `<lastmod>` for blog posts from `published_at` to `updated_at` — signals content freshness to Google on every edit.

---

## Files to Create / Modify

| File | Action |
|------|--------|
| `database/migrations/XXXX_add_language_to_posts_table.php` | Create |
| `app/Models/Post.php` | Modify — add fillable, scopes |
| `app/Livewire/SuperAdmin/Blog/Index.php` | Create |
| `app/Livewire/SuperAdmin/Blog/Editor.php` | Create |
| `resources/views/livewire/super-admin/blog/index.blade.php` | Create |
| `resources/views/livewire/super-admin/blog/editor.blade.php` | Create |
| `routes/web.php` | Modify — add 4 blog admin routes inside super-admin group |
| `resources/views/blog/show.blade.php` | Modify — full schema, OG tags, canonical, lang/dir |
| `resources/views/blog/index.blade.php` | Modify — language filter bar |
| `app/Http/Controllers/BlogController.php` | Modify — lang filter in index() |
| `routes/web.php` (sitemap) | Modify — lastmod → updated_at |

---

## Out of Scope

- Comments / reactions on posts
- Multi-author support
- Post categories admin UI (category is a free-text field)
- Pagination on the admin list (super-admin only, post count stays low)
- Image CDN / optimization pipeline
