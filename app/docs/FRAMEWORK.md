# UI & application framework

Chatbot Hub Pro uses a **layered front-end framework** so dashboard, auth, demo site, and widget stay consistent and deployable.

## CSS layers (load order)

| Layer | File | Purpose |
|-------|------|---------|
| 1 | `public/css/framework.css` | Layout primitives, responsive grids, tables, touch targets |
| 2 | `public/css/dashboard-theme.css` | Design tokens, dashboard shell, components |
| 3 | `public/css/live-chat.css` | Inbox only |
| 4 | `public/css/demo-site.css` | Demo marketing site |
| 5 | `public/widget/chatbot.css` | Embeddable widget |

Vite (`resources/css/app.css`) augments pages that use `@vite` when `public/build/manifest.json` exists.

## Website workspace (per-site pages)

- **Sticky tab nav** — `partials/workspace-nav.blade.php` on hub, settings, knowledge, train, unanswered, keywords, quick actions, webhooks, analytics, plugin
- **Websites index** — card layout on mobile/tablet; table on desktop (≥1024px)
- **Actions** — desktop dropdown; mobile bottom sheet (`website-workspace.css`, `dashboard-ui.js`)
- CSS: `public/css/website-workspace.css`

## Blade components (`<x-dash.*>`)

| Component | Usage |
|-----------|--------|
| `<x-dash.page-header>` | Page title block with optional eyebrow/subtitle |
| `<x-dash.card>` | Content card with optional title |
| `<x-dash.alert type="success\|warning\|danger\|info">` | Flash-style alerts |
| `<x-dash.button variant="primary\|secondary\|ghost" href="...">` | Buttons/links |

Example:

```blade
<x-dash.page-header eyebrow="Websites" title="Multi-website chatbots" subtitle="Each site is isolated." />

<x-dash.card title="Settings">
    <p>Content here</p>
</x-dash.card>
```

## Layout sections

Dashboard pages extend `layouts.app` and may define:

- `@section('page-header')` — title in top bar
- `@section('page-toolbar')` — actions below header
- `@section('content')` — main area (use `.dash-page` wrapper)

## Responsive breakpoints

- **Mobile**: &lt; 768px — sidebar drawer, stacked forms, full-width widget
- **Tablet**: 768px–1023px
- **Desktop**: ≥ 1024px — fixed sidebar, multi-column grids

## PHP structure

```
app/
  Http/Controllers/   Dashboard + Api + Auth
  Services/           Business logic (widget config, chat, webhooks)
  Models/             Eloquent per tenant (organization scoped)
resources/views/
  components/dash/    Reusable UI
  dashboard/          Feature pages
  demo/               Client preview site
public/widget/        Embeddable JS/CSS (CDN-ready via loader.js)
```

## Pre-deploy check

```bash
php artisan platform:doctor
```

See [DEPLOYMENT.md](./DEPLOYMENT.md) for production steps.
