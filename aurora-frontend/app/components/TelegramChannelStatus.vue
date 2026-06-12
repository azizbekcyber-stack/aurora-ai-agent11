<script setup lang="ts">
import { BadgeCheck, Bot, Radio, Send, ShieldCheck, XCircle } from '@lucide/vue'
import type { TelegramChannel } from '~/composables/useApi'

const props = defineProps<{
  channel: TelegramChannel | null
}>()

const channelValue = computed(() => props.channel)
const connected = computed(() => channelValue.value?.status === 'connected' && channelValue.value.bot_can_post_messages)
const displayName = computed(() => channelValue.value?.title || channelValue.value?.username || channelValue.value?.chat_id || 'No channel')
const handle = computed(() => {
  if (!channelValue.value) {
    return 'Connect a channel before publishing'
  }

  return channelValue.value.username ? `@${channelValue.value.username}` : channelValue.value.chat_id
})

const iconFor = computed(() => connected.value ? BadgeCheck : XCircle)
const statusClass = computed(() => connected.value ? 'status-good' : (channelValue.value ? 'status-bad' : 'status-neutral'))
</script>

<template>
  <section class="panel">
    <div class="panel-head">
      <div class="button-row">
        <span class="metric-icon">
          <Radio :size="16" aria-hidden="true" />
        </span>
        <h2>Telegram channel</h2>
      </div>
      <span class="status-badge" :class="statusClass">
        <component :is="iconFor" :size="14" aria-hidden="true" />
        {{ connected ? 'ready' : (channel?.status || 'not connected') }}
      </span>
    </div>

    <div class="panel-body stack">
      <template v-if="channel">
        <div class="destination-strip">
          <div class="destination-main">
            <span class="metric-icon">
              <Send :size="16" aria-hidden="true" />
            </span>
            <div class="truncate">
              <span class="destination-title">{{ displayName }}</span>
              <span class="destination-meta">{{ handle }}</span>
            </div>
          </div>
          <span class="status-badge" :class="connected ? 'status-good' : 'status-active'">
            Publish destination
          </span>
        </div>

        <div class="grid three">
          <div class="quick-card">
            <Bot :size="18" aria-hidden="true" />
            <span class="muted">Source</span>
            <strong>Bot + Web</strong>
          </div>
          <div class="quick-card">
            <ShieldCheck :size="18" aria-hidden="true" />
            <span class="muted">Can post</span>
            <strong>{{ channel.bot_can_post_messages ? 'Yes' : 'No' }}</strong>
          </div>
          <div class="quick-card">
            <Radio :size="18" aria-hidden="true" />
            <span class="muted">Status</span>
            <strong>{{ channel.status }}</strong>
          </div>
        </div>
      </template>

      <div v-else class="empty">
        Connect a Telegram channel to publish approved web drafts and bot-created drafts.
      </div>
    </div>
  </section>
</template>
