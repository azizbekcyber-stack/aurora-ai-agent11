export type DraftStatus =
  | 'draft'
  | 'generating'
  | 'generated'
  | 'selected'
  | 'approved'
  | 'publishing'
  | 'published'
  | 'failed'
  | 'cancelled'

export interface PostVariant {
  id: number
  post_draft_id: number
  title: string | null
  body: string
  hashtags: string[] | null
  cta: string | null
  telegram_text: string
  risk_flags: string[] | null
  created_at: string
}

export interface PublishLog {
  id: number
  status: 'success' | 'failed'
  platform: 'telegram'
  publish_strategy: string | null
  telegram_message_ids: Array<number | string> | null
  error_message: string | null
  published_at: string | null
  created_at: string
}

export interface TelegramChannel {
  id: number
  chat_id: string
  username: string | null
  title: string | null
  bot_can_post_messages: boolean
  status: 'pending' | 'connected' | 'failed' | 'disconnected'
  connected_at: string | null
  last_checked_at: string | null
}

export interface PostDraft {
  id: number
  user_id: number
  telegram_channel_id: number | null
  prompt: string
  image_path: string | null
  source: 'telegram' | 'web'
  status: DraftStatus
  selected_variant_id: number | null
  created_at: string
  updated_at: string
  variants?: PostVariant[]
  selected_variant?: PostVariant | null
  publish_logs?: PublishLog[]
  telegram_channel?: TelegramChannel | null
}

export interface BrandProfile {
  id: number
  user_id: number
  default_language: 'uz' | 'ru' | 'en'
  tone: string | null
  audience: string | null
  emoji_level: 'none' | 'low' | 'medium' | 'high'
  hashtag_style: 'none' | 'minimal' | 'normal' | 'aggressive'
  banned_words: string[] | null
}

interface Paginated<T> {
  data: T[]
  current_page: number
  last_page: number
  total: number
}

export const useApi = () => {
  const config = useRuntimeConfig()
  const userId = useCookie<string | null>('aurora_user_id', {
    sameSite: 'lax',
    default: () => null
  })
  const dashboardToken = useCookie<string | null>('aurora_dashboard_token', {
    sameSite: 'lax',
    secure: process.env.NODE_ENV === 'production',
    default: () => null
  })

  const request = async <T>(path: string, options: Parameters<typeof $fetch<T>>[1] = {}) => {
    const headers = new Headers(options?.headers as HeadersInit | undefined)

    if (userId.value) {
      headers.set('X-Aurora-User-Id', userId.value)
    }

    if (dashboardToken.value) {
      headers.set('X-Aurora-Dashboard-Token', dashboardToken.value)
    }

    return await $fetch<T>(path, {
      baseURL: config.public.apiBase,
      ...options,
      headers
    })
  }

  return {
    request,
    userId,
    dashboardToken
  }
}

export type { Paginated }
