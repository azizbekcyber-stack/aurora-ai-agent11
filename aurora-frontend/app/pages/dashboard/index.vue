<script setup lang="ts">
import { Bot, CheckCircle, Clock, FileText, Radio, RefreshCw, Send, Sparkles, TriangleAlert } from '@lucide/vue'

const { drafts, loading: draftsLoading, error: draftsError, fetchDrafts, createDraft } = useDrafts()
const { channel, fetchChannel } = useTelegramChannel()

const prompt = ref('')
const creating = ref(false)
const createError = ref<string | null>(null)

onMounted(async () => {
  await Promise.all([fetchDrafts(), fetchChannel()])
})

const activeCount = computed(() => drafts.value.filter((draft) => ['generating', 'generated', 'selected', 'approved', 'publishing'].includes(draft.status)).length)
const publishedCount = computed(() => drafts.value.filter((draft) => draft.status === 'published').length)
const failedCount = computed(() => drafts.value.filter((draft) => draft.status === 'failed').length)
const webCount = computed(() => drafts.value.filter((draft) => draft.source === 'web').length)
const recentDrafts = computed(() => drafts.value.slice(0, 5))
const channelReady = computed(() => channel.value?.status === 'connected' && channel.value.bot_can_post_messages)
const destinationName = computed(() => channel.value?.title || channel.value?.username || channel.value?.chat_id || 'No channel connected')
const destinationHandle = computed(() => {
  if (!channel.value) {
    return 'Connect a channel before publishing'
  }

  return channel.value.username ? `@${channel.value.username}` : channel.value.chat_id
})

const submit = async () => {
  if (!prompt.value.trim()) {
    return
  }

  creating.value = true
  createError.value = null

  try {
    const draft = await createDraft(prompt.value.trim())
    prompt.value = ''
    await navigateTo(`/dashboard/drafts/${draft.id}`)
  } catch (exception: any) {
    createError.value = exception?.data?.message || exception?.message || 'Could not create draft.'
  } finally {
    creating.value = false
  }
}
</script>

<template>
  <section class="page">
    <div class="page-head">
      <div>
        <p class="eyebrow">Publishing workspace</p>
        <h1>Create, approve, publish</h1>
        <p class="muted" style="margin: 8px 0 0">
          Aurora keeps web drafts and Telegram bot prompts in one approval flow before anything reaches your channel.
        </p>
      </div>
      <div class="page-actions">
        <button class="btn icon" type="button" title="Refresh" @click="Promise.all([fetchDrafts(), fetchChannel()])">
          <RefreshCw :size="17" aria-hidden="true" />
        </button>
        <NuxtLink class="btn primary" to="/dashboard/drafts">
          <FileText :size="16" aria-hidden="true" />
          Draft studio
        </NuxtLink>
      </div>
    </div>

    <div class="metric-grid">
      <div class="metric">
        <span class="metric-icon amber"><Clock :size="17" aria-hidden="true" /></span>
        <span class="muted">Active</span>
        <strong>{{ activeCount }}</strong>
      </div>
      <div class="metric">
        <span class="metric-icon"><CheckCircle :size="17" aria-hidden="true" /></span>
        <span class="muted">Published</span>
        <strong>{{ publishedCount }}</strong>
      </div>
      <div class="metric">
        <span class="metric-icon rose"><TriangleAlert :size="17" aria-hidden="true" /></span>
        <span class="muted">Failed</span>
        <strong>{{ failedCount }}</strong>
      </div>
      <div class="metric">
        <span class="metric-icon blue"><Sparkles :size="17" aria-hidden="true" /></span>
        <span class="muted">Web drafts</span>
        <strong>{{ webCount }}</strong>
      </div>
    </div>

    <div class="content-grid">
      <div class="stack-lg">
        <section class="panel">
          <div class="panel-head">
            <div class="button-row">
              <span class="metric-icon">
                <Sparkles :size="16" aria-hidden="true" />
              </span>
              <h2>Quick web draft</h2>
            </div>
            <span class="status-badge" :class="channelReady ? 'status-good' : 'status-active'">
              {{ channelReady ? 'publish ready' : 'connect channel' }}
            </span>
          </div>
          <div class="panel-body">
            <form class="composer" @submit.prevent="submit">
              <label class="field">
                <span>Post idea</span>
                <textarea
                  v-model="prompt"
                  class="textarea"
                  placeholder="Example: Create a concise Telegram post about our weekend launch with a warm call to action."
                />
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
                <span class="muted">AI creates 3 options before anything can be published.</span>
                <button class="btn primary" type="submit" :disabled="creating || !prompt.trim()">
                  <Send :size="16" aria-hidden="true" />
                  Create options
                </button>
              </div>
            </form>
          </div>
        </section>

        <section class="panel">
          <div class="panel-head">
            <h2>Recent drafts</h2>
            <span v-if="draftsLoading" class="muted">Loading</span>
          </div>
          <div class="panel-body">
            <p v-if="draftsError" class="error">{{ draftsError }}</p>
            <div v-else-if="recentDrafts.length" class="card-list">
              <DraftCard v-for="draft in recentDrafts" :key="draft.id" :draft="draft" />
            </div>
            <div v-else class="empty">No drafts yet. Create one here or send a prompt to the Telegram bot.</div>
          </div>
        </section>
      </div>

      <div class="stack-lg">
        <TelegramChannelStatus :channel="channel" />

        <section class="panel">
          <div class="panel-head">
            <div class="button-row">
              <span class="metric-icon">
                <Bot :size="16" aria-hidden="true" />
              </span>
              <h2>Creation paths</h2>
            </div>
          </div>
          <div class="panel-body">
            <div class="timeline">
              <div class="timeline-row">
                <span class="timeline-dot"><FileText :size="15" aria-hidden="true" /></span>
                <div>
                  <strong>Web dashboard</strong>
                  <p class="muted" style="margin: 3px 0 0">Write a prompt, attach an image if needed, then review variants.</p>
                </div>
              </div>
              <div class="timeline-row">
                <span class="timeline-dot"><Bot :size="15" aria-hidden="true" /></span>
                <div>
                  <strong>Telegram bot</strong>
                  <p class="muted" style="margin: 3px 0 0">Send a prompt or image with caption and approve with inline buttons.</p>
                </div>
              </div>
              <div class="timeline-row">
                <span class="timeline-dot"><CheckCircle :size="15" aria-hidden="true" /></span>
                <div>
                  <strong>Shared channel</strong>
                  <p class="muted" style="margin: 3px 0 0">Both paths publish to {{ destinationName }} after approval.</p>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
  </section>
</template>
