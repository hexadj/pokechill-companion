import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { RecommendationMatchupTable } from './RecommendationMatchupTable'

describe('RecommendationMatchupTable', () => {
  it('renders desktop and mobile matchup views with normalized category labels', () => {
    const { container } = render(
      <RecommendationMatchupTable
        matchups={[
          {
            opponentSourceKey: 'abomasnow',
            bestAttackTypeCode: 'FIRE',
            bestAttackCategory: 'SpEcIaL',
            typeMultiplierX100: 400,
            physicalScore: 8.75,
            specialScore: 10.15,
            selectedScore: 10.15,
          },
          {
            opponentSourceKey: 'skarmory',
            bestAttackTypeCode: 'ELECTRIC',
            bestAttackCategory: 'physical',
            typeMultiplierX100: 200,
            physicalScore: 6.1,
            specialScore: 5.2,
            selectedScore: 6.1,
          },
        ]}
      />,
    )

    expect(screen.getByRole('table')).not.toBeNull()
    expect(screen.getAllByText('abomasnow')).toHaveLength(2)
    expect(screen.getAllByText('skarmory')).toHaveLength(2)
    expect(screen.getByText('Special')).not.toBeNull()
    expect(screen.getByText('Physical')).not.toBeNull()
    expect(screen.getByText('FIRE / Special')).not.toBeNull()
    expect(screen.getByText('ELECTRIC / Physical')).not.toBeNull()
    expect(screen.getAllByText('10.15')).toHaveLength(2)
    expect(screen.getAllByText('6.10')).toHaveLength(2)
    expect(screen.getAllByText('Selected score')).toHaveLength(2)
    expect(container.querySelectorAll('.matchup-card')).toHaveLength(2)
  })
})
