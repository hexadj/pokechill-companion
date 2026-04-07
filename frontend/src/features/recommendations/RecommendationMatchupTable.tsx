import type { MatchupView } from '../../shared/types/api'

type RecommendationMatchupTableProps = {
  matchups: MatchupView[]
}

function formatAttackCategory(category: string) {
  if (category.length === 0) {
    return category
  }

  return `${category.charAt(0).toUpperCase()}${category.slice(1).toLowerCase()}`
}

export function RecommendationMatchupTable({ matchups }: RecommendationMatchupTableProps) {
  return (
    <>
      <div className="table-scroll matchup-table-desktop">
        <table className="matchup-table">
          <thead>
            <tr>
              <th>Opponent</th>
              <th>Best type</th>
              <th>Category</th>
              <th>Mult. x100</th>
              <th>Selected</th>
            </tr>
          </thead>
          <tbody>
            {matchups.map((m, idx) => (
              <tr key={`${m.opponentSourceKey}-${idx}`}>
                <td>{m.opponentSourceKey}</td>
                <td>{m.bestAttackTypeCode}</td>
                <td>{formatAttackCategory(m.bestAttackCategory)}</td>
                <td>{m.typeMultiplierX100}</td>
                <td>{m.selectedScore.toFixed(2)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <div className="matchup-cards" aria-label="Matchup details">
        {matchups.map((m, idx) => (
          <article key={`${m.opponentSourceKey}-${idx}`} className="matchup-card">
            <div className="matchup-card-opponent">{m.opponentSourceKey}</div>
            <dl className="matchup-card-metrics">
              <div className="matchup-card-row">
                <dt>Best attack</dt>
                <dd>
                  {m.bestAttackTypeCode} / {formatAttackCategory(m.bestAttackCategory)}
                </dd>
              </div>
              <div className="matchup-card-row">
                <dt>Multiplier</dt>
                <dd>{m.typeMultiplierX100}</dd>
              </div>
              <div className="matchup-card-row">
                <dt>Selected score</dt>
                <dd>{m.selectedScore.toFixed(2)}</dd>
              </div>
            </dl>
          </article>
        ))}
      </div>
    </>
  )
}
