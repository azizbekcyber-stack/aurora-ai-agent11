<script setup lang="ts">
import { Check, RefreshCw, Send, X } from '@lucide/vue'

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

onMounted(() => fetchDraft(route.params.id as string))

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
</script>

<template>
  <section>
    <div class="page-head">
      <div>
        <p class="eyebrow">Draft detail</p>
        <h1>Draft #{{ route.params.id }}</h1>
      </div>
      <NuxtLink class="btn" to="/dashboard/drafts">Back</NuxtLink>
    </div>

    <p v-if="loading" class="muted">Loading</p>
    <p v-if="error" class="error">{{ error }}</p>

    <div v-if="currentDraft" class="grid two">
      <div class="stack">
        <section class="panel">
          <div class="panel-head">
            <h2>Prompt</h2>
            <DraftStatusBadge :status="currentDraft.status" />
          </div>
          <div class="panel-body stack">
            <p class="prewrap" style="margin: 0">{{ currentDraft.prompt }}</p>
            <div class="button-row">
              <button
                class="btn primary"
                type="button"
                :disabled="acting || currentDraft.status !== 'selected'"
                @click="run(() => approveDraft(currentDraft!.id))"
              >
                <Check :size="16" aria-hidden="true" />
                Approve
              </button>
              <button
                class="btn primary"
                type="button"
                :disabled="acting || currentDraft.status !== 'approved'"
                @click="run(() => publishDraft(currentDraft!.id))"
              >
                <Send :size="16" aria-hidden="true" />
                Publish
              </button>
              <button
                class="btn danger"
                type="button"
                :disabled="acting || !['generated', 'selected', 'approved'].includes(currentDraft.status)"
                @click="run(() => cancelDraft(currentDraft!.id))"
              >
                <X :size="16" aria-hidden="true" />
                Cancel
              </button>
              <button class="btn icon" type="button" title="Refresh draft" @click="fetchDraft(route.params.id as string)">
                <RefreshCw :size="16" aria-hidden="true" />
              </button>
            </div>
            <p v-if="actionError" class="error">{{ actionError }}</p>
          </div>
        </section>

        <section class="panel">
          <div class="panel-head">
            <h2>Variants</h2>
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
            <div v-else class="empty">No variants stored.</div>
          </div>
        </section>
      </div>

      <section class="panel">
        <div class="panel-head">
          <h2>Publish Logs</h2>
        </div>
        <div class="panel-body">
          <div v-if="currentDraft.publish_logs?.length" class="stack">
            <article v-for="log in currentDraft.publish_logs" :key="log.id" class="draft-card">
              <div class="card-row">
                <strong>{{ log.platform }}</strong>
                <span class="status-badge" :class="log.status === 'success' ? 'status-good' : 'status-bad'">
                  {{ log.status }}
                </span>
              </div>
              <p class="muted" style="margin: 0">{{ log.publish_strategy || 'pending' }}</p>
              <p v-if="log.error_message" class="error" style="margin: 0">{{ log.error_message }}</p>
            </article>
          </div>
          <div v-else class="empty">No publish logs yet.</div>
        </div>
      </section>
    </div>
  </section>
</template>
