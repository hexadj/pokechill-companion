import { useMutation } from '@tanstack/react-query'

import { createRecommendations } from '../../shared/api/recommendations'
import type { RecommendationRequest } from '../../shared/types/api'

export function useRecommendationsMutation() {
  return useMutation({
    mutationFn: (body: RecommendationRequest) => createRecommendations(body),
    retry: 0,
  })
}
