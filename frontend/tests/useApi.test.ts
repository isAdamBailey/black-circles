import { describe, expect, it, vi } from 'vitest'
import { mockNuxtImport } from '@nuxt/test-utils/runtime'

const { fetchMock } = vi.hoisted(() => ({ fetchMock: vi.fn() }))

mockNuxtImport('$fetch', () => fetchMock)

describe('useApi', () => {
  it('GETs against the configured API base', async () => {
    fetchMock.mockResolvedValueOnce({ data: { moods: [], username: 'tester', insight: '' } })

    const { get } = useApi()
    const response = await get<{ data: { username: string } }>('/home')

    expect(response.data.username).toBe('tester')
    expect(fetchMock).toHaveBeenCalledWith(
      '/home',
      expect.objectContaining({ baseURL: 'http://localhost/api/v1', method: 'GET' }),
    )
  })

  it('POSTs a JSON body against the configured API base', async () => {
    fetchMock.mockResolvedValueOnce({ data: { primary: { title: 'Test Album' } } })

    const { post } = useApi()
    const response = await post<{ data: { primary: { title: string } } }>('/vibe/suggest', {
      prompt: 'dark moody post-punk',
    })

    expect(response.data.primary.title).toBe('Test Album')
    expect(fetchMock).toHaveBeenCalledWith(
      '/vibe/suggest',
      expect.objectContaining({ method: 'POST', body: { prompt: 'dark moody post-punk' } }),
    )
  })
})
