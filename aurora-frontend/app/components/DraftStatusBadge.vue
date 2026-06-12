<script setup lang="ts">
import { CheckCircle, CircleDot, Clock, Send, XCircle } from '@lucide/vue'
import type { DraftStatus } from '~/composables/useApi'

const props = defineProps<{
  status: DraftStatus | string
}>()

const badgeClass = computed(() => {
  if (['published', 'approved', 'selected', 'generated'].includes(props.status)) {
    return 'status-good'
  }

  if (['generating', 'publishing', 'draft'].includes(props.status)) {
    return 'status-active'
  }

  if (['failed', 'cancelled'].includes(props.status)) {
    return 'status-bad'
  }

  return 'status-neutral'
})

const icon = computed(() => {
  if (props.status === 'published') {
    return CheckCircle
  }

  if (props.status === 'publishing') {
    return Send
  }

  if (['generating', 'draft'].includes(props.status)) {
    return Clock
  }

  if (['failed', 'cancelled'].includes(props.status)) {
    return XCircle
  }

  return CircleDot
})
</script>

<template>
  <span class="status-badge" :class="badgeClass">
    <component :is="icon" :size="14" :stroke-width="2.5" aria-hidden="true" />
    {{ status }}
  </span>
</template>
