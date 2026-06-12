import type { TelegramChannel } from './useApi'

export const useTelegramChannel = () => {
  const { request } = useApi()
  const channel = useState<TelegramChannel | null>('telegramChannel', () => null)
  const loading = useState('telegramChannelLoading', () => false)
  const error = useState<string | null>('telegramChannelError', () => null)

  const fetchChannel = async () => {
    loading.value = true
    error.value = null

    try {
      channel.value = await request<TelegramChannel | null>('/telegram/channel')
    } catch (exception: any) {
      error.value = exception?.data?.message || exception?.message || 'Could not load Telegram channel.'
    } finally {
      loading.value = false
    }
  }

  const connectChannel = async (channelName: string) => {
    error.value = null

    try {
      channel.value = await request<TelegramChannel>('/telegram/channel/connect', {
        method: 'POST',
        body: { channel: channelName }
      })
    } catch (exception: any) {
      error.value = exception?.data?.message || exception?.message || 'Could not connect channel.'
      throw exception
    }
  }

  const disconnectChannel = async () => {
    await request('/telegram/channel', {
      method: 'DELETE'
    })
    await fetchChannel()
  }

  return {
    channel,
    loading,
    error,
    fetchChannel,
    connectChannel,
    disconnectChannel
  }
}
