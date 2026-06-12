<script setup lang="ts">
import { ImagePlus, Plus, Radio, RefreshCw, Send, ShieldCheck } from '@lucide/vue'

const { drafts, loading, error, fetchDrafts, createDraft } = useDrafts()
const { channel, fetchChannel } = useTelegramChannel()

const prompt = ref('')
const image = ref<File | null>(null)
const imageName = computed(() => image.value?.name || null)
const imageMeta = computed(() => {
  if (!image.value) {
    return null
  }

  return `${image.value.type || 'image'} · ${(image.value.size / 1024).toFixed(1)} KB`
})
const creating = ref(false)
const createError = ref<string | null>(null)

onMounted(async () => {
  await Promise.all([fetchDrafts(), fetchChannel()])
})

const channelReady = computed(() => channel.value?.status === 'connected' && channel.value.bot_can_post_messages)
const destinationName = computed(() => channel.value?.title || channel.value?.username || channel.value?.chat_id || 'No channel connected')
const destinationHandle = computed(() => {
  if (!channel.value) {
    return 'Connect a channel before publishing'
  }

  return channel.value.username ? `@${channel.value.username}` : channel.value.chat_id
})

const onFileChange = (event: Event) => {
  const input = event.target as HTMLInputElement
  image.value = input.files?.[0] || null
  createError.value = null
}

const submit = async () => {
  if (!prompt.value.trim()) {
    return
  }

  creating.value = true
  createError.value = null

  try {
    const draft = await createDraft(prompt.value.trim(), image.value)
    prompt.value = ''
    image.value = null
    await navigateTo(`/dashboard/drafts/${draft.id}`)
  } catch (exception: any) {
    createError.value = getDraftErrorMessage(exception, 'Could not create draft.')
  } finally {
    creating.value = false
  }
}
</script>

<template>
  <section class="page">
    <div class="page-head">
      <div>
        <p class="eyebrow">Draft studio</p>
        <h1>Create posts for Telegram</h1>
        <p class="muted" style="margin: 8px 0 0">
          Write from the web dashboard or keep using the Telegram bot. Every post still goes through variants, approval, and publish logs.
        </p>
      </div>
      <div class="page-actions">
        <button class="btn icon" type="button" title="Refresh drafts" @click="Promise.all([fetchDrafts(), fetchChannel()])">
          <RefreshCw :size="17" aria-hidden="true" />
        </button>
      </div>
    </div>

    <div class="content-grid">
      <section class="panel">
        <div class="panel-head">
          <div class="button-row">
            <span class="metric-icon">
              <Plus :size="16" aria-hidden="true" />
            </span>
            <h2>New web draft</h2>
          </div>
          <span class="status-badge" :class="channelReady ? 'status-good' : 'status-active'">
            <ShieldCheck :size="14" aria-hidden="true" />
            {{ channelReady ? 'channel ready' : 'publish later' }}
          </span>
        </div>
        <div class="panel-body">
          <form class="composer" @submit.prevent="submit">
            <label class="field">
              <span>Prompt</span>
              <textarea
                v-model="prompt"
                class="textarea"
                placeholder="Write the post idea, product details, audience, tone, and call to action."
              />
            </label>

            <label class="dropzone">
              <span class="button-row strong-muted">
                <ImagePlus :size="17" aria-hidden="true" />
                Optional image
              </span>
              <span class="muted">Attach JPG, PNG, or WebP under 10 MB. Gemini can use it as visual context.</span>
              <input class="file-input" type="file" accept="image/png,image/jpeg,image/webp" @change="onFileChange" />
              <span v-if="imageName" class="status-badge status-violet">{{ imageName }}</span>
              <span v-if="imageMeta" class="muted">{{ imageMeta }}</span>
            </label>

            <div class="destination-strip">
              <div class="destination-main">
                <span class="metric-icon">
                  <Radio :size="16" aria-hidden="true" />
                </span>
                <div class="truncate">
                  <span class="destination-title">Publishes to {{ destinationName }}</span>
                  <span class="destination-meta">{{ destinationHandle }}</span>
                </div>
              </div>
              <span class="status-badge" :class="channelReady ? 'status-good' : 'status-active'">
                after approval
              </span>
            </div>

            <p v-if="createError" class="error">{{ createError }}</p>

            <div class="composer-actions">
              <span class="muted">Nothing publishes until you select and approve a variant.</span>
              <button class="btn primary" type="submit" :disabled="creating || !prompt.trim()">
                <Send :size="16" aria-hidden="true" />
                Generate variants
              </button>
            </div>
          </form>
        </div>
      </section>

      <div class="stack-lg">
        <TelegramChannelStatus :channel="channel" />

        <section class="panel">
          <div class="panel-head">
            <h2>Draft inbox</h2>
            <span v-if="loading" class="muted">Loading</span>
          </div>
          <div class="panel-body">
            <p v-if="error" class="error">{{ error }}</p>
            <div v-else-if="drafts.length" class="card-list">
              <DraftCard v-for="draft in drafts" :key="draft.id" :draft="draft" />
            </div>
            <div v-else class="empty">No drafts yet. Create a prompt here or message the Telegram bot.</div>
          </div>
        </section>
      </div>
    </div>
  </section>
</template>
