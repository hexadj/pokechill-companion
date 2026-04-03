import { PokemonSprite } from '../../shared/pokemon/PokemonSprite'
import { TypeBadge } from '../../shared/pokemon/TypeBadge'
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
        <div className="recommendation-card-sprite-wrap">
          <PokemonSprite sourceKey={entry.sourceKey} name={entry.name} variant="recommendation" />
        </div>
        <div className="recommendation-title">
          <div className="recommendation-name">{entry.name}</div>
          <div className="recommendation-meta">
            <div className="type-badge-row">
              <TypeBadge typeCode={entry.primaryTypeCode} />
              {entry.secondaryTypeCode ? <TypeBadge typeCode={entry.secondaryTypeCode} /> : null}
            </div>
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
