import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import MoodSuggestPage from '~/pages/mood/[slug].vue'
import type { Suggestion } from '~/types/api'

const { getMock } = vi.hoisted(() => ({ getMock: vi.fn() }))

mockNuxtImport('useApi', () => () => ({ get: getMock, post: vi.fn() }))

const suggestion: Suggestion = {
  mood: { slug: 'chill', label: 'Chill', emoji: '🌙' },
  primary: {
    id: 1,
    discogs_id: 111,
    title: 'Chill Sessions',
    artist: 'Lounge Act',
    cover_image: null,
    thumb: null,
    year: 2001,
    lowest_price: null,
    genres: ['Jazz'],
    styles: ['Ambient'],
  },
  backups: [
    {
      id: 2,
      discogs_id: 222,
      title: 'Backup Album',
      artist: 'Backup Artist',
      cover_image: null,
      thumb: null,
      year: 1999,
      lowest_price: null,
    },
  ],
}

function fetchError(statusCode: number, data: unknown) {
  const error = new Error('fetch failed') as Error & { statusCode: number; data: unknown }
  error.statusCode = statusCode
  error.data = data
  return error
}

let mountedWrapper: Awaited<ReturnType<typeof mountSuspended>> | undefined

async function mountMoodPage(slug = 'chill') {
  const wrapper = await mountSuspended(MoodSuggestPage, { route: `/mood/${slug}` })
  await flushPromises()

  mountedWrapper = wrapper
  return wrapper
}

describe('mood suggest page', () => {
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

  it('fetches the mood suggestion by slug and renders it', async () => {
    getMock.mockResolvedValue({ data: suggestion })

    const wrapper = await mountMoodPage('chill')

    expect(getMock).toHaveBeenCalledWith('/moods/chill/suggest')
    expect(wrapper.text()).toContain('Chill Sessions')
    expect(wrapper.text()).toContain("Also in Adam's collection")
  })

  it('shows a not-found state for an unknown mood', async () => {
    getMock.mockRejectedValue(fetchError(404, { message: 'Mood not found.' }))

    const wrapper = await mountMoodPage('not-a-real-mood')

    expect(wrapper.text()).toContain('Mood not found')
  })

  it('shows the API message when the collection is empty', async () => {
    getMock.mockRejectedValue(
      fetchError(422, { message: 'Your collection is empty. Sync your Discogs collection to get suggestions.' }),
    )

    const wrapper = await mountMoodPage('chill')

    expect(wrapper.text()).toContain('Your collection is empty. Sync your Discogs collection to get suggestions.')
  })

  it('refetches on try again and re-enables the button once it resolves', async () => {
    getMock.mockResolvedValue({ data: suggestion })

    const wrapper = await mountMoodPage('chill')
    const button = wrapper.get('button')

    await button.trigger('click')
    await flushPromises()

    expect(getMock).toHaveBeenCalledTimes(2)
    expect(button.attributes('disabled')).toBeUndefined()
    expect(button.text()).toBe('Try again')
  })
})
