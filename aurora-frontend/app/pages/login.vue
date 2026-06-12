<script setup lang="ts">
import { LogIn } from '@lucide/vue'

const { userId } = useApi()
const draftUserId = ref(userId.value || '')

const save = async () => {
  userId.value = draftUserId.value.trim() || null
  await navigateTo('/dashboard')
}
</script>

<template>
  <section>
    <div class="page-head">
      <div>
        <p class="eyebrow">Access</p>
        <h1>Dashboard User</h1>
      </div>
    </div>

    <div class="panel" style="max-width: 560px">
      <div class="panel-body">
        <form class="stack" @submit.prevent="save">
          <label class="field">
            <span>User ID</span>
            <input v-model="draftUserId" class="input" placeholder="Leave empty for demo user" />
          </label>
          <div class="button-row">
            <button class="btn primary" type="submit">
              <LogIn :size="16" aria-hidden="true" />
              Continue
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>
