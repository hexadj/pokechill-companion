import { act, cleanup, render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import type { ReactNode } from 'react'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { RecommendationPage } from './RecommendationPage'

const fixtures = vi.hoisted(() => {
  const alpha = {
    sourceKey: 'alpha-mon',
    name: 'Alpha Mon',
    primaryTypeCode: 'fire',
    secondaryTypeCode: null,
    hp: 3,
    atk: 4,
    def: 3,
    satk: 4,
    sdef: 3,
    spe: 3,
    bstSum: 20,
    division: 'S',
    isObtainable: true,
    obtainabilityCode: null,
  }

  const beta = {
    sourceKey: 'beta-mon',
    name: 'Beta Mon',
    primaryTypeCode: 'water',
    secondaryTypeCode: null,
    hp: 3,
    atk: 4,
    def: 3,
    satk: 4,
    sdef: 3,
    spe: 3,
    bstSum: 20,
    division: 'S',
    isObtainable: true,
    obtainabilityCode: null,
  }

  const successResponse = {
    opponentTeam: [alpha],
    recommendations: [
      {
        sourceKey: 'bulba',
        name: 'Bulbasaur',
        primaryTypeCode: 'grass',
        secondaryTypeCode: 'poison',
        hp: 3,
        atk: 3,
        def: 3,
        satk: 4,
        sdef: 4,
        spe: 3,
        division: 'A',
        score: 12.5,
        matchups: [],
      },
    ],
  }

  return {
    alpha,
    beta,
    successResponse,
  }
})

const recommendationApiMock = vi.hoisted(() => {
  type Deferred = {
    reject: (error: unknown) => void
    resolve: (value: unknown) => void
  }

  const requests: unknown[] = []
  let deferreds: Deferred[] = []

  const createRecommendations = vi.fn((request: unknown) => {
    requests.push(request)

    return new Promise((resolve, reject) => {
      deferreds.push({ resolve, reject })
    })
  })

  return {
    createRecommendations,
    getRequests: () => [...requests],
    reset: () => {
      requests.length = 0
      deferreds = []
      createRecommendations.mockClear()
    },
    resolveNext: (value: unknown) => {
      const deferred = deferreds.shift()
      if (!deferred) {
        throw new Error('No pending recommendation request to resolve.')
      }
      deferred.resolve(value)
    },
    rejectNext: (error: unknown) => {
      const deferred = deferreds.shift()
      if (!deferred) {
        throw new Error('No pending recommendation request to reject.')
      }
      deferred.reject(error)
    },
  }
})

vi.mock('../../shared/api/recommendations', () => ({
  createRecommendations: recommendationApiMock.createRecommendations,
}))

vi.mock('../../features/team-builder/OpponentTeamBuilder', () => ({
  OpponentTeamBuilder: ({
    disabled,
    onChange,
  }: {
    disabled?: boolean
    onChange: (next: Array<typeof fixtures.alpha>) => void
  }) => (
    <div>
      <button type="button" disabled={disabled} onClick={() => onChange([fixtures.alpha])}>
        Use Alpha team
      </button>
      <button type="button" disabled={disabled} onClick={() => onChange([fixtures.beta])}>
        Use Beta team
      </button>
      <button type="button" disabled={disabled} onClick={() => onChange([])}>
        Clear team
      </button>
    </div>
  ),
}))

vi.mock('../../features/recommendations/RecommendationCard', () => ({
  RecommendationCard: ({
    entry,
    rank,
  }: {
    entry: { name: string }
    rank: number
  }) => <div>{`#${rank} ${entry.name}`}</div>,
}))

function renderWithProviders(ui: ReactNode) {
  const client = new QueryClient({
    defaultOptions: {
      queries: {
        retry: false,
      },
      mutations: {
        retry: false,
      },
    },
  })

  return render(<QueryClientProvider client={client}>{ui}</QueryClientProvider>)
}

describe('RecommendationPage', () => {
  beforeEach(() => {
    recommendationApiMock.reset()
  })

  afterEach(() => {
    cleanup()
  })

  it('hides successful results when the opponent team changes after a run', async () => {
    const user = userEvent.setup()
    renderWithProviders(<RecommendationPage />)

    await user.click(screen.getByRole('button', { name: 'Use Alpha team' }))
    await user.click(screen.getByRole('button', { name: 'Run analysis' }))

    expect(recommendationApiMock.getRequests()).toEqual([
      {
        opponentSourceKeys: ['alpha-mon'],
        limit: 20,
      },
    ])

    await act(async () => {
      recommendationApiMock.resolveNext(fixtures.successResponse)
    })

    expect(await screen.findByText('#1 Bulbasaur')).toBeTruthy()

    await user.click(screen.getByRole('button', { name: 'Use Beta team' }))

    await waitFor(() => {
      expect(screen.queryByText('#1 Bulbasaur')).toBeNull()
    })

    expect(screen.getByText('Analysis out of date')).toBeTruthy()
    expect(screen.getByText('Parameters changed. Run analysis again.')).toBeTruthy()
    expect(recommendationApiMock.getRequests()).toHaveLength(1)
  })

  it('clears visible API errors when the request parameters change', async () => {
    const user = userEvent.setup()
    renderWithProviders(<RecommendationPage />)

    await user.click(screen.getByRole('button', { name: 'Use Alpha team' }))
    await user.click(screen.getByRole('button', { name: 'Run analysis' }))

    await act(async () => {
      recommendationApiMock.rejectNext(new Error('Backend validation failed'))
    })

    const alert = await screen.findByRole('alert')
    expect(alert.textContent).toContain('Backend validation failed')

    const limitInput = screen.getByLabelText('Maximum number of recommendations')
    await user.clear(limitInput)
    await user.type(limitInput, '10')

    await waitFor(() => {
      expect(screen.queryByRole('alert')).toBeNull()
    })

    expect(screen.getByText('Analysis out of date')).toBeTruthy()
    expect(recommendationApiMock.getRequests()).toHaveLength(1)
  })

  it('does not auto-run again when the form becomes non-executable after a successful run', async () => {
    const user = userEvent.setup()
    renderWithProviders(<RecommendationPage />)

    await user.click(screen.getByRole('button', { name: 'Use Alpha team' }))
    await user.click(screen.getByRole('button', { name: 'Run analysis' }))

    await act(async () => {
      recommendationApiMock.resolveNext(fixtures.successResponse)
    })

    expect(await screen.findByText('#1 Bulbasaur')).toBeTruthy()

    await user.click(screen.getByRole('button', { name: 'Clear team' }))

    await waitFor(() => {
      expect(screen.queryByText('#1 Bulbasaur')).toBeNull()
    })

    expect(screen.getByText('Parameters changed. Run analysis again.')).toBeTruthy()
    expect(screen.getByRole('button', { name: 'Run analysis' })).toHaveProperty('disabled', true)
    expect(recommendationApiMock.getRequests()).toHaveLength(1)
  })
})
