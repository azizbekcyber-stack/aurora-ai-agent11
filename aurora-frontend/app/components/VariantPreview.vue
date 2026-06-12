<script setup lang="ts">
import { Check, ShieldAlert } from '@lucide/vue'
import type { PostVariant } from '~/composables/useApi'

defineProps<{
  variant: PostVariant
  selected?: boolean
}>()

defineEmits<{
  select: [variantId: number]
}>()
</script>

<template>
  <article class="variant-card" :class="{ selected }">
    <div class="card-row">
      <h3>{{ variant.title || 'Untitled variant' }}</h3>
      <span v-if="selected" class="status-badge status-good">
        <Check :size="14" aria-hidden="true" />
        selected
      </span>
    </div>

    <p class="prewrap">{{ variant.telegram_text }}</p>

    <div v-if="variant.risk_flags?.length" class="muted button-row">
      <ShieldAlert :size="16" aria-hidden="true" />
      {{ variant.risk_flags.join(', ') }}
    </div>

    <div class="button-row">
      <button class="btn" type="button" :disabled="selected" @click="$emit('select', variant.id)">
        <Check :size="16" aria-hidden="true" />
        Select
      </button>
    </div>
  </article>
</template>
