import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import CollectionIndexPage from '~/pages/collection/index.vue'
import type { CollectionIndex, ReleaseSummary } from '~/types/api'

const { getMock, navigateToMock } = vi.hoisted(() => ({
  getMock: vi.fn(),
  navigateToMock: vi.fn(),
}))

mockNuxtImport('useApi', () => () => ({ get: getMock, post: vi.fn() }))
mockNuxtImport('navigateTo', () => navigateToMock)

function release(overrides: Partial<ReleaseSummary> = {}): ReleaseSummary {
  return {
    id: 1,
    discogs_id: 111,
    title: 'Chill Sessions',
    artist: 'Lounge Act',
    cover_image: null,
    thumb: null,
    year: 2001,
    lowest_price: null,
    ...overrides,
  }
}

function collectionResponse(overrides: Partial<CollectionIndex> = {}): CollectionIndex {
  return {
    data: [release()],
    links: { first: null, last: null, prev: null, next: null },
    meta: { current_page: 1, from: 1, last_page: 1, path: '/collection', per_page: 48, to: 1, total: 1 },
    filters: { sort: 'value', direction: 'desc' },
    allGenres: ['Rock', 'Electronic'],
    allStyles: ['Post-Punk', 'Ambient'],
    username: 'adam',
    lastSynced: null,
    ...overrides,
  }
}

function mockCollection(overrides: Partial<CollectionIndex> = {}) {
  getMock.mockImplementation((path: string) => {
    if (path === '/collection') return Promise.resolve(collectionResponse(overrides))
    if (path === '/collection/search') return Promise.resolve({ data: [] })
    throw new Error(`unexpected path ${path}`)
  })
}

let mountedWrapper: Awaited<ReturnType<typeof mountSuspended>> | undefined

async function mountCollection() {
  const wrapper = await mountSuspended(CollectionIndexPage, { route: '/collection' })
  await flushPromises()

  mountedWrapper = wrapper
  return wrapper
}

describe('collection index page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    clearNuxtData('collection-index')
  })

  afterEach(() => {
    mountedWrapper?.unmount()
    mountedWrapper = undefined
    vi.useRealTimers()
  })

  it('renders the grid and record count', async () => {
    mockCollection({ data: [release(), release({ id: 2, discogs_id: 222, title: 'Electric Pulse' })], meta: { current_page: 1, from: 1, last_page: 1, path: '/collection', per_page: 48, to: 2, total: 2 } })

    const wrapper = await mountCollection()

    expect(getMock).toHaveBeenCalledWith('/collection', { query: { sort: 'value', direction: 'desc', page: 1 } })
    expect(wrapper.text()).toContain('2 records')
    expect(wrapper.findAll('a[href^="/collection/"]')).toHaveLength(2)
    expect(wrapper.text()).toContain('Electric Pulse')
  })

  it('shows the sync prompt when no collection has been synced', async () => {
    mockCollection({ username: null, data: [] })

    const wrapper = await mountCollection()

    expect(wrapper.text()).toContain('No collection synced yet')
  })

  it('filters by genre', async () => {
    mockCollection()

    const wrapper = await mountCollection()

    await wrapper.get('button[title="Descending"]')
    await wrapper.findAll('button').find((b) => b.text().startsWith('Filter'))!.trigger('click')
    await wrapper.findAll('button').find((b) => b.text() === 'Rock')!.trigger('click')
    await flushPromises()

    expect(getMock).toHaveBeenLastCalledWith('/collection', {
      query: { sort: 'value', direction: 'desc', page: 1, 'genres[]': ['Rock'] },
    })
  })

  it('debounces search input and fetches autocomplete suggestions', async () => {
    vi.useFakeTimers()
    mockCollection()

    const wrapper = await mountCollection()

    await wrapper.get('input[type="text"]').setValue('Pink')

    await vi.advanceTimersByTimeAsync(400)
    await flushPromises()

    expect(getMock).toHaveBeenLastCalledWith('/collection', {
      query: { sort: 'value', direction: 'desc', page: 1, search: 'Pink' },
    })
    expect(getMock).toHaveBeenCalledWith('/collection/search', { query: { q: 'Pink' } })
  })

  it('navigates to a release when a suggestion is selected', async () => {
    vi.useFakeTimers()
    getMock.mockImplementation((path: string) => {
      if (path === '/collection') return Promise.resolve(collectionResponse())
      if (path === '/collection/search') {
        return Promise.resolve({ data: [{ id: 9, discogs_id: 999, title: 'Dark Side', artist: 'Pink Floyd', thumb: null }] })
      }
      throw new Error(`unexpected path ${path}`)
    })

    const wrapper = await mountCollection()

    await wrapper.get('input[type="text"]').setValue('Pink Fl')
    await vi.advanceTimersByTimeAsync(150)
    await flushPromises()

    await wrapper.get('button.w-full.flex.items-center').trigger('click')

    expect(navigateToMock).toHaveBeenCalledWith('/collection/999')
  })

  it('loads more releases on click', async () => {
    mockCollection({
      meta: { current_page: 1, from: 1, last_page: 2, path: '/collection', per_page: 1, to: 1, total: 2 },
    })

    const wrapper = await mountCollection()

    expect(wrapper.findAll('a[href^="/collection/"]')).toHaveLength(1)

    getMock.mockImplementation((path: string) => {
      if (path === '/collection') {
        return Promise.resolve(
          collectionResponse({
            data: [release({ id: 2, discogs_id: 222, title: 'Electric Pulse' })],
            meta: { current_page: 2, from: 2, last_page: 2, path: '/collection', per_page: 1, to: 2, total: 2 },
          }),
        )
      }
      throw new Error(`unexpected path ${path}`)
    })

    const loadMoreButton = wrapper.findAll('button').find((b) => b.text() === 'Load more')!
    await loadMoreButton.trigger('click')
    await flushPromises()

    expect(wrapper.findAll('a[href^="/collection/"]')).toHaveLength(2)
    expect(wrapper.text()).toContain('Electric Pulse')
  })

  it('surfaces an API failure with a retry', async () => {
    getMock.mockRejectedValueOnce(new Error('Network Error'))

    const wrapper = await mountCollection()

    expect(wrapper.text()).toContain("Couldn't load the collection")

    mockCollection()
    await wrapper.get('[role="alert"] button').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Chill Sessions')
  })
})
