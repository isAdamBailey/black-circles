import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import CollectionShowPage from '~/pages/collection/[id].vue'
import type { Release } from '~/types/api'

const { getMock } = vi.hoisted(() => ({ getMock: vi.fn() }))

mockNuxtImport('useApi', () => () => ({ get: getMock, post: vi.fn() }))

function release(overrides: Partial<Release> = {}): Release {
  return {
    discogs_id: 111,
    title: 'Chill Sessions',
    artist: 'Lounge Act',
    label: 'Lounge Records',
    catalog_number: 'LR-001',
    year: 2001,
    cover_image: 'https://example.com/cover.jpg',
    thumb: null,
    images: [{ uri: 'https://example.com/cover.jpg' }, { uri: 'https://example.com/back.jpg' }],
    formats: [{ name: 'Vinyl' }],
    tracklist: [{ position: 'A1', title: 'Intro', duration: '1:00' }],
    videos: [{ uri: 'https://youtu.be/dQw4w9WgXcQ', title: 'Official Video', embed: true }],
    lowest_price: '25.00',
    median_price: '30.00',
    highest_price: '40.00',
    discogs_uri: 'https://discogs.com/release/111',
    notes: 'Great record.',
    genres: ['Jazz'],
    styles: ['Ambient'],
    collection_item: { rating: 4, date_added: '2020-01-01T00:00:00.000000Z' },
    ...overrides,
  }
}

function fetchError(statusCode: number) {
  const error = new Error('fetch failed') as Error & { statusCode: number }
  error.statusCode = statusCode
  return error
}

let mountedWrapper: Awaited<ReturnType<typeof mountSuspended>> | undefined

async function mountShowPage(id = '111') {
  const wrapper = await mountSuspended(CollectionShowPage, { route: `/collection/${id}` })
  await flushPromises()

  mountedWrapper = wrapper
  return wrapper
}

describe('collection show page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    clearNuxtData()
  })

  afterEach(() => {
    mountedWrapper?.unmount()
    mountedWrapper = undefined
  })

  it('fetches the release by id and renders its details', async () => {
    getMock.mockResolvedValue({ data: release() })

    const wrapper = await mountShowPage('111')

    expect(getMock).toHaveBeenCalledWith('/collection/111')
    expect(wrapper.get('h1').text()).toBe('Chill Sessions')
    expect(wrapper.text()).toContain('Lounge Act')
    expect(wrapper.text()).toContain('Lounge Records')
    expect(wrapper.text()).toContain('Vinyl')
    expect(wrapper.text()).toContain('Intro')
    expect(wrapper.text()).toContain('★★★★☆')
    expect(wrapper.text()).toContain('$25.00')
    expect(wrapper.find('a[href="https://discogs.com/release/111"]').exists()).toBe(true)
  })

  it('links genre and style chips to the filtered collection index', async () => {
    getMock.mockResolvedValue({ data: release() })

    const wrapper = await mountShowPage('111')

    const genreLink = wrapper.findAll('a').find((a) => a.text() === 'Jazz')
    expect(genreLink?.attributes('href')).toContain('/collection')
  })

  it('shows a not-found state for an unknown release', async () => {
    getMock.mockRejectedValue(fetchError(404))

    const wrapper = await mountShowPage('999999')

    expect(wrapper.text()).toContain('Release not found')
  })

  it('surfaces an API failure', async () => {
    getMock.mockRejectedValue(fetchError(500))

    const wrapper = await mountShowPage('111')

    expect(wrapper.text()).toContain("Couldn't load this release")
  })
})

