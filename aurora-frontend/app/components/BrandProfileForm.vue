<script setup lang="ts">
import { Save } from '@lucide/vue'
import type { BrandProfile } from '~/composables/useApi'

const props = defineProps<{
  profile: BrandProfile | null
  saving?: boolean
}>()

const emit = defineEmits<{
  save: [payload: Partial<BrandProfile>]
}>()

const form = reactive({
  default_language: 'en',
  tone: '',
  audience: '',
  emoji_level: 'medium',
  hashtag_style: 'normal',
  banned_words: ''
})

watch(
  () => props.profile,
  (profile) => {
    if (!profile) {
      return
    }

    form.default_language = profile.default_language
    form.tone = profile.tone || ''
    form.audience = profile.audience || ''
    form.emoji_level = profile.emoji_level
    form.hashtag_style = profile.hashtag_style
    form.banned_words = (profile.banned_words || []).join('\n')
  },
  { immediate: true }
)

const submit = () => {
  emit('save', {
    default_language: form.default_language as BrandProfile['default_language'],
    tone: form.tone || null,
    audience: form.audience || null,
    emoji_level: form.emoji_level as BrandProfile['emoji_level'],
    hashtag_style: form.hashtag_style as BrandProfile['hashtag_style'],
    banned_words: form.banned_words
      .split(/\r?\n|,/)
      .map((word) => word.trim())
      .filter(Boolean)
  })
}
</script>

<template>
  <form class="stack" @submit.prevent="submit">
    <div class="grid two">
      <label class="field">
        <span>Default language</span>
        <select v-model="form.default_language" class="select">
          <option value="en">English</option>
          <option value="uz">Uzbek</option>
          <option value="ru">Russian</option>
        </select>
      </label>

      <label class="field">
        <span>Emoji level</span>
        <select v-model="form.emoji_level" class="select">
          <option value="none">None</option>
          <option value="low">Low</option>
          <option value="medium">Medium</option>
          <option value="high">High</option>
        </select>
      </label>
    </div>

    <div class="grid two">
      <label class="field">
        <span>Tone</span>
        <input v-model="form.tone" class="input" placeholder="Confident, warm, concise" />
      </label>

      <label class="field">
        <span>Audience</span>
        <input v-model="form.audience" class="input" placeholder="Telegram channel subscribers" />
      </label>
    </div>

    <label class="field">
      <span>Hashtag style</span>
      <select v-model="form.hashtag_style" class="select">
        <option value="none">None</option>
        <option value="minimal">Minimal</option>
        <option value="normal">Normal</option>
        <option value="aggressive">Aggressive</option>
      </select>
    </label>

    <label class="field">
      <span>Banned words</span>
      <textarea v-model="form.banned_words" class="textarea" />
    </label>

    <div class="button-row">
      <button class="btn primary" type="submit" :disabled="saving">
        <Save :size="16" aria-hidden="true" />
        Save
      </button>
    </div>
  </form>
</template>
