<script setup lang="ts">
import { KeyRound, LogIn, UserRound } from '@lucide/vue'

const { userId, dashboardToken } = useApi()
const draftUserId = ref(userId.value || '')
const accessToken = ref(dashboardToken.value || '')

const save = async () => {
  userId.value = draftUserId.value.trim() || null
  dashboardToken.value = accessToken.value.trim() || null
  await navigateTo('/dashboard')
}
</script>

<template>
  <section class="page">
    <div class="page-head">
      <div>
        <p class="eyebrow">Workspace access</p>
        <h1>Open Aurora dashboard</h1>
        <p class="muted" style="margin: 8px 0 0">
          Local development can use only a user ID. Production should also use the dashboard access token from your hosting environment.
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

            <label class="field">
              <span>Dashboard access token</span>
              <input v-model="accessToken" class="input" type="password" placeholder="Required when AURORA_DASHBOARD_TOKEN is set" />
            </label>

            <div class="info-strip">
              <div class="destination-main">
                <span class="metric-icon">
                  <KeyRound :size="16" aria-hidden="true" />
                </span>
                <div>
                  <span class="destination-title">Production protection</span>
                  <span class="destination-meta">Use the same value as AURORA_DASHBOARD_TOKEN. Keep it private.</span>
                </div>
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
          <h2>Local testing</h2>
        </div>
        <div class="panel-body">
          <div class="timeline">
            <div class="timeline-row">
              <span class="timeline-dot">1</span>
              <div>
                <strong>Telegram user drafts</strong>
                <p class="muted" style="margin: 3px 0 0">Use <span class="kbd">2</span> to view the Telegram user you connected earlier.</p>
              </div>
            </div>
            <div class="timeline-row">
              <span class="timeline-dot">2</span>
              <div>
                <strong>Production token</strong>
                <p class="muted" style="margin: 3px 0 0">When deployed, set AURORA_DASHBOARD_TOKEN on the backend and enter it here.</p>
              </div>
            </div>
            <div class="timeline-row">
              <span class="timeline-dot">3</span>
              <div>
                <strong>Future auth</strong>
                <p class="muted" style="margin: 3px 0 0">This token gate is a simple MVP protection before full account login.</p>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </section>
</template>
