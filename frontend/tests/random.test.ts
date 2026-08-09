import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import RandomPage from '~/pages/random.vue'
import type { Release } from '~/types/api'

const { getMock, navigateToMock } = vi.hoisted(() => ({
  getMock: vi.fn(),
  navigateToMock: vi.fn(),
}))

mockNuxtImport('useApi', () => () => ({ get: getMock, post: vi.fn() }))
mockNuxtImport('navigateTo', () => navigateToMock)

function release(overrides: Partial<Release> = {}): Release {
  return {
    discogs_id: 111,
    title: 'Chill Sessions',
    artist: 'Lounge Act',
    label: null,
    catalog_number: null,
    year: 2001,
    cover_image: null,
    thumb: null,
    images: null,
    formats: null,
    tracklist: null,
    videos: null,
    lowest_price: null,
    median_price: null,
    highest_price: null,
    discogs_uri: null,
    notes: null,
    ...overrides,
  }
}

function fetchError(statusCode: number, data: unknown) {
  const error = new Error('fetch failed') as Error & { statusCode: number; data: unknown }
  error.statusCode = statusCode
  error.data = data
  return error
}

let mountedWrapper: Awaited<ReturnType<typeof mountSuspended>> | undefined

async function mountRandomPage() {
  const wrapper = await mountSuspended(RandomPage, { route: '/random' })
  await flushPromises()

  mountedWrapper = wrapper
  return wrapper
}

describe('random page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    clearNuxtData('random-release')
  })

  afterEach(() => {
    mountedWrapper?.unmount()
    mountedWrapper = undefined
  })

  it('fetches a random release and navigates to its detail page', async () => {
    getMock.mockResolvedValue({ data: release() })

    await mountRandomPage()

    expect(getMock).toHaveBeenCalledWith('/collection/random')
    expect(navigateToMock).toHaveBeenCalledWith('/collection/111', { replace: true })
  })

  it('shows the API message when the collection is empty', async () => {
    getMock.mockRejectedValue(
      fetchError(404, { message: 'Your collection is empty. Sync your Discogs collection to get suggestions.' }),
    )

    const wrapper = await mountRandomPage()

    expect(wrapper.text()).toContain('Your collection is empty. Sync your Discogs collection to get suggestions.')
    expect(navigateToMock).not.toHaveBeenCalled()
  })

  it('surfaces an API failure', async () => {
    getMock.mockRejectedValue(fetchError(500, null))

    const wrapper = await mountRandomPage()

    expect(wrapper.text()).toContain("Couldn't load a release")
  })
})
