import type { Paginated, PostDraft } from './useApi'

const MAX_IMAGE_BYTES = 10 * 1024 * 1024
const SUPPORTED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp']

export const getDraftErrorMessage = (exception: any, fallback = 'Action failed.') => {
  const errors = exception?.data?.errors

  if (errors && typeof errors === 'object') {
    const first = Object.values(errors).flat().find(Boolean)

    if (typeof first === 'string') {
      return first
    }
  }

  return exception?.data?.message || exception?.message || fallback
}

const imageToPayload = async (image: File) => {
  if (!SUPPORTED_IMAGE_TYPES.includes(image.type)) {
    throw new Error('Please attach a JPG, PNG, or WebP image.')
  }

  if (image.size > MAX_IMAGE_BYTES) {
    throw new Error('Please attach an image under 10 MB.')
  }

  const dataUrl = await new Promise<string>((resolve, reject) => {
    const reader = new FileReader()
    reader.onload = () => resolve(String(reader.result || ''))
    reader.onerror = () => reject(new Error('Could not read the selected image.'))
    reader.readAsDataURL(image)
  })

  const [, base64 = ''] = dataUrl.split(',')

  if (!base64) {
    throw new Error('Could not read the selected image.')
  }

  return {
    image_data: base64,
    image_mime_type: image.type
  }
}

export const useDrafts = () => {
  const { request } = useApi()
  const drafts = useState<PostDraft[]>('drafts', () => [])
  const currentDraft = useState<PostDraft | null>('currentDraft', () => null)
  const loading = useState('draftsLoading', () => false)
  const error = useState<string | null>('draftsError', () => null)

  const fetchDrafts = async () => {
    loading.value = true
    error.value = null

    try {
      const response = await request<Paginated<PostDraft>>('/drafts')
      drafts.value = response.data
    } catch (exception: any) {
      error.value = exception?.data?.message || exception?.message || 'Could not load drafts.'
    } finally {
      loading.value = false
    }
  }

  const fetchDraft = async (id: string | number) => {
    loading.value = true
    error.value = null

    try {
      currentDraft.value = await request<PostDraft>(`/drafts/${id}`)
    } catch (exception: any) {
      error.value = exception?.data?.message || exception?.message || 'Could not load draft.'
    } finally {
      loading.value = false
    }
  }

  const createDraft = async (prompt: string, image?: File | null) => {
    const body: Record<string, string> = { prompt }

    if (image) {
      Object.assign(body, await imageToPayload(image))
    }

    const draft = await request<PostDraft>('/drafts', {
      method: 'POST',
      body
    })
    drafts.value = [draft, ...drafts.value]
    return draft
  }

  const selectVariant = async (draftId: number, variantId: number) => {
    currentDraft.value = await request<PostDraft>(`/drafts/${draftId}/select-variant`, {
      method: 'POST',
      body: { variant_id: variantId }
    })
  }

  const approveDraft = async (draftId: number) => {
    currentDraft.value = await request<PostDraft>(`/drafts/${draftId}/approve`, {
      method: 'POST'
    })
  }

  const publishDraft = async (draftId: number) => {
    await request(`/drafts/${draftId}/publish`, {
      method: 'POST'
    })
    await fetchDraft(draftId)
  }

  const cancelDraft = async (draftId: number) => {
    currentDraft.value = await request<PostDraft>(`/drafts/${draftId}/cancel`, {
      method: 'POST'
    })
  }

  return {
    drafts,
    currentDraft,
    loading,
    error,
    fetchDrafts,
    fetchDraft,
    createDraft,
    selectVariant,
    approveDraft,
    publishDraft,
    cancelDraft
  }
}
