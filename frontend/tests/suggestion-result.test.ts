import { describe, expect, it } from 'vitest'
import { mountSuspended } from '@nuxt/test-utils/runtime'
import SuggestionResult from '~/components/SuggestionResult.vue'
import type { ReleaseSummary } from '~/types/api'

const primary: ReleaseSummary = {
  id: 1,
  discogs_id: 111,
  title: 'Night Drive',
  artist: 'Nocturne',
  cover_image: 'https://example.com/cover.jpg',
  thumb: null,
  year: 1986,
  lowest_price: null,
  genres: ['Rock'],
  styles: ['Post-Punk'],
}

const backups: ReleaseSummary[] = [
  {
    id: 2,
    discogs_id: 222,
    title: 'Backup Album',
    artist: 'Backup Artist',
    cover_image: null,
    thumb: 'https://example.com/thumb.jpg',
    year: 1990,
    lowest_price: null,
  },
]

describe('SuggestionResult', () => {
  it('renders the primary pick, badges, and backups', async () => {
    const wrapper = await mountSuspended(SuggestionResult, {
      props: { emoji: '🌙', label: 'Chill', primary, backups, retrying: false },
    })

    expect(wrapper.text()).toContain('Chill')
    expect(wrapper.find('a[href="/collection/111"]').text()).toContain('Night Drive')
    expect(wrapper.text()).toContain('Nocturne')
    expect(wrapper.text()).toContain('Rock')
    expect(wrapper.text()).toContain('Post-Punk')

    expect(wrapper.find('a[href="/collection/222"]').text()).toContain('Backup Album')
  })

  it('emits retry when the button is clicked, and disables while retrying', async () => {
    const wrapper = await mountSuspended(SuggestionResult, {
      props: { emoji: '🌙', label: 'Chill', primary, backups, retrying: false },
    })

    await wrapper.get('button').trigger('click')
    expect(wrapper.emitted('retry')).toHaveLength(1)

    await wrapper.setProps({ retrying: true })
    expect(wrapper.get('button').attributes('disabled')).toBeDefined()
    expect(wrapper.get('button').text()).toBe('Finding...')
  })

  it('omits the backups section when there are none', async () => {
    const wrapper = await mountSuspended(SuggestionResult, {
      props: { emoji: '🌙', label: 'Chill', primary, backups: [], retrying: false },
    })

    expect(wrapper.text()).not.toContain("Also in Adam's collection")
  })

  it('shows a placeholder when no cover art is available', async () => {
    const noArt: ReleaseSummary = { ...primary, cover_image: null, thumb: null }
    const wrapper = await mountSuspended(SuggestionResult, {
      props: { emoji: '🌙', label: 'Chill', primary: noArt, backups: [], retrying: false },
    })

    expect(wrapper.find('a[href="/collection/111"] img').exists()).toBe(false)
    expect(wrapper.find('a[href="/collection/111"]').text()).toContain('⚫')
  })
})
