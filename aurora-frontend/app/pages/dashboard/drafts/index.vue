<script setup lang="ts">
import { Plus, RefreshCw } from '@lucide/vue'

const { drafts, loading, error, fetchDrafts, createDraft } = useDrafts()
const prompt = ref('')
const creating = ref(false)
const createError = ref<string | null>(null)

onMounted(fetchDrafts)

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
  <section>
    <div class="page-head">
      <div>
        <p class="eyebrow">Post history</p>
        <h1>Drafts</h1>
      </div>
      <button class="btn icon" type="button" title="Refresh drafts" @click="fetchDrafts">
        <RefreshCw :size="17" aria-hidden="true" />
      </button>
    </div>

    <div class="grid two">
      <section class="panel">
        <div class="panel-head">
          <h2>All Drafts</h2>
          <span v-if="loading" class="muted">Loading</span>
        </div>
        <div class="panel-body">
          <p v-if="error" class="error">{{ error }}</p>
          <div v-else-if="drafts.length" class="card-list">
            <DraftCard v-for="draft in drafts" :key="draft.id" :draft="draft" />
          </div>
          <div v-else class="empty">No drafts yet.</div>
        </div>
      </section>

      <section class="panel">
        <div class="panel-head">
          <h2>New Web Draft</h2>
        </div>
        <div class="panel-body">
          <form class="stack" @submit.prevent="submit">
            <label class="field">
              <span>Prompt</span>
              <textarea v-model="prompt" class="textarea" />
            </label>
            <p v-if="createError" class="error">{{ createError }}</p>
            <button class="btn primary" type="submit" :disabled="creating">
              <Plus :size="16" aria-hidden="true" />
              Create
            </button>
          </form>
        </div>
      </section>
    </div>
  </section>
</template>
