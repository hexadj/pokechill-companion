import type { RecommendationRequest, RecommendationResponse } from '../types/api'
import { apiPostJson } from './client'

export async function createRecommendations(
  body: RecommendationRequest,
): Promise<RecommendationResponse> {
  return apiPostJson<RecommendationResponse, RecommendationRequest>(
    '/api/v1/recommendations',
    body,
  )
}
