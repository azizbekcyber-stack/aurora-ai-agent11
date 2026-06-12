<script setup lang="ts">
import { Bot, Link, RefreshCw, ShieldCheck, Unplug } from '@lucide/vue'

const { channel, loading, error, fetchChannel, connectChannel, disconnectChannel } = useTelegramChannel()
const channelName = ref('')
const saving = ref(false)
const saveError = ref<string | null>(null)

onMounted(fetchChannel)

const connect = async () => {
  if (!channelName.value.trim()) {
    return
  }

  saving.value = true
  saveError.value = null

  try {
    await connectChannel(channelName.value.trim())
    channelName.value = ''
  } catch (exception: any) {
    saveError.value = exception?.data?.message || exception?.message || 'Could not connect channel.'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <section class="page">
    <div class="page-head">
      <div>
        <p class="eyebrow">Channel setup</p>
        <h1>Publishing destination</h1>
        <p class="muted" style="margin: 8px 0 0">
          Approved web drafts and Telegram bot drafts are sent to this channel.
        </p>
      </div>
      <button class="btn icon" type="button" title="Refresh channel" @click="fetchChannel">
        <RefreshCw :size="17" aria-hidden="true" />
      </button>
    </div>

    <div class="content-grid">
      <TelegramChannelStatus :channel="channel" />

      <section class="panel">
        <div class="panel-head">
          <div class="button-row">
            <span class="metric-icon">
              <Link :size="16" aria-hidden="true" />
            </span>
            <h2>Connect or replace</h2>
          </div>
          <span v-if="loading" class="muted">Loading</span>
        </div>
        <div class="panel-body">
          <form class="stack" @submit.prevent="connect">
            <label class="field">
              <span>Channel username or ID</span>
              <input v-model="channelName" class="input" placeholder="@your_channel" />
            </label>

            <div class="info-strip">
              <div class="destination-main">
                <span class="metric-icon">
                  <ShieldCheck :size="16" aria-hidden="true" />
                </span>
                <div>
                  <span class="destination-title">Bot admin permission is required</span>
                  <span class="destination-meta">Enable posting permission before connecting.</span>
                </div>
              </div>
            </div>

            <p v-if="error || saveError" class="error">{{ saveError || error }}</p>

            <div class="button-row">
              <button class="btn primary" type="submit" :disabled="saving || !channelName.trim()">
                <Link :size="16" aria-hidden="true" />
                Connect channel
              </button>
              <button class="btn danger" type="button" :disabled="!channel" @click="disconnectChannel">
                <Unplug :size="16" aria-hidden="true" />
                Disconnect
              </button>
            </div>
          </form>
        </div>
      </section>
    </div>

    <section class="panel">
      <div class="panel-head">
        <div class="button-row">
          <span class="metric-icon">
            <Bot :size="16" aria-hidden="true" />
          </span>
          <h2>Telegram bot flow</h2>
        </div>
      </div>
      <div class="panel-body">
        <div class="grid three">
          <div class="quick-card">
            <strong>1. Start bot</strong>
            <p class="muted" style="margin: 0">Send /start to the bot.</p>
          </div>
          <div class="quick-card">
            <strong>2. Connect channel</strong>
            <p class="muted" style="margin: 0">Send /connect_channel or connect it here by username.</p>
          </div>
          <div class="quick-card">
            <strong>3. Publish after approval</strong>
            <p class="muted" style="margin: 0">Create a draft, pick one variant, approve, then publish.</p>
          </div>
        </div>
      </div>
    </section>
  </section>
</template>
