import type { RecommendationView } from '../../shared/types/api'
import { EmptyState } from '../../shared/ui/EmptyState'
import { RecommendationCard } from './RecommendationCard'

export type RecommendationListState = 'initial' | 'stale' | 'empty' | 'error' | 'ready'

type RecommendationListProps = {
  state: RecommendationListState
  items: RecommendationView[]
}

export function RecommendationList({ items, state }: RecommendationListProps) {
  if (state === 'initial') {
    return (
      <EmptyState
        title="No recommendations yet"
        description="Run an analysis with a non-empty opponent team."
      />
    )
  }

  if (state === 'stale') {
    return (
      <EmptyState
        title="Analysis out of date"
        description="Parameters changed. Run analysis again."
      />
    )
  }

  if (state === 'error') {
    return (
      <EmptyState
        title="Analysis unavailable"
        description="Fix the request and run analysis again."
      />
    )
  }

  if (state === 'empty') {
    return (
      <EmptyState
        title="No recommendations found"
        description="Try broadening the filters or changing the opponent team."
      />
    )
  }

  return (
    <div className="recommendation-list">
      {items.map((entry, idx) => (
        <RecommendationCard key={`${entry.sourceKey}-${idx}`} entry={entry} rank={idx + 1} />
      ))}
    </div>
  )
}
