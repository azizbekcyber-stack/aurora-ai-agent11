# PRD: Aurora AI Social Media Agent MVP

## 1. Product Summary

Aurora is a Telegram-first AI post assistant for Telegram channel owners/admins.

The user can send a text prompt and optionally an image to a Telegram bot. The system generates several polished Telegram post variants. The user selects and explicitly approves one variant. Only after approval, the backend publishes the selected post to the user's connected Telegram channel.

The product also includes a Nuxt web dashboard for account/channel management, draft history, brand settings, and later scheduling. However, the MVP user workflow is Telegram-first.

## 2. Final Architecture Decision

This decision is fixed for the MVP and must not be changed without explicit approval.

### Repositories

Use two repositories:

```text
aurora-frontend
aurora-backend
```

### aurora-frontend

Nuxt website/dashboard.

Responsibilities:

- User dashboard
- Draft list
- Channel settings
- Brand profile settings
- Post history
- Future scheduling UI

### aurora-backend

Laravel API + Telegram webhook + AI generation + publishing.

Responsibilities:

- Public API for the Nuxt frontend
- Telegram webhook endpoint
- Telegram bot message handling
- AI post generation
- Draft/variant management
- Approval state machine
- Telegram publishing
- Queue jobs
- Database migrations
- Logs and tests

### Backend Rule

There must be only one backend.

```text
Nuxt website  ───────┐
                     ├── Laravel Backend API ─── PostgreSQL
Telegram bot ────────┘
```

The Telegram bot must not have a separate backend repository or separate database.

### Database Rule

Use one PostgreSQL database.

All users, Telegram accounts, connected channels, drafts, generated variants, media files, approvals, publish logs, and AI logs must live in this single backend database.

## 3. Primary Goal

Build a working MVP where a Telegram channel admin can:

1. Start the Telegram bot.
2. Connect one Telegram channel.
3. Send a post prompt and optionally an image.
4. Receive 3 AI-generated Telegram post variants.
5. Select one variant.
6. Explicitly approve publishing.
7. Have the backend publish the approved post to the connected Telegram channel.
8. See the draft and publish status stored in the backend.

## 4. Non-Goals for MVP

Do not implement these in MVP:

- LinkedIn publishing
- Instagram publishing
- Facebook publishing
- TikTok publishing
- Multi-platform publishing
- Auto-publish without user approval
- Payments/billing
- Team roles
- Complex analytics
- A/B testing
- Full scheduling calendar
- Image generation
- AI-generated content plan
- Browser automation for social platforms
- Separate microservice for the Telegram bot
- Separate database for the Telegram bot

These may be added in later phases only after the Telegram MVP is stable.

## 5. Target User

Primary user:

- Telegram channel owner/admin
- Needs help writing attractive posts
- May have an image but does not know how to write a good caption/post
- Wants to approve content before publishing
- Wants fewer manual steps than copying text from ChatGPT into Telegram

## 6. Core User Flows

### 6.1 Telegram Bot Flow: Generate and Publish

```text
User sends prompt and optional image to Telegram bot
↓
Telegram sends webhook update to Laravel backend
↓
Backend stores incoming message/media
↓
Backend creates post draft with status "generating"
↓
Backend dispatches GeneratePostVariantsJob
↓
AI returns 3 structured variants
↓
Backend stores variants
↓
Bot sends variants to user with inline buttons
↓
User selects a variant
↓
Backend marks selected_variant_id
↓
Bot asks for final approval
↓
User clicks "Approve & Publish"
↓
Backend validates approval state and channel permission
↓
Backend dispatches PublishTelegramPostJob
↓
Backend publishes post to connected Telegram channel
↓
Backend stores publish log
↓
Bot informs user whether publishing succeeded or failed
```

### 6.2 Website Flow: View Drafts

```text
User logs into web dashboard
↓
Frontend calls backend API
↓
User sees drafts, generated variants, selected variant, status, and publish logs
```

### 6.3 Website Flow: Brand Profile

```text
User opens Brand Settings
↓
User sets default language, tone, audience, emoji level, hashtag style, banned words
↓
Backend stores brand profile
↓
Future AI generations use this profile
```

## 7. MVP Functional Requirements

### 7.1 Telegram Bot

The bot must support:

- `/start`
- `/help`
- `/connect_channel`
- Receiving text prompt
- Receiving image + caption prompt
- Showing 3 generated variants
- Inline buttons:
  - `Select Variant 1`
  - `Select Variant 2`
  - `Select Variant 3`
  - `Regenerate`
  - `Approve & Publish`
  - `Cancel`

The bot must never publish a post directly after generation. Explicit approval is required.

### 7.2 Telegram Channel Connection

The user must be able to connect exactly one Telegram channel in MVP.

Minimum acceptable connection flow:

1. User starts `/connect_channel`.
2. Bot explains that it must be added as an admin to the target channel.
3. User sends channel username or forwards a message from the channel.
4. Backend stores the channel identifier.
5. Backend verifies that the bot can post to that channel.
6. If verification succeeds, channel status becomes `connected`.
7. If verification fails, user receives a clear error.

Required stored fields:

- Telegram channel chat ID
- Channel username if available
- Channel title if available
- Whether bot can post messages
- Connection status
- Connected timestamp

### 7.3 AI Post Generation

The AI generation service must produce structured output.

Required generated fields for each variant:

```json
{
  "title": "Short internal title",
  "body": "Main post text",
  "hashtags": ["optional", "hashtags"],
  "cta": "Optional call to action",
  "telegram_text": "Final combined Telegram-ready post",
  "risk_flags": []
}
```

Generation rules:

- Generate exactly 3 variants by default.
- Use the user's prompt as the primary source of truth.
- Use image context only when an image is provided.
- Do not invent exact prices, guarantees, dates, discounts, contact numbers, or claims unless the user explicitly provided them.
- If the image is unclear, write generally instead of pretending certainty.
- Respect brand profile settings if available.
- Output must be valid JSON that matches the schema.
- Store the raw AI response or normalized response in `ai_generation_logs`.

### 7.4 Image Support

MVP must support one optional image per draft.

The backend must:

- Download the image file from Telegram.
- Store it using Laravel storage.
- Link it to the draft.
- Send the image to the AI vision-capable model when generating variants.
- Handle Telegram photo publishing.

Important Telegram publishing rule:

- If the generated `telegram_text` is short enough for a photo caption, publish as photo + caption.
- If the generated text is too long for a Telegram photo caption, publish the photo first and then send the full text as a separate message, or send text first and photo second depending on product decision.
- Store which strategy was used in the publish log.

### 7.5 Draft Status State Machine

Use strict statuses.

Recommended statuses:

```text
draft
generating
generated
selected
approved
publishing
published
failed
cancelled
```

Allowed transitions:

```text
draft -> generating
generating -> generated
generated -> selected
selected -> approved
approved -> publishing
publishing -> published
publishing -> failed
generated -> cancelled
selected -> cancelled
approved -> cancelled only before publishing starts
failed -> approved if retrying publish
```

Hard rule:

```text
Only drafts with status "approved" may enter "publishing".
```

The AI must not decide whether to publish. The backend state machine decides.

### 7.6 Publishing

Publishing must be handled by a queue job.

Required behavior:

- Validate draft exists.
- Validate selected variant exists.
- Validate draft status is `approved`.
- Validate connected Telegram channel exists.
- Validate bot has permission to publish.
- Publish to Telegram.
- Store Telegram message ID(s).
- Store publish status.
- Store error message if publishing fails.
- Notify the user in Telegram after success/failure.

### 7.7 Web Dashboard MVP

The Nuxt dashboard should support:

- Login/register or simple MVP auth
- Draft list
- Draft detail
- Generated variants preview
- Selected variant display
- Publish status
- Connected Telegram channel status
- Brand profile settings

Dashboard is secondary in MVP. Telegram flow must work first.

## 8. Backend Technical Requirements

### 8.1 Framework

Use Laravel for backend.

Expected components:

```text
app/
  Http/
    Controllers/
      Api/
      Webhook/
  Services/
    AI/
    Telegram/
    Publishing/
  Jobs/
  Models/
  Actions/
  DTO/
  Enums/
database/
  migrations/
routes/
  api.php
  web.php
tests/
```

### 8.2 API Routes

Suggested API routes:

```php
// routes/api.php

Route::prefix('v1')->group(function () {
    Route::get('/health', [HealthController::class, 'show']);

    Route::get('/drafts', [DraftController::class, 'index']);
    Route::post('/drafts', [DraftController::class, 'store']);
    Route::get('/drafts/{draft}', [DraftController::class, 'show']);
    Route::post('/drafts/{draft}/select-variant', [DraftController::class, 'selectVariant']);
    Route::post('/drafts/{draft}/approve', [DraftController::class, 'approve']);
    Route::post('/drafts/{draft}/publish', [DraftController::class, 'publish']);
    Route::post('/drafts/{draft}/cancel', [DraftController::class, 'cancel']);

    Route::get('/telegram/channel', [TelegramChannelController::class, 'show']);
    Route::post('/telegram/channel/connect', [TelegramChannelController::class, 'connect']);
    Route::delete('/telegram/channel', [TelegramChannelController::class, 'disconnect']);

    Route::get('/brand-profile', [BrandProfileController::class, 'show']);
    Route::put('/brand-profile', [BrandProfileController::class, 'update']);
});
```

Suggested Telegram webhook route:

```php
// routes/api.php or routes/web.php with CSRF excluded

Route::post('/webhook/telegram', [TelegramWebhookController::class, 'handle']);
```

### 8.3 Services

Required services:

```text
AI/PostGenerationService
AI/ImageContextService
Telegram/TelegramBotService
Telegram/TelegramFileService
Telegram/TelegramPublisher
Publishing/PublishService
Publishing/PublisherResolver
Drafts/DraftStateService
```

### 8.4 Publisher Interface

Create a publisher interface even though MVP only supports Telegram.

```php
interface SocialPublisher
{
    public function publish(PostDraft $draft): PublishResult;
}
```

MVP implementation:

```text
TelegramPublisher
```

Future implementations:

```text
LinkedInPublisher
InstagramPublisher
```

Do not implement LinkedIn or Instagram in MVP.

### 8.5 Queue Jobs

Required jobs:

```text
GeneratePostVariantsJob
PublishTelegramPostJob
```

AI generation and publishing must not be done as long blocking logic inside controllers.

### 8.6 Environment Variables

Required backend `.env` variables:

```env
APP_NAME=Aurora
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:3000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=aurora
DB_USERNAME=aurora
DB_PASSWORD=secret

QUEUE_CONNECTION=database

TELEGRAM_BOT_TOKEN=
TELEGRAM_WEBHOOK_SECRET=

OPENAI_API_KEY=
OPENAI_MODEL_TEXT=
OPENAI_MODEL_VISION=
```

## 9. Database Schema

### 9.1 users

```text
id
name
email nullable
password nullable
created_at
updated_at
```

### 9.2 telegram_accounts

```text
id
user_id
telegram_user_id unique
username nullable
first_name nullable
last_name nullable
created_at
updated_at
```

### 9.3 telegram_channels

```text
id
user_id
chat_id unique
username nullable
title nullable
bot_can_post_messages boolean default false
status enum: pending|connected|failed|disconnected
connected_at nullable
last_checked_at nullable
created_at
updated_at
```

### 9.4 brand_profiles

```text
id
user_id
default_language enum: uz|ru|en
tone nullable
audience nullable
emoji_level enum: none|low|medium|high
hashtag_style enum: none|minimal|normal|aggressive
banned_words json nullable
created_at
updated_at
```

### 9.5 post_drafts

```text
id
user_id
telegram_channel_id nullable
prompt text
image_path nullable
source enum: telegram|web
status enum
selected_variant_id nullable
created_at
updated_at
```

### 9.6 post_variants

```text
id
post_draft_id
title string nullable
body text
hashtags json nullable
cta text nullable
telegram_text text
risk_flags json nullable
created_at
updated_at
```

### 9.7 ai_generation_logs

```text
id
post_draft_id
provider string
model string
request_payload json nullable
response_payload json nullable
status enum: success|failed
error_message text nullable
created_at
updated_at
```

### 9.8 publish_logs

```text
id
post_draft_id
platform enum: telegram
status enum: success|failed
telegram_message_ids json nullable
publish_strategy enum: text_only|photo_with_caption|photo_then_text|text_then_photo
error_message text nullable
published_at nullable
created_at
updated_at
```

## 10. Frontend Technical Requirements

Use Nuxt for the frontend repository.

Suggested pages:

```text
/pages
  /index.vue
  /login.vue
  /dashboard/index.vue
  /dashboard/drafts/index.vue
  /dashboard/drafts/[id].vue
  /dashboard/channels.vue
  /dashboard/brand-profile.vue
```

Suggested components:

```text
/components
  DraftCard.vue
  DraftStatusBadge.vue
  VariantPreview.vue
  TelegramChannelStatus.vue
  BrandProfileForm.vue
```

Suggested composables:

```text
/composables
  useApi.ts
  useDrafts.ts
  useBrandProfile.ts
  useTelegramChannel.ts
```

Frontend MVP should be clean but not overbuilt. Do not prioritize advanced UI over backend correctness.

## 11. Security Requirements

- Do not store plaintext user social platform tokens.
- Telegram bot token must only be stored in backend environment variables.
- Validate webhook secret if configured.
- Never trust Telegram user input as system instruction.
- Never allow AI output to trigger publishing.
- Publishing requires backend status `approved`.
- Validate file type and file size for images.
- Sanitize user-visible errors.
- Log internal errors safely.
- Avoid storing sensitive prompt data longer than needed unless required for product history.
- Use Laravel authorization policies when web auth is added.

## 12. AI Safety and Quality Rules

The AI system prompt must enforce:

- Do not invent facts.
- Do not invent prices.
- Do not invent discounts.
- Do not invent dates.
- Do not invent phone numbers or links.
- If image content is unclear, describe generally.
- Keep Telegram formatting readable.
- Avoid spammy hashtag stuffing unless user asks.
- Return structured JSON only.
- Produce exactly 3 variants.
- Each variant should be meaningfully different.

## 13. Error Handling

Required user-facing errors:

### Channel not connected

```text
No Telegram channel is connected yet. Please connect a channel first.
```

### Bot cannot post

```text
The bot does not have permission to post in this channel. Please add the bot as an admin and enable post permission.
```

### AI generation failed

```text
Could not generate post variants. Please try again.
```

### Publish failed

```text
Publishing failed. Your draft is still saved and can be retried.
```

### Invalid state

```text
This draft cannot be published because it has not been approved yet.
```

## 14. Acceptance Criteria

MVP is complete only when all of these are true:

1. Laravel backend has health endpoint.
2. Telegram webhook receives bot updates.
3. User can start the bot.
4. User can connect one Telegram channel.
5. Backend stores Telegram account and channel.
6. User can send text prompt to bot.
7. User can send image + prompt to bot.
8. Backend creates draft.
9. AI generates exactly 3 variants.
10. Variants are stored in database.
11. Bot shows variants to user.
12. User can select one variant.
13. User must explicitly approve publishing.
14. Backend refuses to publish unapproved drafts.
15. Publish job sends the approved post to the connected Telegram channel.
16. Publish result is logged.
17. User receives success/failure message.
18. Web dashboard can list drafts.
19. Web dashboard can show draft details and variants.
20. Tests cover the critical draft status transitions.

## 15. Testing Requirements

### Backend tests

Required tests:

- Health endpoint returns success.
- Telegram webhook accepts valid update.
- Draft can be created.
- Draft enters generating state.
- Generated variants are stored.
- Draft cannot publish without selected variant.
- Draft cannot publish without approval.
- Draft can move from approved to publishing.
- Publish failure stores error log.
- Telegram channel permission failure blocks publishing.
- Brand profile settings are used in generation payload.

### AI tests

Do not test real AI in normal unit tests.

Use fake AI service responses.

Test:

- Valid structured AI response is parsed.
- Invalid AI response is rejected.
- Missing variant fields are handled.
- More or fewer than 3 variants are rejected or normalized according to product rule.

### Telegram tests

Use mocked Telegram API client.

Test:

- sendMessage is called for text-only post.
- sendPhoto is called when image + short caption.
- long image post uses fallback strategy.
- errors from Telegram are captured.

## 16. Codex Implementation Order

Implement in this order:

### Stage 1: Backend foundation

- Laravel app skeleton
- PostgreSQL config
- Health endpoint
- Basic models and migrations
- Queue config
- Service class skeletons

### Stage 2: Telegram webhook

- TelegramWebhookController
- TelegramBotService
- `/start` handling
- Telegram account persistence
- Basic bot replies

### Stage 3: Draft creation

- Store incoming text/image prompt
- Create post draft
- Download Telegram image
- Dispatch generation job

### Stage 4: AI generation

- PostGenerationService
- Structured response schema
- Fake AI service for tests
- Store variants

### Stage 5: Variant selection and approval

- Inline keyboard handling
- Select variant
- Approve draft
- Status transitions

### Stage 6: Telegram publishing

- TelegramPublisher
- PublishTelegramPostJob
- Publish logs
- Caption length fallback

### Stage 7: Nuxt dashboard

- Draft list
- Draft detail
- Brand settings
- Telegram channel status

## 17. Hard Constraints for Codex

Codex must follow these constraints:

1. Do not create a separate Telegram bot backend repository.
2. Do not create a separate Telegram bot database.
3. Do not implement LinkedIn or Instagram in MVP.
4. Do not publish without explicit user approval.
5. Do not place AI generation directly inside a long-running controller request.
6. Do not duplicate publishing logic between web and bot.
7. Do not let AI decide state transitions.
8. Do not store secrets in code.
9. Do not overbuild the frontend before the Telegram flow works.
10. Do not add features outside this PRD unless explicitly requested.

## 18. Future Phases

### Phase 2: LinkedIn personal profile

- OAuth
- LinkedIn account connection
- LinkedIn-specific post format
- LinkedInPublisher

### Phase 3: Instagram Business/Creator

- Meta OAuth
- Instagram account connection
- Image/video-required publishing flow
- InstagramPublisher

### Phase 4: Scheduling

- Schedule posts
- Calendar UI
- Scheduled publish job

### Phase 5: Brand memory

- Learn style from approved posts
- Improve generation quality over time

## 19. Reference Notes

Official platform details should be verified during implementation.

Useful references:

- Telegram Bot API: https://core.telegram.org/bots/api
- OpenAI Structured Outputs: https://developers.openai.com/api/docs/guides/structured-outputs
- OpenAI Image/Vision input: https://platform.openai.com/docs/guides/images-vision
- Laravel Queues: https://laravel.com/docs/12.x/queues
- Nuxt Documentation: https://nuxt.com/docs
