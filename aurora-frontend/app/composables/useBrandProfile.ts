import type { BrandProfile } from './useApi'

export const useBrandProfile = () => {
  const { request } = useApi()
  const profile = useState<BrandProfile | null>('brandProfile', () => null)
  const loading = useState('brandProfileLoading', () => false)
  const error = useState<string | null>('brandProfileError', () => null)

  const fetchProfile = async () => {
    loading.value = true
    error.value = null

    try {
      profile.value = await request<BrandProfile>('/brand-profile')
    } catch (exception: any) {
      error.value = exception?.data?.message || exception?.message || 'Could not load brand profile.'
    } finally {
      loading.value = false
    }
  }

  const saveProfile = async (payload: Partial<BrandProfile>) => {
    profile.value = await request<BrandProfile>('/brand-profile', {
      method: 'PUT',
      body: payload
    })
  }

  return {
    profile,
    loading,
    error,
    fetchProfile,
    saveProfile
  }
}
