import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import VibePage from '~/pages/vibe.vue'
import type { Suggestion } from '~/types/api'

const { postMock } = vi.hoisted(() => ({ postMock: vi.fn() }))

mockNuxtImport('useApi', () => () => ({ get: vi.fn(), post: postMock }))

const suggestion: Suggestion = {
  mood: { slug: 'vibe', label: 'dark moody post-punk', emoji: '🎵' },
  primary: {
    id: 1,
    discogs_id: 111,
    title: 'Night Drive',
    artist: 'Nocturne',
    cover_image: null,
    thumb: null,
    year: 1986,
    lowest_price: null,
  },
  backups: [],
}

function fetchError(statusCode: number, data: unknown) {
  const error = new Error('fetch failed') as Error & { statusCode: number; data: unknown }
  error.statusCode = statusCode
  error.data = data
  return error
}

let mountedWrapper: Awaited<ReturnType<typeof mountSuspended>> | undefined

async function mountVibePage(prompt?: string) {
  const route = prompt === undefined ? '/vibe' : `/vibe?prompt=${encodeURIComponent(prompt)}`
  const wrapper = await mountSuspended(VibePage, { route })
  await flushPromises()

  mountedWrapper = wrapper
  return wrapper
}

describe('vibe result page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    clearNuxtData()
  })

  afterEach(() => {
    // Instances left mounted across tests share the router singleton, so a
    // route change in a later test would otherwise re-trigger their watchers.
    mountedWrapper?.unmount()
    mountedWrapper = undefined
  })

  it('posts the prompt from the query string and renders the suggestion', async () => {
    postMock.mockResolvedValue({ data: suggestion })

    const wrapper = await mountVibePage('dark moody post-punk')

    expect(postMock).toHaveBeenCalledWith('/vibe/suggest', { prompt: 'dark moody post-punk' })
    expect(wrapper.text()).toContain('Night Drive')
    expect(wrapper.text()).toContain('dark moody post-punk')
  })

  it('shows a fallback state and skips the request when the prompt is missing', async () => {
    const wrapper = await mountVibePage()

    expect(postMock).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('No search to show')
  })

  it('shows a fallback state and skips the request when the prompt is too short', async () => {
    const wrapper = await mountVibePage('ab')

    expect(postMock).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('No search to show')
  })

  it('shows the API message when the collection is empty', async () => {
    postMock.mockRejectedValue(
      fetchError(422, { message: 'Your collection is empty. Sync your Discogs collection to get suggestions.' }),
    )

    const wrapper = await mountVibePage('smooth jazz')

    expect(wrapper.text()).toContain('Your collection is empty. Sync your Discogs collection to get suggestions.')
  })

  it('re-posts the same prompt on try again', async () => {
    postMock.mockResolvedValue({ data: suggestion })

    const wrapper = await mountVibePage('dark moody post-punk')

    await wrapper.get('button').trigger('click')
    await flushPromises()

    expect(postMock).toHaveBeenCalledTimes(2)
    expect(postMock).toHaveBeenLastCalledWith('/vibe/suggest', { prompt: 'dark moody post-punk' })
  })
})
