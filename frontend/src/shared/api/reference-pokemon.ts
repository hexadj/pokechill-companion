import type { ListReferencePokemonResponse } from '../types/api'
import { apiGetJson } from './client'

export type ListReferencePokemonParams = {
  search?: string
  limit?: number
}

export async function listReferencePokemon(
  params: ListReferencePokemonParams,
): Promise<ListReferencePokemonResponse> {
  const q = new URLSearchParams()
  if (params.search !== undefined && params.search !== '') {
    q.set('search', params.search)
  }
  if (params.limit !== undefined) {
    q.set('limit', String(params.limit))
  }
  const query = q.toString()
  const path = query === '' ? '/api/v1/reference/pokemon' : `/api/v1/reference/pokemon?${query}`
  return apiGetJson<ListReferencePokemonResponse>(path)
}
