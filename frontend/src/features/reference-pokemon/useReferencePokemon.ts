import { useQuery } from '@tanstack/react-query'

import { listReferencePokemon } from '../../shared/api/reference-pokemon'

const REFERENCE_LIMIT = 30

export function useReferencePokemon(debouncedSearch: string) {
  return useQuery({
    queryKey: ['reference-pokemon', debouncedSearch, REFERENCE_LIMIT],
    queryFn: () =>
      listReferencePokemon({
        search: debouncedSearch.trim() === '' ? undefined : debouncedSearch.trim(),
        limit: REFERENCE_LIMIT,
      }),
    staleTime: 30_000,
  })
}
