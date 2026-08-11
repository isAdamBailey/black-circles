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

function pageMeta(currentPage: number, lastPage: number): CollectionIndex['meta'] {
  return {
    current_page: currentPage,
    from: currentPage,
    last_page: lastPage,
    path: '/collection',
    per_page: 1,
    to: currentPage,
    total: lastPage,
  }
}

/**
 * Infinite scroll is driven by an IntersectionObserver on a sentinel below the
 * grid, so the tests install a fake observer they can fire by hand — happy-dom
 * never scrolls and would never intersect anything on its own.
 */
type FakeObserver = {
  callback: IntersectionObserverCallback
  elements: Set<Element>
  disconnected: boolean
  /** The intersection state the browser would currently report. */
  intersecting: boolean
}

let observers: FakeObserver[] = []

function deliver(observer: FakeObserver, target: Element, isIntersecting: boolean) {
  observer.callback([{ isIntersecting, target } as unknown as IntersectionObserverEntry], {} as IntersectionObserver)
}

function installFakeIntersectionObserver() {
  observers = []
  vi.stubGlobal(
    'IntersectionObserver',
    class {
      private record: FakeObserver

      constructor(callback: IntersectionObserverCallback) {
        this.record = { callback, elements: new Set(), disconnected: false, intersecting: false }
        observers.push(this.record)
      }

      observe(el: Element) {
        const record = this.record
        record.elements.add(el)
        // Per spec, observing queues an *initial* notification of the target's
        // current state — that re-delivery is what the page relies on to keep
        // paging while the sentinel stays on screen.
        queueMicrotask(() => {
          if (!record.disconnected && record.elements.has(el)) deliver(record, el, record.intersecting)
        })
      }

      unobserve(el: Element) {
        this.record.elements.delete(el)
      }

      disconnect() {
        this.record.elements.clear()
        this.record.disconnected = true
      }

      takeRecords() {
        return []
      }

      root = null
      rootMargin = ''
      thresholds = []
    },
  )
}

/**
 * Fire the sentinel observer as if the user scrolled it into (or out of) view.
 * NuxtLink's prefetcher builds its own IntersectionObserver over the grid's
 * `<a>` elements, so target the one watching the page's `<div>` sentinel.
 */
async function scrollSentinel(isIntersecting = true) {
  const observer = observers.find(
    (o) => !o.disconnected && o.elements.size > 0 && [...o.elements].every((el) => el.tagName !== 'A'),
  )
  if (!observer) throw new Error('no sentinel is being observed')

  observer.intersecting = isIntersecting
  for (const target of observer.elements) deliver(observer, target, isIntersecting)
  await flushPromises()
}

let mountedWrapper: Awaited<ReturnType<typeof mountSuspended>> | undefined

async function mountCollection() {
  const wrapper = await mountSuspended(CollectionIndexPage, { route: '/collection' })
  await flushPromises()

  mountedWrapper = wrapper
  return wrapper
}

async function mountCollectionAt(route: string) {
  const wrapper = await mountSuspended(CollectionIndexPage, { route })
  await flushPromises()

  mountedWrapper = wrapper
  return wrapper
}

describe('collection index page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    clearNuxtData('collection-index')
    installFakeIntersectionObserver()
  })

  afterEach(() => {
    mountedWrapper?.unmount()
    mountedWrapper = undefined
    vi.unstubAllGlobals()
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

  it('seeds the genre filter from the URL query (deep-linked from a release page)', async () => {
    mockCollection()

    await mountCollectionAt('/collection?genres[]=Rock')

    expect(getMock).toHaveBeenCalledWith('/collection', {
      query: { sort: 'value', direction: 'desc', page: 1, 'genres[]': ['Rock'] },
    })
  })

  it('debounces search input and fetches autocomplete suggestions', async () => {
    mockCollection()

    const wrapper = await mountCollection()

    await wrapper.get('input[type="text"]').setValue('Pink')
    await new Promise((resolve) => setTimeout(resolve, 450))
    await flushPromises()

    expect(getMock).toHaveBeenLastCalledWith('/collection', {
      query: { sort: 'value', direction: 'desc', page: 1, search: 'Pink' },
    })
    expect(getMock).toHaveBeenCalledWith('/collection/search', { query: { q: 'Pink' } })
  }, 10000)

  it('navigates to a release when a suggestion is selected', async () => {
    getMock.mockImplementation((path: string) => {
      if (path === '/collection') return Promise.resolve(collectionResponse())
      if (path === '/collection/search') {
        return Promise.resolve({ data: [{ id: 9, discogs_id: 999, title: 'Dark Side', artist: 'Pink Floyd', thumb: null }] })
      }
      throw new Error(`unexpected path ${path}`)
    })

    const wrapper = await mountCollection()

    await wrapper.get('input[type="text"]').setValue('Pink Fl')
    await new Promise((resolve) => setTimeout(resolve, 200))
    await flushPromises()

    await wrapper.get('button.w-full.flex.items-center').trigger('click')

    expect(navigateToMock).toHaveBeenCalledWith('/collection/999')
  }, 10000)

  it('loads the next page when the sentinel scrolls into view', async () => {
    mockCollection({ meta: pageMeta(1, 2) })

    const wrapper = await mountCollection()

    expect(wrapper.findAll('a[href^="/collection/"]')).toHaveLength(1)
    expect(wrapper.findAll('button').some((b) => b.text() === 'Load more')).toBe(false)
    expect(wrapper.text()).toContain('Loading more records…')

    getMock.mockImplementation((path: string) => {
      if (path === '/collection') {
        return Promise.resolve(
          collectionResponse({
            data: [release({ id: 2, discogs_id: 222, title: 'Electric Pulse' })],
            meta: pageMeta(2, 2),
          }),
        )
      }
      throw new Error(`unexpected path ${path}`)
    })

    await scrollSentinel()

    expect(getMock).toHaveBeenLastCalledWith('/collection', {
      query: { sort: 'value', direction: 'desc', page: 2 },
    })
    expect(wrapper.findAll('a[href^="/collection/"]')).toHaveLength(2)
    expect(wrapper.text()).toContain('Electric Pulse')
  })

  it('keeps loading while the sentinel stays in view, then reports the end of the collection', async () => {
    mockCollection({ meta: pageMeta(1, 3) })

    const wrapper = await mountCollection()

    getMock.mockImplementation((path: string, options: { query: { page: number } }) => {
      if (path === '/collection') {
        const page = options.query.page
        return Promise.resolve(
          collectionResponse({
            data: [release({ id: page, discogs_id: page * 111, title: `Record ${page}` })],
            meta: pageMeta(page, 3),
          }),
        )
      }
      throw new Error(`unexpected path ${path}`)
    })

    // A single intersection: the sentinel never leaves the viewport, so the
    // page should chain through to the last page on its own.
    await scrollSentinel()
    await flushPromises()

    expect(wrapper.findAll('a[href^="/collection/"]')).toHaveLength(3)
    expect(wrapper.text()).toContain('Record 2')
    expect(wrapper.text()).toContain('Record 3')
    expect(wrapper.text()).not.toContain('Loading more records…')
    expect(wrapper.text()).toContain("That's all 3 records.")
    expect(wrapper.get('[role="status"]').text()).toBe('All 3 records loaded.')
  })

  it('announces scroll progress in a live region', async () => {
    mockCollection({ meta: pageMeta(1, 4) })

    const wrapper = await mountCollection()

    const status = () => wrapper.get('[role="status"]')
    expect(status().attributes('aria-live')).toBe('polite')
    expect(status().text()).toBe('Showing 1 of 4 records.')

    let resolvePageTwo: (value: CollectionIndex) => void = () => {}
    getMock.mockImplementation(
      () =>
        new Promise<CollectionIndex>((resolve) => {
          resolvePageTwo = resolve
        }),
    )

    await scrollSentinel()
    expect(status().text()).toBe('Loading more records…')

    // Scroll away so the append settles instead of immediately chaining page 3.
    await scrollSentinel(false)

    resolvePageTwo(
      collectionResponse({ data: [release({ id: 2, discogs_id: 222, title: 'Record 2' })], meta: pageMeta(2, 4) }),
    )
    await flushPromises()

    expect(status().text()).toBe('Showing 2 of 4 records.')
  })

  it('stops chaining once the sentinel scrolls back out of view', async () => {
    mockCollection({ meta: pageMeta(1, 5) })

    const wrapper = await mountCollection()

    let resolvePageTwo: (value: CollectionIndex) => void = () => {}
    const pageTwo = new Promise<CollectionIndex>((resolve) => {
      resolvePageTwo = resolve
    })
    getMock.mockImplementation((path: string) => {
      if (path === '/collection') return pageTwo
      throw new Error(`unexpected path ${path}`)
    })
    const callsBeforeScroll = getMock.mock.calls.length

    // Page 2 is still in flight when the user scrolls the sentinel away, so
    // the chain must not keep pulling pages 3, 4, 5 behind their back.
    await scrollSentinel(true)
    await scrollSentinel(false)

    resolvePageTwo(
      collectionResponse({
        data: [release({ id: 2, discogs_id: 222, title: 'Record 2' })],
        meta: pageMeta(2, 5),
      }),
    )
    await flushPromises()

    expect(wrapper.findAll('a[href^="/collection/"]')).toHaveLength(2)
    expect(getMock.mock.calls.length - callsBeforeScroll).toBe(1)
  })

  it('offers a retry when loading the next page fails', async () => {
    mockCollection({ meta: pageMeta(1, 2) })

    const wrapper = await mountCollection()

    getMock.mockRejectedValueOnce(new Error('Network Error'))
    await scrollSentinel()

    expect(wrapper.text()).toContain("Couldn't load more records.")
    const callsAfterFailure = getMock.mock.calls.length

    // A failed page must not be retried automatically by further intersections.
    await scrollSentinel()
    expect(getMock.mock.calls.length).toBe(callsAfterFailure)

    getMock.mockImplementation((path: string) => {
      if (path === '/collection') {
        return Promise.resolve(
          collectionResponse({
            data: [release({ id: 2, discogs_id: 222, title: 'Electric Pulse' })],
            meta: pageMeta(2, 2),
          }),
        )
      }
      throw new Error(`unexpected path ${path}`)
    })

    await wrapper.findAll('button').find((b) => b.text() === 'Try again')!.trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Electric Pulse')
    expect(wrapper.text()).not.toContain("Couldn't load more records.")
  })

  it('falls back to a manual button when IntersectionObserver is unavailable', async () => {
    vi.stubGlobal('IntersectionObserver', undefined)
    mockCollection({ meta: pageMeta(1, 2) })

    const wrapper = await mountCollection()

    // The page has done its feature detection by now; NuxtLink prefetches on an
    // idle callback and would throw asynchronously without a constructor, so
    // put one back before any timer runs.
    installFakeIntersectionObserver()

    const loadMoreButton = wrapper.findAll('button').find((b) => b.text() === 'Load more')
    expect(loadMoreButton).toBeDefined()

    getMock.mockImplementation((path: string) => {
      if (path === '/collection') {
        return Promise.resolve(
          collectionResponse({
            data: [release({ id: 2, discogs_id: 222, title: 'Electric Pulse' })],
            meta: pageMeta(2, 2),
          }),
        )
      }
      throw new Error(`unexpected path ${path}`)
    })

    await loadMoreButton!.trigger('click')
    await flushPromises()

    expect(wrapper.findAll('a[href^="/collection/"]')).toHaveLength(2)
    expect(wrapper.text()).toContain('Electric Pulse')
  })

  it('discards an in-flight page when the filters change underneath it', async () => {
    mockCollection({ meta: pageMeta(1, 2) })

    const wrapper = await mountCollection()

    let resolvePageTwo: (value: CollectionIndex) => void = () => {}
    const pageTwo = new Promise<CollectionIndex>((resolve) => {
      resolvePageTwo = resolve
    })

    getMock.mockImplementation((path: string, options: { query: { page: number } }) => {
      if (path === '/collection') {
        if (options.query.page === 2) return pageTwo
        return Promise.resolve(
          collectionResponse({ data: [release({ id: 7, discogs_id: 777, title: 'Sorted By Title' })], meta: pageMeta(1, 1) }),
        )
      }
      throw new Error(`unexpected path ${path}`)
    })

    await scrollSentinel()
    await wrapper.get('select').setValue('title')
    await flushPromises()

    resolvePageTwo(
      collectionResponse({
        data: [release({ id: 2, discogs_id: 222, title: 'Stale Page Two' })],
        meta: pageMeta(2, 2),
      }),
    )
    await flushPromises()

    expect(wrapper.text()).toContain('Sorted By Title')
    expect(wrapper.text()).not.toContain('Stale Page Two')
    expect(wrapper.findAll('a[href^="/collection/"]')).toHaveLength(1)
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
