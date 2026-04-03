import { useCallback, useMemo, useState } from 'react'

import { OpponentTeamBuilder } from '../../features/team-builder/OpponentTeamBuilder'
import { RecommendationList } from '../../features/recommendations/RecommendationList'
import { useRecommendationsMutation } from '../../features/recommendations/useRecommendationsMutation'
import { getUserFacingApiMessage } from '../../shared/api/client'
import { POKECHILL_DIVISION_CODES } from '../../shared/constants/pokechillDivisions'
import { TypeBadge } from '../../shared/pokemon/TypeBadge'
import type { ReferencePokemonItem, RecommendationRequest } from '../../shared/types/api'
import { Button } from '../../shared/ui/Button'
import { Card } from '../../shared/ui/Card'
import { ErrorState } from '../../shared/ui/ErrorState'
import { Input } from '../../shared/ui/Input'
import { Loader } from '../../shared/ui/Loader'

const DEFAULT_LIMIT = 20

function clampLimit(raw: string): number | null {
  const n = Number.parseInt(raw, 10)
  if (!Number.isFinite(n)) {
    return null
  }
  if (n < 1 || n > 50) {
    return null
  }
  return n
}

function divisionPayload(selected: ReadonlySet<string>): string[] | undefined {
  if (selected.size === POKECHILL_DIVISION_CODES.length) {
    return undefined
  }
  return POKECHILL_DIVISION_CODES.filter((code) => selected.has(code))
}

export function RecommendationPage() {
  const [team, setTeam] = useState<ReferencePokemonItem[]>([])
  const [limitInput, setLimitInput] = useState(String(DEFAULT_LIMIT))
  const [includeNonObtainable, setIncludeNonObtainable] = useState(false)
  const [selectedDivisionCodes, setSelectedDivisionCodes] = useState(
    () => new Set<string>(POKECHILL_DIVISION_CODES),
  )
  const mutation = useRecommendationsMutation()

  const limit = useMemo(() => {
    const parsed = clampLimit(limitInput.trim())
    return parsed ?? DEFAULT_LIMIT
  }, [limitInput])

  const limitValid = clampLimit(limitInput.trim()) !== null

  const toggleDivision = useCallback((code: string) => {
    setSelectedDivisionCodes((prev) => {
      const next = new Set(prev)
      if (next.has(code)) {
        next.delete(code)
      } else {
        next.add(code)
      }
      return next
    })
  }, [])

  const selectAllDivisions = useCallback(() => {
    setSelectedDivisionCodes(new Set(POKECHILL_DIVISION_CODES))
  }, [])

  const clearDivisions = useCallback(() => {
    setSelectedDivisionCodes(new Set())
  }, [])

  const runAnalysis = useCallback(() => {
    if (team.length === 0) {
      return
    }
    const divisionCodes = divisionPayload(selectedDivisionCodes)
    const body: RecommendationRequest = {
      opponentSourceKeys: team.map((p) => p.sourceKey),
      limit,
    }
    if (includeNonObtainable) {
      body.includeNonObtainable = true
    }
    if (divisionCodes !== undefined) {
      body.divisionCodes = divisionCodes
    }
    mutation.mutate(body)
  }, [includeNonObtainable, limit, mutation, selectedDivisionCodes, team])

  const last = mutation.data
  const divisionsValid = selectedDivisionCodes.size >= 1
  const canAnalyze =
    team.length >= 1 &&
    team.length <= 6 &&
    limitValid &&
    divisionsValid &&
    !mutation.isPending

  return (
    <div className="page">
      <header className="page-header">
        <h1 className="page-title">Pokechill Companion</h1>
        <p className="page-subtitle muted">
          Build an opponent team, then run the offensive recommendation engine.
        </p>
        <details className="ruleset-disclosure hint">
          <summary className="ruleset-disclosure-summary">V1 scoring rules (read-only)</summary>
          <div className="ruleset-disclosure-body muted">
            <p>
              Scores are <strong>offensive only</strong> (no defensive rating). For each candidate vs each
              opponent: type effectiveness uses Pokechill multipliers{' '}
              <strong>×0 / ×0.5 / ×1 / ×1.5</strong>; on dual types, combine multipliers by multiplication.
            </p>
            <p>
              For each matchup we take the better of physical (<code>atk</code> vs opponent{' '}
              <code>def</code>) and special (<code>satk</code> vs <code>sdef</code>), scaled by that type
              multiplier, then <strong>sum</strong> across the opponent team. Moves, abilities, items, IV/EV,
              and Pokechill division/stars are <strong>not</strong> part of the V1 formula.
            </p>
          </div>
        </details>
      </header>

      <Card title="Opponent team">
        <OpponentTeamBuilder team={team} disabled={mutation.isPending} onChange={setTeam} />
      </Card>

      <Card title="Analysis">
        <div className="analysis-row">
          <div className="analysis-limit">
            <label className="label" htmlFor="rec-limit">
              Result limit (1–50)
            </label>
            <Input
              id="rec-limit"
              value={limitInput}
              onChange={setLimitInput}
              placeholder="20"
              disabled={mutation.isPending}
              aria-label="Maximum number of recommendations"
            />
            {!limitValid ? (
              <p className="hint error-text">Enter an integer between 1 and 50.</p>
            ) : null}
          </div>
          <div className="analysis-action">
            <Button
              type="button"
              disabled={!canAnalyze}
              onClick={() => runAnalysis()}
            >
              {mutation.isPending ? 'Analyzing…' : 'Run analysis'}
            </Button>
          </div>
        </div>
        <div className="analysis-filters stack-top">
          <label className="checkbox-row">
            <input
              type="checkbox"
              checked={includeNonObtainable}
              onChange={(e) => setIncludeNonObtainable(e.target.checked)}
              disabled={mutation.isPending}
            />
            <span>Include unobtainable Pokémon in recommendations</span>
          </label>
          <div className="analysis-divisions">
            <div className="analysis-divisions-header">
              <span className="label">Candidate divisions</span>
              <div className="analysis-divisions-actions">
                <button
                  type="button"
                  className="link-button"
                  onClick={selectAllDivisions}
                  disabled={mutation.isPending}
                >
                  Select all
                </button>
                <button
                  type="button"
                  className="link-button"
                  onClick={clearDivisions}
                  disabled={mutation.isPending}
                >
                  Clear
                </button>
              </div>
            </div>
            <div className="division-checkboxes" role="group" aria-label="Pokechill divisions for candidates">
              {POKECHILL_DIVISION_CODES.map((code) => (
                <label key={code} className="division-checkbox">
                  <input
                    type="checkbox"
                    checked={selectedDivisionCodes.has(code)}
                    onChange={() => toggleDivision(code)}
                    disabled={mutation.isPending}
                  />
                  <span>{code}</span>
                </label>
              ))}
            </div>
            {!divisionsValid ? (
              <p className="hint error-text">Select at least one division.</p>
            ) : (
              <p className="hint muted">By default all divisions are allowed; narrow the pool with the toggles above.</p>
            )}
          </div>
        </div>
        {mutation.isError ? (
          <div className="stack-top">
            <ErrorState message={getUserFacingApiMessage(mutation.error)} />
          </div>
        ) : null}
      </Card>

      <Card title="Recommendations">
        {mutation.isPending ? (
          <div className="center-pad">
            <Loader label="Computing recommendations…" />
          </div>
        ) : (
          <>
            {last ? (
              <div className="opponent-summary">
                <div className="opponent-summary-title">Opponent order (from your picks)</div>
                <ol className="opponent-order">
                  {last.opponentTeam.map((p, idx) => (
                    <li key={`${p.sourceKey}-${idx}`}>
                      {p.name}{' '}
                      <span className="type-badge-row type-badge-row--inline" aria-label="Types">
                        <TypeBadge typeCode={p.primaryTypeCode} />
                        {p.secondaryTypeCode ? <TypeBadge typeCode={p.secondaryTypeCode} /> : null}
                      </span>
                    </li>
                  ))}
                </ol>
              </div>
            ) : null}
            <RecommendationList items={last?.recommendations ?? []} />
          </>
        )}
      </Card>
    </div>
  )
}
