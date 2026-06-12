<script setup lang="ts">
import { RefreshCw, Settings, Sparkles } from '@lucide/vue'

const { profile, loading, error, fetchProfile, saveProfile } = useBrandProfile()
const saving = ref(false)
const saved = ref(false)
const saveError = ref<string | null>(null)

onMounted(fetchProfile)

const save = async (payload: any) => {
  saving.value = true
  saved.value = false
  saveError.value = null

  try {
    await saveProfile(payload)
    saved.value = true
  } catch (exception: any) {
    saveError.value = exception?.data?.message || exception?.message || 'Could not save brand profile.'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <section class="page">
    <div class="page-head">
      <div>
        <p class="eyebrow">AI settings</p>
        <h1>Brand voice</h1>
        <p class="muted" style="margin: 8px 0 0">
          These defaults guide Gemini when it creates post variants from web drafts and Telegram bot prompts.
        </p>
      </div>
      <button class="btn icon" type="button" title="Refresh profile" @click="fetchProfile">
        <RefreshCw :size="17" aria-hidden="true" />
      </button>
    </div>

    <div class="content-grid">
      <section class="panel">
        <div class="panel-head">
          <div class="button-row">
            <span class="metric-icon">
              <Settings :size="16" aria-hidden="true" />
            </span>
            <h2>Generation defaults</h2>
          </div>
          <span v-if="loading" class="muted">Loading</span>
        </div>
        <div class="panel-body">
          <p v-if="error" class="error">{{ error }}</p>
          <BrandProfileForm :profile="profile" :saving="saving" @save="save" />
          <p v-if="saved" class="status-badge status-good" style="margin-top: 12px">Saved</p>
          <p v-if="saveError" class="error">{{ saveError }}</p>
        </div>
      </section>

      <section class="panel">
        <div class="panel-head">
          <div class="button-row">
            <span class="metric-icon">
              <Sparkles :size="16" aria-hidden="true" />
            </span>
          <h2>How it shapes posts</h2>
          </div>
        </div>
        <div class="panel-body">
          <div class="timeline">
            <div class="timeline-row">
              <span class="timeline-dot">1</span>
              <div>
                <strong>Language and tone</strong>
                <p class="muted" style="margin: 3px 0 0">Set the default writing language, voice, and audience.</p>
              </div>
            </div>
            <div class="timeline-row">
              <span class="timeline-dot">2</span>
              <div>
                <strong>Emoji and hashtags</strong>
                <p class="muted" style="margin: 3px 0 0">Control how expressive or minimal the generated posts should be.</p>
              </div>
            </div>
            <div class="timeline-row">
              <span class="timeline-dot">3</span>
              <div>
                <strong>Banned words</strong>
                <p class="muted" style="margin: 3px 0 0">Keep unwanted phrases out of generated Telegram copy.</p>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </section>
</template>
