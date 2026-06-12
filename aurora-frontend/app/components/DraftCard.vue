<script setup lang="ts">
import { ArrowRight, Bot, Globe2, Image, MessageSquareText } from '@lucide/vue'
import type { PostDraft } from '~/composables/useApi'

defineProps<{
  draft: PostDraft
}>()

const formatDate = (value: string) => new Intl.DateTimeFormat('en', {
  month: 'short',
  day: 'numeric',
  hour: '2-digit',
  minute: '2-digit'
}).format(new Date(value))
</script>

<template>
  <NuxtLink class="draft-card" :to="`/dashboard/drafts/${draft.id}`">
    <div class="card-row">
      <div class="truncate">
        <strong>Draft #{{ draft.id }}</strong>
        <span class="muted"> · {{ formatDate(draft.created_at) }}</span>
      </div>
      <DraftStatusBadge :status="draft.status" />
    </div>

    <p class="muted prewrap" style="margin: 0">{{ draft.prompt }}</p>

    <div class="card-row muted">
      <span class="button-row">
        <span class="source-badge">
          <Bot v-if="draft.source === 'telegram'" :size="14" aria-hidden="true" />
          <Globe2 v-else :size="14" aria-hidden="true" />
          {{ draft.source }}
        </span>
        <span class="button-row strong-muted">
          <MessageSquareText :size="16" aria-hidden="true" />
          {{ draft.variants?.length || 0 }} variants
        </span>
        <span v-if="draft.image_path" class="button-row strong-muted">
          <Image :size="16" aria-hidden="true" />
          image
        </span>
      </span>
      <span class="metric-icon blue">
        <ArrowRight :size="17" aria-hidden="true" />
      </span>
    </div>
  </NuxtLink>
</template>
