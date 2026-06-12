<script setup lang="ts">
import { LogIn, UserRound } from '@lucide/vue'

const { userId } = useApi()
const draftUserId = ref(userId.value || '')

const save = async () => {
  userId.value = draftUserId.value.trim() || null
  await navigateTo('/dashboard')
}
</script>

<template>
  <section class="page">
    <div class="page-head">
      <div>
        <p class="eyebrow">Local access</p>
        <h1>Choose workspace user</h1>
        <p class="muted" style="margin: 8px 0 0">
          Local MVP uses a simple user ID selector so the browser can show the same drafts created from Telegram.
        </p>
      </div>
    </div>

    <div class="content-grid">
      <section class="panel">
        <div class="panel-head">
          <div class="button-row">
            <span class="metric-icon">
              <UserRound :size="16" aria-hidden="true" />
            </span>
            <h2>Access</h2>
          </div>
        </div>
        <div class="panel-body">
          <form class="stack" @submit.prevent="save">
            <label class="field">
              <span>User ID</span>
              <input v-model="draftUserId" class="input" placeholder="Leave empty for demo user" />
            </label>
            <div class="info-strip">
              <div>
                <span class="destination-title">Current local test</span>
                <span class="destination-meta">Use <span class="kbd">2</span> to view the Telegram user drafts you connected earlier.</span>
              </div>
            </div>
            <div class="button-row">
              <button class="btn primary" type="submit">
                <LogIn :size="16" aria-hidden="true" />
                Continue
              </button>
            </div>
          </form>
        </div>
      </section>

      <section class="panel">
        <div class="panel-head">
          <h2>How local users work</h2>
        </div>
        <div class="panel-body">
          <div class="timeline">
            <div class="timeline-row">
              <span class="timeline-dot">1</span>
              <div>
                <strong>Telegram creates a user</strong>
                <p class="muted" style="margin: 3px 0 0">When you message the bot, backend stores a Telegram user and drafts.</p>
              </div>
            </div>
            <div class="timeline-row">
              <span class="timeline-dot">2</span>
              <div>
                <strong>Browser selects that user</strong>
                <p class="muted" style="margin: 3px 0 0">The dashboard sends the selected user ID to the API.</p>
              </div>
            </div>
            <div class="timeline-row">
              <span class="timeline-dot">3</span>
              <div>
                <strong>Production will use auth</strong>
                <p class="muted" style="margin: 3px 0 0">This MVP selector will be replaced by real login later.</p>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </section>
</template>
