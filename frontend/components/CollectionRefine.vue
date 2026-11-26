<script setup lang="ts">
type Direction = 'asc' | 'desc'

const props = defineProps<{
  allGenres: string[]
  allStyles: string[]
  /** Records matching the current filters, for the mobile "Show N" action. */
  total?: number
  /** The applied search term, so the heading names everything narrowing the grid. */
  search?: string
}>()

const sort = defineModel<string>('sort', { required: true })
const direction = defineModel<Direction>('direction', { required: true })
const genres = defineModel<string[]>('genres', { required: true })
const styles = defineModel<string[]>('styles', { required: true })

const sortOptions: { sort: string, direction: Direction, label: string }[] = [
  { sort: 'value', direction: 'desc', label: 'Price: high to low' },
  { sort: 'value', direction: 'asc', label: 'Price: low to high' },
  { sort: 'date_added', direction: 'desc', label: 'Recently added' },
  { sort: 'date_added', direction: 'asc', label: 'First added' },
  { sort: 'artist', direction: 'asc', label: 'Artist A–Z' },
  { sort: 'artist', direction: 'desc', label: 'Artist Z–A' },
  { sort: 'title', direction: 'asc', label: 'Title A–Z' },
  { sort: 'title', direction: 'desc', label: 'Title Z–A' },
  { sort: 'year', direction: 'desc', label: 'Year: newest' },
  { sort: 'year', direction: 'asc', label: 'Year: oldest' },
]

const sortKey = computed({
  get: () => `${sort.value}:${direction.value}`,
  set: (key: string) => {
    const [s = 'value', d = 'desc'] = key.split(':')
    sort.value = s
    direction.value = d as Direction
  },
})

const sortLabel = computed(
  () => sortOptions.find((o) => o.sort === sort.value && o.direction === direction.value)?.label ?? 'Sorted',
)

const activeFilters = computed(() => [...genres.value, ...styles.value])

const filterSummary = computed(() => {
  const names = activeFilters.value
  const tags = names.length <= 2 ? names.join(', ') : `${names[0]}, ${names[1]} +${names.length - 2}`
  const query = props.search?.trim()
  if (query && tags) return `“${query}” in ${tags}`
  if (query) return `Matching “${query}”`
  return tags || 'All records'
})

function toggled(list: string[], value: string) {
  return list.includes(value) ? list.filter((v) => v !== value) : [...list, value]
}

function clearFilters() {
  genres.value = []
  styles.value = []
}

const styleQuery = ref('')
const visibleStyles = computed(() => {
  const q = styleQuery.value.trim().toLowerCase()
  return q ? props.allStyles.filter((s) => s.toLowerCase().includes(q)) : props.allStyles
})

const open = ref(false)
const rootRef = ref<HTMLElement | null>(null)
const triggerRef = ref<HTMLButtonElement | null>(null)
const panelRef = ref<HTMLElement | null>(null)
const panelId = useId()

async function openPanel() {
  open.value = true
  await nextTick()
  const panel = panelRef.value
  ;(panel?.querySelector<HTMLElement>('input:checked') ?? panel?.querySelector<HTMLElement>('input'))?.focus()
}

function closePanel(returnFocus = true) {
  if (!open.value) return
  open.value = false
  styleQuery.value = ''
  if (returnFocus) triggerRef.value?.focus()
}

// Below the sm breakpoint the panel is a modal bottom sheet; above it, a popover.
const isSheet = ref(false)
let sheetQuery: MediaQueryList | undefined
const syncSheet = () => {
  isSheet.value = !!sheetQuery?.matches
}
onMounted(() => {
  sheetQuery = window.matchMedia('(max-width: 639px)')
  syncSheet()
  sheetQuery.addEventListener('change', syncSheet)
})

function trapFocus(event: KeyboardEvent) {
  const focusable = panelRef.value?.querySelectorAll<HTMLElement>(
    'button:not([disabled]), input:not([type="radio"]), input[type="radio"]:checked',
  )
  if (!focusable?.length) return
  const first = focusable[0]!
  const last = focusable[focusable.length - 1]!
  if (event.shiftKey && document.activeElement === first) {
    event.preventDefault()
    last.focus()
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault()
    first.focus()
  }
}

function onKeydown(event: KeyboardEvent) {
  if (!open.value) return
  if (event.key === 'Escape') {
    event.preventDefault()
    closePanel()
  } else if (event.key === 'Tab' && isSheet.value) {
    trapFocus(event)
  }
}

// As a desktop popover, tabbing out of it closes it quietly. The sheet traps focus instead.
function onFocusOut(event: FocusEvent) {
  if (isSheet.value) return
  const next = event.relatedTarget as Node | null
  if (next && rootRef.value && !rootRef.value.contains(next)) closePanel(false)
}

// The sheet covers the page, so the page behind it shouldn't scroll.
watch([open, isSheet], ([isOpen, sheet]) => {
  document.documentElement.style.overflow = isOpen && sheet ? 'hidden' : ''
})

onUnmounted(() => {
  sheetQuery?.removeEventListener('change', syncSheet)
  document.documentElement.style.overflow = ''
})

const resultLabel = computed(() => {
  if (props.total == null) return 'Show records'
  return `Show ${props.total} ${props.total === 1 ? 'record' : 'records'}`
})
</script>

<template>
  <div ref="rootRef" class="relative" @keydown="onKeydown" @focusout="onFocusOut">
    <div class="flex items-center gap-x-3 gap-y-1 flex-wrap">
      <h2 class="min-w-0">
        <button
          ref="triggerRef"
          type="button"
          class="group flex min-w-0 max-w-full items-center gap-2 py-1.5 text-left focus-visible:outline-none"
          :aria-expanded="open"
          :aria-controls="panelId"
          @click="open ? closePanel() : openPanel()"
        >
          <span class="sr-only">Sort and filter: </span>
          <span class="truncate text-lg font-semibold text-pressing decoration-jacket underline-offset-4 group-hover:underline group-focus-visible:underline group-focus-visible:decoration-2 group-focus-visible:decoration-pressing">{{ filterSummary }}</span>
          <span class="text-jacket" aria-hidden="true">·</span>
          <span class="shrink-0 text-sm text-label group-hover:text-sleeve">{{ sortLabel }}</span>
          <svg
            class="h-4 w-4 shrink-0 text-label group-hover:text-pressing transition-transform duration-200 ease-out"
            :class="open && 'rotate-180'"
            viewBox="0 0 20 20"
            fill="none"
            stroke="currentColor"
            stroke-width="1.75"
            aria-hidden="true"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="m5 7.5 5 5 5-5" />
          </svg>
        </button>
      </h2>
      <button
        v-if="activeFilters.length"
        type="button"
        class="text-sm text-label underline-offset-4 hover:text-pressing hover:underline"
        @click="clearFilters"
      >
        Clear filters
      </button>
    </div>

    <Transition
      enter-active-class="motion-safe:transition-opacity duration-200 ease-out"
      leave-active-class="motion-safe:transition-opacity duration-150 ease-in"
      enter-from-class="opacity-0"
      leave-to-class="opacity-0"
    >
      <div
        v-if="open"
        class="fixed inset-0 z-[60] bg-void/75 sm:bg-transparent"
        aria-hidden="true"
        @click="closePanel()"
      />
    </Transition>

    <Transition
      enter-active-class="motion-safe:transition duration-250 ease-[cubic-bezier(0.16,1,0.3,1)]"
      leave-active-class="motion-safe:transition duration-150 ease-in"
      enter-from-class="translate-y-full opacity-0 sm:translate-y-1"
      leave-to-class="translate-y-full opacity-0 sm:translate-y-1"
    >
      <div
        v-if="open"
        :id="panelId"
        ref="panelRef"
        role="dialog"
        :aria-modal="isSheet || undefined"
        aria-label="Sort and filter"
        class="fixed inset-x-0 bottom-0 z-[70] flex max-h-[85dvh] flex-col rounded-t-xl border-t border-groove bg-cabinet sm:absolute sm:inset-x-auto sm:bottom-auto sm:left-0 sm:top-full sm:mt-2 sm:max-h-[min(34rem,55vh)] sm:w-[min(52rem,calc(100vw-3rem))] sm:rounded-xl sm:border"
      >
        <div class="flex items-center justify-between px-5 pt-4 pb-2 sm:hidden">
          <p class="text-base font-semibold text-pressing">Sort and filter</p>
          <button
            type="button"
            class="-mr-2 rounded-md p-2 text-label hover:bg-shelf hover:text-pressing"
            aria-label="Close"
            @click="closePanel()"
          >
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
              <path stroke-linecap="round" d="m5 5 10 10M15 5 5 15" />
            </svg>
          </button>
        </div>

        <div class="grid min-h-0 flex-1 grid-cols-1 overflow-y-auto sm:grid-cols-[13rem_1fr] sm:overflow-hidden">
          <fieldset class="border-b border-shelf px-5 pt-2 pb-5 sm:overflow-y-auto sm:border-b-0 sm:border-r sm:pt-5">
            <legend class="float-left mb-3 w-full text-xs font-semibold uppercase tracking-wider text-label">Sort</legend>
            <div class="grid clear-both grid-cols-2 gap-1 sm:grid-cols-1">
              <label
                v-for="opt in sortOptions"
                :key="`${opt.sort}:${opt.direction}`"
                class="flex cursor-pointer items-center gap-2.5 rounded-md px-2 py-2 text-sm transition-colors has-[:checked]:bg-shelf has-[:checked]:text-pressing text-label hover:text-pressing has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-1 has-[:focus-visible]:outline-groove"
              >
                <input
                  v-model="sortKey"
                  type="radio"
                  name="collection-sort"
                  :value="`${opt.sort}:${opt.direction}`"
                  class="peer sr-only"
                >
                <span
                  class="grid h-3.5 w-3.5 shrink-0 place-items-center rounded-full border border-jacket peer-checked:border-pressing after:h-1.5 after:w-1.5 after:rounded-full after:bg-pressing after:opacity-0 peer-checked:after:opacity-100"
                  aria-hidden="true"
                />
                {{ opt.label }}
              </label>
            </div>
            <p class="mt-3 px-2 text-xs text-label text-pretty">Price is each record&apos;s lowest listing on Discogs.</p>
          </fieldset>

          <div class="flex min-h-0 flex-col gap-6 px-5 py-5 sm:overflow-y-auto">
            <section v-if="allGenres.length" aria-labelledby="refine-genres">
              <h3 id="refine-genres" class="mb-3 text-xs font-semibold uppercase tracking-wider text-label">
                Genre
              </h3>
              <div class="flex flex-wrap gap-1.5">
                <button
                  v-for="genre in allGenres"
                  :key="genre"
                  type="button"
                  class="rounded-md px-2.5 py-1.5 text-xs font-medium transition-colors"
                  :class="
                    genres.includes(genre)
                      ? 'bg-pressing text-void hover:bg-sleeve'
                      : 'bg-shelf text-label hover:bg-groove hover:text-pressing'
                  "
                  :aria-pressed="genres.includes(genre)"
                  @click="genres = toggled(genres, genre)"
                >
                  {{ genre }}
                </button>
              </div>
            </section>

            <section v-if="allStyles.length" aria-labelledby="refine-styles">
              <div class="mb-3 flex items-center justify-between gap-3">
                <h3 id="refine-styles" class="text-xs font-semibold uppercase tracking-wider text-label">Style</h3>
                <input
                  v-if="allStyles.length > 16"
                  v-model="styleQuery"
                  type="search"
                  placeholder="Find a style"
                  aria-label="Find a style"
                  class="w-36 rounded-md border border-groove bg-void px-2.5 py-1 text-xs text-pressing placeholder-label focus:border-dust focus:outline-none"
                >
              </div>
              <div class="flex flex-wrap gap-1.5">
                <button
                  v-for="style in visibleStyles"
                  :key="style"
                  type="button"
                  class="rounded-md px-2.5 py-1.5 text-xs font-medium transition-colors"
                  :class="
                    styles.includes(style)
                      ? 'bg-pressing text-void hover:bg-sleeve'
                      : 'bg-shelf text-label hover:bg-groove hover:text-pressing'
                  "
                  :aria-pressed="styles.includes(style)"
                  @click="styles = toggled(styles, style)"
                >
                  {{ style }}
                </button>
                <p v-if="visibleStyles.length === 0" class="text-xs text-label">
                  No styles match &ldquo;{{ styleQuery }}&rdquo;.
                </p>
              </div>
            </section>
          </div>
        </div>

        <div class="flex items-center justify-between gap-3 border-t border-shelf px-5 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
          <button
            type="button"
            class="text-sm text-label hover:text-pressing disabled:pointer-events-none disabled:opacity-50"
            :disabled="activeFilters.length === 0"
            @click="clearFilters"
          >
            Clear filters
          </button>
          <button
            type="button"
            class="rounded-md bg-pressing px-5 py-2.5 text-sm font-semibold text-void transition-colors hover:bg-sleeve focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-groove"
            @click="closePanel()"
          >
            <span class="sm:hidden">{{ resultLabel }}</span>
            <span class="hidden sm:inline">Done</span>
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>
