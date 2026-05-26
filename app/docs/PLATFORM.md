# AI Chatbot Hub Pro — Platform Architecture

Centralized multi-website SaaS for deploying, training, and managing AI chatbot widgets.

## Spec coverage map

| Requirement | Status | Location |
|-------------|--------|----------|
| Unlimited websites per org | Done | `Website`, `WebsiteController` |
| Per-site isolated training | Done | Q&A, knowledge, keywords scoped by `website_id` |
| 2-step create wizard (+ channels) | Done | `wizard-step1/2`, `widget_channels` on create |
| Plugin ZIP download | Done | `WidgetPluginExportService`, `websites.embed.download` |
| Embed / init code | Done | `WidgetEmbedController`, `Website::embedSnippet()` |
| Q&A + bulk CSV import | Done | `QaPairController::import`, knowledge import |
| Trigger keywords UI | Done | `TriggerKeywordController`, `keywords.blade.php` |
| Quick actions (url/email/phone/whatsapp) | Done | `QuickActionController`, widget `handleQuickAction` |
| Design customization | Done | `ChatbotConfiguration`, `edit.blade.php` |
| WhatsApp + Email below input | Done | `widget_channels` JSON + `renderChannelButtons()` |
| Live chat + archive | Done | `AgentInboxController`, inbox archive |
| Unanswered questions | Done | `UnansweredQuestion`, promote to Q&A |
| Unanswered bulk per-row promote | Done | `answers[id]` on `websites.unanswered.bulk` |
| Analytics | Done | `AnalyticsController` |
| Roles + permissions matrix UI | Done | `RolePermissionMatrix`, org settings page |
| Role enforcement (controllers) | Done | `AuthorizesTenantRole` on websites, inbox writes, org settings |
| Dashboard today stats | Done | `AnalyticsService::organizationTodayStats`, home slice |
| Website hub polish | Done | Hub: config version, token/snippet copy, unanswered preview |
| Widget loader CDN base | Done | `loader.js` `data-cdn-base`, `docs/API.md` |
| Widget security | Done | `bot_token`, domain validation, rate limits |
| Instant config updates | Done | `config_version` + 15–45s poll in widget |
| Widget fullscreen / draggable | Done | `widget_fullscreen`, `widget_draggable` modules |
| Widget position + offsets | Done | `position`, `security_settings` offsets → widget CSS vars |
| CSAT on close | Done | Widget close → rating overlay; `conversation_ratings`; inbox session panel |
| Business hours | Done | `OperatingHour`, schedule on website edit, widget offline state |
| Inbox conversations CSV export | Done | `inbox.export`, `ConversationExportService` |
| Unanswered bulk actions | Done | `websites.unanswered.bulk` dismiss / promote |
| Websites index quick stats | Done | Open chats + unanswered columns on `websites.index` |
| Agent browser notifications | Done | `live-chat.js` + inbox Notify button (`localStorage`) |
| REST API docs stub | Done | `docs/API.md` (widget routes, loader `data-cdn-base`, 429 shape) |
| Dashboard today stats | Done | `AnalyticsService::organizationTodayStats`, dashboard index |
| Website hub polish | Done | `ChatbotHubController` — config version, token/snippet copy, unanswered preview |
| Role enforcement (websites/inbox/settings) | Done | `AuthorizesTenantRole` on controllers + feature tests |
| Widget domain allowlist | Done | `ValidateWidgetDomain`, `WidgetDomainValidationTest` |
| Widget 429 feedback | Done | `WidgetRateLimitService::rateLimitResponse`, widget chat error copy |
| Inbox internal notes | Done | `inbox.notes.store`, visitor panel form |
| Demo SkyCut seed data | Done | `DatabaseSeeder` — Q&A samples, Mon–Fri hours |
| OpenAI | Done | `ChatResponseService` |
| WebSockets (agents) | Partial | Reverb + Echo; widget uses polling |
| Webhooks (lead/chat/close) | Done | `WebhookDispatchService`, `WebhookDispatchTest` |
| Duplicate website | Done | `websites.duplicate` |
| Channels settings UI | Done | Website edit → WhatsApp & email section |
| Team email invites | Done | Org settings → pending invite + accept link; `OrganizationInviteMail` on create; `team.create` for manual add |
| Automation rules UI | Done | `automation-rules.index`, hub quick action, create/update tests |
| Knowledge article search | Done | `websites.knowledge.index` filters title/body via `q` |
| Widget notification sound | Done | `sound_enabled` on config + website edit; widget mute via `localStorage` |
| Inbox saved filter views | Done | Per-agent `inbox_filter_presets`; inbox toolbar save/load |
| Widget custom CSS preview | Done | Website edit live preview; values in `WidgetConfigService` |
| API health check | Done | `GET /api/health` (DB ping; use alongside Laravel `/up`) |

| UI framework (components + CSS) | Done | `docs/FRAMEWORK.md`, `public/css/framework.css`, `<x-dash.*>` |
| Deploy readiness | Done | `docs/DEPLOYMENT.md`, `platform:doctor`, `scripts/deploy-production.*` |

## Core flow

1. Admin logs in → Dashboard
2. **Websites → New** → Step 1 (site info) → Step 2 (bot config)
3. Redirect to **Embed / Plugin** → copy code or **Download ZIP**
4. Install on client site (one script tag; live config from API)
5. Train per site: Q&A, keywords, knowledge upload, unanswered → Q&A
6. Visitors chat; handoff to agents; leads in CRM

## API

See **[API.md](./API.md)** for widget and dashboard JSON endpoint tables.

## Database (primary tables)

- `websites`, `chatbot_configurations`
- `qa_pairs`, `trigger_keywords`, `quick_actions`
- `conversations`, `messages`, `leads`, `conversation_ratings`
- `unanswered_questions`, `operating_hours`
- `knowledge_*`, `analytics_events`

## Next enhancements (optional)

- Visitor WebSocket channel
- Per-org CDN URL in dashboard (loader supports `data-cdn-base` manually today)
- Webhook retry queue / delivery logs
- Editable per-org role permission overrides (matrix is read-only; fixed roles in `UserRole` enum)
