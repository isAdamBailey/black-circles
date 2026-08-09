export interface Mood {
  slug: string
  label: string
  emoji: string
}

export interface CollectionItemSummary {
  rating: number
  date_added: string
}

export interface ReleaseSummary {
  id: number
  discogs_id: number
  title: string
  artist: string
  cover_image: string | null
  thumb: string | null
  year: number | null
  lowest_price: string | null
  genres?: string[]
  styles?: string[]
  collection_item?: CollectionItemSummary | null
}

export interface Release {
  discogs_id: number
  title: string
  artist: string
  label: string | null
  catalog_number: string | null
  year: number | null
  cover_image: string | null
  thumb: string | null
  images: unknown
  formats: unknown
  tracklist: unknown
  videos: unknown
  lowest_price: string | null
  median_price: string | null
  highest_price: string | null
  discogs_uri: string | null
  notes: string | null
  genres?: string[]
  styles?: string[]
  collection_item?: CollectionItemSummary | null
}

export interface HomeData {
  moods: Mood[]
  username: string | null
  insight: string
}

export interface Suggestion {
  mood: Mood | null
  primary: ReleaseSummary
  backups: ReleaseSummary[]
}

export interface CollectionFilters {
  search?: string
  genres?: string | string[]
  styles?: string | string[]
  sort: string
  direction: string
}

export interface PaginationLinks {
  first: string | null
  last: string | null
  prev: string | null
  next: string | null
}

export interface PaginationMeta {
  current_page: number
  from: number | null
  last_page: number
  path: string
  per_page: number
  to: number | null
  total: number
}

export interface CollectionIndex {
  data: ReleaseSummary[]
  links: PaginationLinks
  meta: PaginationMeta
  filters: CollectionFilters
  allGenres: string[]
  allStyles: string[]
  username: string | null
  lastSynced: string | null
}

export interface SearchSuggestion {
  id: number
  discogs_id: number
  title: string
  artist: string
  thumb: string | null
}

export interface ApiEnvelope<T> {
  data: T
}

export interface ApiError {
  message: string
  errors?: Record<string, string[]>
}
