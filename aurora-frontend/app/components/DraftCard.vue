<script setup lang="ts">
import { ArrowRight, Image, MessageSquareText } from '@lucide/vue'
import type { PostDraft } from '~/composables/useApi'

defineProps<{
  draft: PostDraft
}>()
</script>

<template>
  <NuxtLink class="draft-card" :to="`/dashboard/drafts/${draft.id}`">
    <div class="card-row">
      <div class="truncate">
        <strong>#{{ draft.id }}</strong>
        <span class="muted"> · {{ draft.source }}</span>
      </div>
      <DraftStatusBadge :status="draft.status" />
    </div>

    <p class="muted prewrap" style="margin: 0">{{ draft.prompt }}</p>

    <div class="card-row muted">
      <span class="button-row">
        <span class="button-row">
          <MessageSquareText :size="16" aria-hidden="true" />
          {{ draft.variants?.length || 0 }} variants
        </span>
        <span v-if="draft.image_path" class="button-row">
          <Image :size="16" aria-hidden="true" />
          image
        </span>
      </span>
      <ArrowRight :size="18" aria-hidden="true" />
    </div>
  </NuxtLink>
</template>
