import type { RecommendationView } from '../../shared/types/api'
import { RecommendationMatchupTable } from './RecommendationMatchupTable'

type RecommendationCardProps = {
  entry: RecommendationView
  rank: number
}

export function RecommendationCard({ entry, rank }: RecommendationCardProps) {
  return (
    <article className="recommendation-card">
      <header className="recommendation-card-header">
        <div className="recommendation-rank">#{rank}</div>
        <div className="recommendation-title">
          <div className="recommendation-name">{entry.name}</div>
          <div className="recommendation-meta muted">
            {entry.primaryTypeCode}
            {entry.secondaryTypeCode ? ` · ${entry.secondaryTypeCode}` : ''}
          </div>
        </div>
        <div className="recommendation-score">{entry.score.toFixed(2)}</div>
      </header>
      <details className="recommendation-details">
        <summary>Matchup details</summary>
        <RecommendationMatchupTable matchups={entry.matchups} />
      </details>
    </article>
  )
}
