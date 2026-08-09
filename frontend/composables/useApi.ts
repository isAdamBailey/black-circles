import type { FetchOptions } from 'ofetch'

/**
 * Typed $fetch wrapper against the Laravel API. Response shapes come straight
 * from the Laravel Resources (see types/api.ts) — most single-resource
 * endpoints return `ApiEnvelope<T>` (a `data` key), while the paginated
 * collection endpoint merges pagination/meta alongside `data` at the top
 * level, so callers type each call with the exact shape they expect rather
 * than having a single generic unwrap here.
 */
export function useApi() {
  const config = useRuntimeConfig()

  function get<T>(path: string, options: FetchOptions = {}): Promise<T> {
    return $fetch<T>(path, {
      baseURL: config.public.apiBase,
      ...options,
      method: 'GET',
    })
  }

  function post<T>(path: string, body?: Record<string, unknown>, options: FetchOptions = {}): Promise<T> {
    return $fetch<T>(path, {
      baseURL: config.public.apiBase,
      ...options,
      method: 'POST',
      body,
    })
  }

  return { get, post }
}
