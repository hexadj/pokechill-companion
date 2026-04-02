import type { RecommendationView } from '../../shared/types/api'
import { EmptyState } from '../../shared/ui/EmptyState'
import { RecommendationCard } from './RecommendationCard'

type RecommendationListProps = {
  items: RecommendationView[]
}

export function RecommendationList({ items }: RecommendationListProps) {
  if (items.length === 0) {
    return (
      <EmptyState
        title="No recommendations yet"
        description="Run an analysis with a non-empty opponent team."
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
