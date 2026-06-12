<script setup lang="ts">
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
  <section>
    <div class="page-head">
      <div>
        <p class="eyebrow">Generation defaults</p>
        <h1>Brand Profile</h1>
      </div>
    </div>

    <section class="panel">
      <div class="panel-head">
        <h2>Settings</h2>
        <span v-if="loading" class="muted">Loading</span>
      </div>
      <div class="panel-body">
        <p v-if="error" class="error">{{ error }}</p>
        <BrandProfileForm :profile="profile" :saving="saving" @save="save" />
        <p v-if="saved" class="status-badge status-good" style="margin-top: 12px">Saved</p>
        <p v-if="saveError" class="error">{{ saveError }}</p>
      </div>
    </section>
  </section>
</template>
