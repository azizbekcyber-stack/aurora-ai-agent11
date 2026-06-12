import type { Paginated, PostDraft } from './useApi'

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

  const createDraft = async (prompt: string) => {
    const draft = await request<PostDraft>('/drafts', {
      method: 'POST',
      body: { prompt }
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
