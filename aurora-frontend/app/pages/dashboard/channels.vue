<script setup lang="ts">
import { Link, RefreshCw, Unplug } from '@lucide/vue'

const { channel, loading, error, fetchChannel, connectChannel, disconnectChannel } = useTelegramChannel()
const channelName = ref('')
const saving = ref(false)

onMounted(fetchChannel)

const connect = async () => {
  if (!channelName.value.trim()) {
    return
  }

  saving.value = true

  try {
    await connectChannel(channelName.value.trim())
    channelName.value = ''
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <section>
    <div class="page-head">
      <div>
        <p class="eyebrow">Telegram account</p>
        <h1>Channel</h1>
      </div>
      <button class="btn icon" type="button" title="Refresh channel" @click="fetchChannel">
        <RefreshCw :size="17" aria-hidden="true" />
      </button>
    </div>

    <div class="grid two">
      <TelegramChannelStatus :channel="channel" />

      <section class="panel">
        <div class="panel-head">
          <h2>Connection</h2>
          <span v-if="loading" class="muted">Loading</span>
        </div>
        <div class="panel-body">
          <form class="stack" @submit.prevent="connect">
            <label class="field">
              <span>Channel username or ID</span>
              <input v-model="channelName" class="input" placeholder="@your_channel" />
            </label>
            <p v-if="error" class="error">{{ error }}</p>
            <div class="button-row">
              <button class="btn primary" type="submit" :disabled="saving">
                <Link :size="16" aria-hidden="true" />
                Connect
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
  </section>
</template>
