import { PokemonSprite } from '../../shared/pokemon/PokemonSprite'
import { TypeBadge } from '../../shared/pokemon/TypeBadge'
import type { RecommendationView } from '../../shared/types/api'
import { RecommendationMatchupTable } from './RecommendationMatchupTable'

const MAX_STARS = 6

function renderStars(rating: number): boolean[] {
  const safeRating = Math.min(MAX_STARS, Math.max(1, Math.round(rating)))
  return Array.from({ length: MAX_STARS }, (_, idx) => idx < safeRating)
}

function renderStarsNode(rating: number) {
  const stars = renderStars(rating)
  return (
    <>
      {stars.map((isFull, idx) => (
        <span key={idx} className={isFull ? 'stat-stars-full' : 'stat-stars-empty'}>
          {isFull ? '★' : '☆'}
        </span>
      ))}
    </>
  )
}

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
            <div className="recommendation-division muted">Division {entry.division}</div>
          </div>
        </div>
        <dl className="recommendation-stats" aria-label={`${entry.name} star stats`}>
          <div className="recommendation-stat-row">
            <dt>HP</dt>
            <dd className="stat-stars">{renderStarsNode(entry.hp)}</dd>
          </div>
          <div className="recommendation-stat-row">
            <dt>ATK</dt>
            <dd className="stat-stars">{renderStarsNode(entry.atk)}</dd>
          </div>
          <div className="recommendation-stat-row">
            <dt>DEF</dt>
            <dd className="stat-stars">{renderStarsNode(entry.def)}</dd>
          </div>
          <div className="recommendation-stat-row">
            <dt>SATK</dt>
            <dd className="stat-stars">{renderStarsNode(entry.satk)}</dd>
          </div>
          <div className="recommendation-stat-row">
            <dt>SDEF</dt>
            <dd className="stat-stars">{renderStarsNode(entry.sdef)}</dd>
          </div>
          <div className="recommendation-stat-row">
            <dt>SPE</dt>
            <dd className="stat-stars">{renderStarsNode(entry.spe)}</dd>
          </div>
        </dl>
      </header>
      <details className="recommendation-details">
        <summary>Matchup score : {entry.score.toFixed(2)}</summary>
        <RecommendationMatchupTable matchups={entry.matchups} />
      </details>
    </article>
  )
}
