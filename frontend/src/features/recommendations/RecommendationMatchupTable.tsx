import type { MatchupView } from '../../shared/types/api'

type RecommendationMatchupTableProps = {
  matchups: MatchupView[]
}

export function RecommendationMatchupTable({ matchups }: RecommendationMatchupTableProps) {
  return (
    <div className="table-scroll">
      <table className="matchup-table">
        <thead>
          <tr>
            <th>Opponent</th>
            <th>Best type</th>
            <th>Category</th>
            <th>Mult. ×100</th>
            <th>Selected</th>
          </tr>
        </thead>
        <tbody>
          {matchups.map((m, idx) => (
            <tr key={`${m.opponentSourceKey}-${idx}`}>
              <td>{m.opponentSourceKey}</td>
              <td>{m.bestAttackTypeCode}</td>
              <td>{m.bestAttackCategory}</td>
              <td>{m.typeMultiplierX100}</td>
              <td>{m.selectedScore.toFixed(2)}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}
