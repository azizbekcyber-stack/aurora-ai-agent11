<script setup lang="ts">
const { drafts, loading: draftsLoading, error: draftsError, fetchDrafts } = useDrafts()
const { channel, fetchChannel } = useTelegramChannel()

onMounted(async () => {
  await Promise.all([fetchDrafts(), fetchChannel()])
})

const publishedCount = computed(() => drafts.value.filter((draft) => draft.status === 'published').length)
const activeCount = computed(() => drafts.value.filter((draft) => ['generating', 'generated', 'selected', 'approved', 'publishing'].includes(draft.status)).length)
const failedCount = computed(() => drafts.value.filter((draft) => draft.status === 'failed').length)
const recentDrafts = computed(() => drafts.value.slice(0, 5))
</script>

<template>
  <section>
    <div class="page-head">
      <div>
        <p class="eyebrow">Telegram-first publishing</p>
        <h1>Dashboard</h1>
      </div>
      <NuxtLink class="btn primary" to="/dashboard/drafts">Drafts</NuxtLink>
    </div>

    <div class="grid two">
      <div class="stack">
        <section class="panel">
          <div class="panel-head">
            <h2>Status</h2>
          </div>
          <div class="panel-body">
            <div class="metric-grid">
              <div class="metric">
                <span class="muted">Active</span>
                <strong>{{ activeCount }}</strong>
              </div>
              <div class="metric">
                <span class="muted">Published</span>
                <strong>{{ publishedCount }}</strong>
              </div>
              <div class="metric">
                <span class="muted">Failed</span>
                <strong>{{ failedCount }}</strong>
              </div>
            </div>
          </div>
        </section>

        <section class="panel">
          <div class="panel-head">
            <h2>Recent Drafts</h2>
            <span v-if="draftsLoading" class="muted">Loading</span>
          </div>
          <div class="panel-body">
            <p v-if="draftsError" class="error">{{ draftsError }}</p>
            <div v-else-if="recentDrafts.length" class="card-list">
              <DraftCard v-for="draft in recentDrafts" :key="draft.id" :draft="draft" />
            </div>
            <div v-else class="empty">No drafts yet.</div>
          </div>
        </section>
      </div>

      <TelegramChannelStatus :channel="channel" />
    </div>
  </section>
</template>
