<script setup lang="ts">
import { BadgeCheck, PlugZap, XCircle } from '@lucide/vue'
import type { TelegramChannel } from '~/composables/useApi'

const props = defineProps<{
  channel: TelegramChannel | null
}>()

const iconFor = computed(() => {
  if (! channelValue.value) {
    return PlugZap
  }

  if (channelValue.value.status === 'connected' && channelValue.value.bot_can_post_messages) {
    return BadgeCheck
  }

  return XCircle
})

const statusClass = computed(() => {
  if (! channelValue.value) {
    return 'status-neutral'
  }

  return channelValue.value.status === 'connected' && channelValue.value.bot_can_post_messages
    ? 'status-good'
    : 'status-bad'
})

const channelValue = computed(() => props.channel)
</script>

<template>
  <section class="panel">
    <div class="panel-head">
      <h2>Telegram Channel</h2>
      <span class="status-badge" :class="statusClass">
        <component :is="iconFor" :size="14" aria-hidden="true" />
        {{ channel?.status || 'not connected' }}
      </span>
    </div>
    <div class="panel-body stack">
      <template v-if="channel">
        <div>
          <strong>{{ channel.title || channel.username || channel.chat_id }}</strong>
          <p class="muted" style="margin: 4px 0 0">{{ channel.username ? `@${channel.username}` : channel.chat_id }}</p>
        </div>
        <div class="metric-grid">
          <div class="metric">
            <span class="muted">Can post</span>
            <strong>{{ channel.bot_can_post_messages ? 'Yes' : 'No' }}</strong>
          </div>
          <div class="metric">
            <span class="muted">Status</span>
            <strong>{{ channel.status }}</strong>
          </div>
          <div class="metric">
            <span class="muted">Chat ID</span>
            <strong style="font-size: 1rem">{{ channel.chat_id }}</strong>
          </div>
        </div>
      </template>
      <div v-else class="empty">No channel connected.</div>
    </div>
  </section>
</template>
