import { render, screen } from '@testing-library/react'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { RecommendationCard } from './RecommendationCard'

const recommendationMatchupTableSpy = vi.hoisted(() => vi.fn())

vi.mock('../../shared/pokemon/PokemonSprite', () => ({
  PokemonSprite: ({
    name,
    sourceKey,
    variant,
  }: {
    name: string
    sourceKey: string
    variant?: string
  }) => <div>{`Sprite ${name} ${sourceKey} ${variant ?? 'team'}`}</div>,
}))

vi.mock('../../shared/pokemon/TypeBadge', () => ({
  TypeBadge: ({ typeCode }: { typeCode: string }) => <span>{typeCode}</span>,
}))

vi.mock('./RecommendationMatchupTable', () => ({
  RecommendationMatchupTable: ({ matchups }: { matchups: unknown[] }) => {
    recommendationMatchupTableSpy(matchups)
    return <div>{`Matchups: ${matchups.length}`}</div>
  },
}))

describe('RecommendationCard', () => {
  beforeEach(() => {
    recommendationMatchupTableSpy.mockClear()
  })

  it('renders the recommendation score in the details summary and keeps matchup details attached', () => {
    const { container } = render(
      <RecommendationCard
        rank={1}
        entry={{
          sourceKey: 'charizard',
          name: 'Charizard',
          primaryTypeCode: 'FIRE',
          secondaryTypeCode: 'FLYING',
          hp: 4,
          atk: 5,
          def: 3,
          satk: 6,
          sdef: 4,
          spe: 5,
          division: 'A',
          score: 12.5,
          matchups: [
            {
              opponentSourceKey: 'abomasnow',
              bestAttackTypeCode: 'FIRE',
              bestAttackCategory: 'special',
              typeMultiplierX100: 400,
              physicalScore: 8.75,
              specialScore: 10.15,
              selectedScore: 10.15,
            },
          ],
        }}
      />,
    )

    expect(screen.getByText('#1')).not.toBeNull()
    expect(screen.getByText('Charizard')).not.toBeNull()
    expect(screen.getByText('Division A')).not.toBeNull()
    expect(screen.getByText('FIRE')).not.toBeNull()
    expect(screen.getByText('FLYING')).not.toBeNull()
    expect(screen.getByText('Matchup score : 12.50')).not.toBeNull()
    expect(screen.getByText('Matchups: 1')).not.toBeNull()
    expect(screen.getByLabelText('Charizard star stats')).not.toBeNull()
    expect(container.querySelectorAll('.recommendation-stat-row')).toHaveLength(6)
    expect(recommendationMatchupTableSpy).toHaveBeenCalledTimes(1)
  })
})
