<script setup lang="ts">
import { Bot, CheckCircle, KeyRound, Link2, Loader2, LogIn, Radio, RefreshCw, ShieldCheck } from '@lucide/vue'
import type { TelegramAuthStart, TelegramAuthStatus } from '~/composables/useApi'

const { request, userId, dashboardToken, sessionToken } = useApi()

const auth = ref<TelegramAuthStart | null>(null)
const status = ref<'idle' | 'starting' | 'waiting' | 'connected' | 'error'>('idle')
const error = ref<string | null>(null)
const pollTimer = ref<ReturnType<typeof setInterval> | null>(null)

const isPolling = computed(() => status.value === 'waiting')

const stopPolling = () => {
  if (pollTimer.value) {
    clearInterval(pollTimer.value)
    pollTimer.value = null
  }
}

const checkStatus = async () => {
  if (!auth.value?.code) {
    return
  }

  try {
    const result = await request<TelegramAuthStatus>('/auth/telegram/status', {
      method: 'POST',
      body: { code: auth.value.code }
    })

    if (!result.authenticated || !result.session_token || !result.user) {
      return
    }

    sessionToken.value = result.session_token
    userId.value = String(result.user.id)
    dashboardToken.value = null
    status.value = 'connected'
    stopPolling()
    await navigateTo(result.user.channel ? '/dashboard' : '/dashboard/channels')
  } catch (exception: any) {
    error.value = exception?.data?.message || exception?.message || 'Could not verify Telegram login.'
    status.value = 'error'
    stopPolling()
  }
}

const startLogin = async () => {
  status.value = 'starting'
  error.value = null
  stopPolling()

  try {
    auth.value = await request<TelegramAuthStart>('/auth/telegram/start', {
      method: 'POST'
    })

    status.value = 'waiting'

    if (auth.value.telegram_url) {
      window.open(auth.value.telegram_url, '_blank', 'noopener,noreferrer')
    }

    pollTimer.value = setInterval(checkStatus, 2500)
  } catch (exception: any) {
    error.value = exception?.data?.message || exception?.message || 'Could not start Telegram login.'
    status.value = 'error'
  }
}

onBeforeUnmount(stopPolling)
</script>

<template>
  <section class="page">
    <div class="page-head">
      <div>
        <p class="eyebrow">Telegram authentication</p>
        <h1>Connect Aurora to your Telegram</h1>
        <p class="muted" style="margin: 8px 0 0">
          Start from the web, confirm inside Telegram, connect your channel, then create and publish from either place.
        </p>
      </div>
      <button class="btn primary" type="button" :disabled="status === 'starting'" @click="startLogin">
        <Loader2 v-if="status === 'starting'" :size="16" aria-hidden="true" />
        <LogIn v-else :size="16" aria-hidden="true" />
        Continue with Telegram
      </button>
    </div>

    <div class="content-grid">
      <section class="panel">
        <div class="panel-head">
          <div class="button-row">
            <span class="metric-icon">
              <Bot :size="16" aria-hidden="true" />
            </span>
            <h2>Sign in flow</h2>
          </div>
          <span class="status-badge" :class="status === 'connected' ? 'status-good' : isPolling ? 'status-active' : 'status-neutral'">
            {{ status === 'connected' ? 'connected' : isPolling ? 'waiting' : 'ready' }}
          </span>
        </div>
        <div class="panel-body">
          <div class="timeline">
            <div class="timeline-row">
              <span class="timeline-dot"><LogIn :size="15" aria-hidden="true" /></span>
              <div>
                <strong>Open Telegram</strong>
                <p class="muted" style="margin: 3px 0 0">Aurora creates a short secure login link and opens the bot.</p>
              </div>
            </div>
            <div class="timeline-row">
              <span class="timeline-dot"><CheckCircle :size="15" aria-hidden="true" /></span>
              <div>
                <strong>Press Start in the bot</strong>
                <p class="muted" style="margin: 3px 0 0">The bot confirms your Telegram account and links it to this dashboard.</p>
              </div>
            </div>
            <div class="timeline-row">
              <span class="timeline-dot"><Radio :size="15" aria-hidden="true" /></span>
              <div>
                <strong>Connect a channel</strong>
                <p class="muted" style="margin: 3px 0 0">Use /connect_channel in Telegram or connect by username in the dashboard.</p>
              </div>
            </div>
          </div>

          <div v-if="auth?.telegram_url" class="info-strip" style="margin-top: 14px">
            <div class="destination-main">
              <span class="metric-icon blue">
                <Link2 :size="16" aria-hidden="true" />
              </span>
              <div>
                <span class="destination-title">Telegram link is ready</span>
                <span class="destination-meta">After pressing Start, this page will continue automatically.</span>
              </div>
            </div>
            <a class="btn" :href="auth.telegram_url" target="_blank" rel="noreferrer">
              <Bot :size="16" aria-hidden="true" />
              Open bot
            </a>
          </div>

          <p v-if="error" class="error">{{ error }}</p>

          <div class="button-row" style="margin-top: 14px">
            <button class="btn primary" type="button" :disabled="status === 'starting'" @click="startLogin">
              <RefreshCw :size="16" aria-hidden="true" />
              {{ auth ? 'Create new login link' : 'Continue with Telegram' }}
            </button>
            <button v-if="isPolling" class="btn" type="button" @click="checkStatus">
              <ShieldCheck :size="16" aria-hidden="true" />
              I pressed Start
            </button>
          </div>
        </div>
      </section>

      <section class="panel">
        <div class="panel-head">
          <div class="button-row">
            <span class="metric-icon amber">
              <KeyRound :size="16" aria-hidden="true" />
            </span>
            <h2>What changes</h2>
          </div>
        </div>
        <div class="panel-body">
          <div class="stack">
            <div class="quick-card">
              <strong>No manual user ID</strong>
              <p class="muted" style="margin: 0">Aurora now gets your account from Telegram login.</p>
            </div>
            <div class="quick-card">
              <strong>One shared workspace</strong>
              <p class="muted" style="margin: 0">Bot drafts and web drafts stay under the same user and channel.</p>
            </div>
            <div class="quick-card">
              <strong>Approval stays controlled</strong>
              <p class="muted" style="margin: 0">Nothing publishes until you select and approve a generated option.</p>
            </div>
          </div>
        </div>
      </section>
    </div>
  </section>
</template>
