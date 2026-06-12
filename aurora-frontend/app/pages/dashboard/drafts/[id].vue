<script setup lang="ts">
import { Bot, Check, Clock, FileText, Radio, RefreshCw, Send, X } from '@lucide/vue'

const route = useRoute()
const {
  currentDraft,
  loading,
  error,
  fetchDraft,
  selectVariant,
  approveDraft,
  publishDraft,
  cancelDraft
} = useDrafts()

const actionError = ref<string | null>(null)
const acting = ref(false)

onMounted(async () => {
  await fetchDraft(route.params.id as string)
})

const run = async (action: () => Promise<void>) => {
  acting.value = true
  actionError.value = null

  try {
    await action()
  } catch (exception: any) {
    actionError.value = exception?.data?.message || exception?.message || 'Action failed.'
  } finally {
    acting.value = false
  }
}

const selectedVariantId = computed(() => currentDraft.value?.selected_variant_id || null)
const canApprove = computed(() => currentDraft.value?.status === 'selected')
const canPublish = computed(() => currentDraft.value?.status === 'approved')
const canCancel = computed(() => currentDraft.value && ['generated', 'selected', 'approved'].includes(currentDraft.value.status))
const waitingForAi = computed(() => currentDraft.value && ['draft', 'generating'].includes(currentDraft.value.status))
const destination = computed(() => currentDraft.value?.telegram_channel || null)
const destinationReady = computed(() => Boolean(destination.value?.status === 'connected' && destination.value.bot_can_post_messages))
const destinationName = computed(() => destination.value?.title || destination.value?.username || destination.value?.chat_id || 'No channel connected')
const destinationHandle = computed(() => {
  if (!destination.value) {
    return 'Connect a channel before publishing'
  }

  return destination.value.username ? `@${destination.value.username}` : destination.value.chat_id
})

const formatDate = (value?: string | null) => {
  if (!value) {
    return 'Not yet'
  }

  return new Intl.DateTimeFormat('en', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(new Date(value))
}
</script>

<template>
  <section class="page">
    <div class="page-head">
      <div>
        <p class="eyebrow">Draft review</p>
        <h1>Draft #{{ route.params.id }}</h1>
        <p class="muted" style="margin: 8px 0 0">
          Select one AI variant, approve it, then publish to the connected Telegram channel.
        </p>
      </div>
      <div class="page-actions">
        <NuxtLink class="btn" to="/dashboard/drafts">Back</NuxtLink>
        <button class="btn icon" type="button" title="Refresh draft" @click="fetchDraft(route.params.id as string)">
          <RefreshCw :size="16" aria-hidden="true" />
        </button>
      </div>
    </div>

    <p v-if="loading" class="muted">Loading draft...</p>
    <p v-if="error" class="error">{{ error }}</p>

    <div v-if="currentDraft" class="content-grid">
      <div class="stack-lg">
        <section class="panel">
          <div class="panel-head">
            <div class="button-row">
              <span class="metric-icon">
                <FileText :size="16" aria-hidden="true" />
              </span>
              <h2>Prompt and controls</h2>
            </div>
            <DraftStatusBadge :status="currentDraft.status" />
          </div>
          <div class="panel-body stack">
            <p class="prewrap" style="margin: 0">{{ currentDraft.prompt }}</p>

            <div class="button-row">
              <span class="source-badge">
                <Bot v-if="currentDraft.source === 'telegram'" :size="14" aria-hidden="true" />
                <FileText v-else :size="14" aria-hidden="true" />
                {{ currentDraft.source }}
              </span>
              <span v-if="currentDraft.image_path" class="status-badge status-violet">image attached</span>
              <span class="muted">Created {{ formatDate(currentDraft.created_at) }}</span>
            </div>

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
              <span class="status-badge" :class="destinationReady ? 'status-good' : 'status-active'">
                {{ destinationReady ? 'channel ready' : 'channel needed' }}
              </span>
            </div>

            <div class="button-row">
              <button
                class="btn primary"
                type="button"
                :disabled="acting || !canApprove"
                @click="run(() => approveDraft(currentDraft!.id))"
              >
                <Check :size="16" aria-hidden="true" />
                Approve selected
              </button>
              <button
                class="btn dark"
                type="button"
                :disabled="acting || !canPublish"
                @click="run(() => publishDraft(currentDraft!.id))"
              >
                <Send :size="16" aria-hidden="true" />
                Publish
              </button>
              <button
                class="btn danger"
                type="button"
                :disabled="acting || !canCancel"
                @click="run(() => cancelDraft(currentDraft!.id))"
              >
                <X :size="16" aria-hidden="true" />
                Cancel
              </button>
            </div>

            <p v-if="waitingForAi" class="muted">AI is still preparing variants. Refresh in a few seconds.</p>
            <p v-if="actionError" class="error">{{ actionError }}</p>
          </div>
        </section>

        <section class="panel">
          <div class="panel-head">
            <h2>AI variants</h2>
            <span class="muted">{{ currentDraft.variants?.length || 0 }} options</span>
          </div>
          <div class="panel-body">
            <div v-if="currentDraft.variants?.length" class="card-list">
              <VariantPreview
                v-for="variant in currentDraft.variants"
                :key="variant.id"
                :variant="variant"
                :selected="selectedVariantId === variant.id"
                @select="(variantId) => run(() => selectVariant(currentDraft!.id, variantId))"
              />
            </div>
            <div v-else class="empty">
              Variants have not arrived yet. Keep the queue worker running and refresh this draft.
            </div>
          </div>
        </section>
      </div>

      <div class="stack-lg">
        <section class="panel">
          <div class="panel-head">
            <div class="button-row">
              <span class="metric-icon">
                <Clock :size="16" aria-hidden="true" />
              </span>
              <h2>Workflow</h2>
            </div>
          </div>
          <div class="panel-body">
            <div class="timeline">
              <div class="timeline-row">
                <span class="timeline-dot"><FileText :size="15" aria-hidden="true" /></span>
                <div>
                  <strong>Generate</strong>
                  <p class="muted" style="margin: 3px 0 0">Aurora creates three Telegram-ready variants.</p>
                </div>
              </div>
              <div class="timeline-row">
                <span class="timeline-dot"><Check :size="15" aria-hidden="true" /></span>
                <div>
                  <strong>Select and approve</strong>
                  <p class="muted" style="margin: 3px 0 0">Choose one variant, then approve it for publishing.</p>
                </div>
              </div>
              <div class="timeline-row">
                <span class="timeline-dot"><Send :size="15" aria-hidden="true" /></span>
                <div>
                  <strong>Publish</strong>
                  <p class="muted" style="margin: 3px 0 0">The approved post is sent to {{ destinationName }}.</p>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="panel">
          <div class="panel-head">
            <h2>Publish logs</h2>
          </div>
          <div class="panel-body">
            <div v-if="currentDraft.publish_logs?.length" class="stack">
              <article v-for="log in currentDraft.publish_logs" :key="log.id" class="activity-item">
                <div class="card-row">
                  <strong>{{ log.platform }}</strong>
                  <span class="status-badge" :class="log.status === 'success' ? 'status-good' : 'status-bad'">
                    {{ log.status }}
                  </span>
                </div>
                <p class="muted" style="margin: 0">{{ log.publish_strategy || 'pending' }} · {{ formatDate(log.published_at || log.created_at) }}</p>
                <p v-if="log.error_message" class="error" style="margin: 0">{{ log.error_message }}</p>
              </article>
            </div>
            <div v-else class="empty">No publish logs yet.</div>
          </div>
        </section>
      </div>
    </div>
  </section>
</template>
