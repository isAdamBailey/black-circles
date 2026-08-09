import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import HomePage from '~/pages/index.vue'
import type { HomeData } from '~/types/api'

const { getMock, navigateToMock } = vi.hoisted(() => ({
  getMock: vi.fn(),
  navigateToMock: vi.fn(),
}))

mockNuxtImport('useApi', () => () => ({ get: getMock, post: vi.fn() }))
mockNuxtImport('navigateTo', () => navigateToMock)

const homeData: HomeData = {
  moods: [
    { slug: 'chill', label: 'Chill', emoji: '🌙' },
    { slug: 'dark', label: 'Dark', emoji: '🖤' },
  ],
  username: 'adam',
  insight: 'A listener who values depth and atmosphere.',
}

function resolveHomeWith(data: Partial<HomeData> = {}) {
  getMock.mockResolvedValue({ data: { ...homeData, ...data } })
}

async function mountHome() {
  const wrapper = await mountSuspended(HomePage)
  await flushPromises()

  return wrapper
}

describe('home page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    clearNuxtData('home')
  })

  it('renders the mood grid, action row, and personality insight', async () => {
    resolveHomeWith()

    const wrapper = await mountHome()

    expect(getMock).toHaveBeenCalledWith('/home')

    const moodLinks = wrapper.findAll('a[href^="/mood/"]')
    expect(moodLinks).toHaveLength(2)
    expect(moodLinks[0]!.attributes('href')).toBe('/mood/chill')
    expect(moodLinks[0]!.text()).toContain('Chill')

    expect(wrapper.find('a[href="/random"]').exists()).toBe(true)
    expect(wrapper.find('a[href="/collection"]').exists()).toBe(true)

    expect(wrapper.text()).toContain("Adam's music personality")
    expect(wrapper.text()).toContain('A listener who values depth and atmosphere.')
  })

  it('hides and shows the mood section', async () => {
    resolveHomeWith()

    const wrapper = await mountHome()

    expect(wrapper.find('#mood-section-panel').exists()).toBe(true)

    await wrapper.get('button[aria-controls="mood-section-panel"]').trigger('click')

    expect(wrapper.find('#mood-section-panel').exists()).toBe(false)
  })

  it('navigates to the vibe result with the trimmed prompt', async () => {
    resolveHomeWith()

    const wrapper = await mountHome()
    const submit = wrapper.get('button[type="submit"]')

    expect(submit.attributes('disabled')).toBeDefined()

    await wrapper.get('input[type="text"]').setValue('  dark moody post-punk  ')
    expect(submit.attributes('disabled')).toBeUndefined()

    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(navigateToMock).toHaveBeenCalledWith({
      path: '/vibe',
      query: { prompt: 'dark moody post-punk' },
    })
  })

  it('keeps the submit button disabled for a prompt below the API minimum', async () => {
    resolveHomeWith()

    const wrapper = await mountHome()

    await wrapper.get('input[type="text"]').setValue('ab')
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(wrapper.get('button[type="submit"]').attributes('disabled')).toBeDefined()
    expect(navigateToMock).not.toHaveBeenCalled()
  })

  it('shows the sync prompt when no collection has been synced', async () => {
    resolveHomeWith({ username: null, insight: '' })

    const wrapper = await mountHome()

    expect(wrapper.text()).toContain('No collection synced yet')
    expect(wrapper.find('form').exists()).toBe(false)
    expect(wrapper.find('a[href="/random"]').exists()).toBe(false)
    expect(wrapper.text()).not.toContain("Adam's music personality")
  })

  it('surfaces an API failure with a retry', async () => {
    getMock.mockRejectedValueOnce(new Error('Network Error'))

    const wrapper = await mountHome()

    expect(wrapper.text()).toContain("Couldn't load the collection")

    resolveHomeWith()
    await wrapper.get('[role="alert"] button').trigger('click')
    await flushPromises()

    expect(wrapper.findAll('a[href^="/mood/"]')).toHaveLength(2)
  })
})
