import { describe, expect, it } from 'vitest'
import { mountSuspended } from '@nuxt/test-utils/runtime'
import DefaultLayout from '~/layouts/default.vue'

describe('default layout', () => {
  it('renders the brand, collection link, and footer', async () => {
    const wrapper = await mountSuspended(DefaultLayout)

    expect(wrapper.find('a[href="/"]').text()).toContain('Black Circles')
    expect(wrapper.findAll('a[href="/collection"]').length).toBeGreaterThan(0)
    expect(wrapper.find('a[href="https://adambailey.io"]').text()).toContain('Adam Bailey')
  })

  it('renders page content in the default slot', async () => {
    const wrapper = await mountSuspended(DefaultLayout, {
      slots: { default: () => '<p>Page body</p>' },
    })

    expect(wrapper.find('main').text()).toContain('Page body')
  })

  it('marks the collection link inactive on the home route', async () => {
    const wrapper = await mountSuspended(DefaultLayout)

    const desktopLink = wrapper.get('.hidden.sm\\:flex a[href="/collection"]')
    expect(desktopLink.classes()).toContain('text-label')
    expect(desktopLink.classes()).not.toContain('text-pressing')
  })
})
