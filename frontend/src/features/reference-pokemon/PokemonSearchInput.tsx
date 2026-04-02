import { useId, useRef, useState } from 'react'

import { getUserFacingApiMessage } from '../../shared/api/client'
import type { ReferencePokemonItem } from '../../shared/types/api'
import { useDebouncedValue } from '../../shared/lib/useDebouncedValue'
import { ErrorState } from '../../shared/ui/ErrorState'
import { Input } from '../../shared/ui/Input'
import { Loader } from '../../shared/ui/Loader'
import { useReferencePokemon } from './useReferencePokemon'

const DEBOUNCE_MS = 280

type PokemonSearchInputProps = {
  disabled?: boolean
  teamSize: number
  onPick: (pokemon: ReferencePokemonItem) => void
}

export function PokemonSearchInput({ disabled, teamSize, onPick }: PokemonSearchInputProps) {
  const listId = useId()
  const [query, setQuery] = useState('')
  const debounced = useDebouncedValue(query, DEBOUNCE_MS)
  const { data, isPending, isError, error, refetch } = useReferencePokemon(debounced)
  const [open, setOpen] = useState(false)
  const blurTimeout = useRef<ReturnType<typeof setTimeout> | null>(null)

  const canAdd = teamSize < 6
  const items = data?.items ?? []

  const handleBlur = () => {
    blurTimeout.current = window.setTimeout(() => setOpen(false), 120)
  }

  const handleFocus = () => {
    if (blurTimeout.current) {
      window.clearTimeout(blurTimeout.current)
    }
    setOpen(true)
  }

  const handlePick = (item: ReferencePokemonItem) => {
    onPick(item)
    setQuery('')
    setOpen(false)
  }

  const handleQueryChange = (value: string) => {
    setQuery(value)
    // After a pick we close the list; without a new focus event, typing must reopen it.
    if (canAdd) {
      setOpen(true)
    }
  }

  return (
    <div className="search-field">
      <label className="label" htmlFor={listId}>
        Search Pokémon
      </label>
      <div className="search-input-wrap">
        <Input
          id={listId}
          value={query}
          onChange={handleQueryChange}
          placeholder="Name…"
          disabled={disabled || !canAdd}
          aria-label="Search Pokémon by name"
          onFocus={handleFocus}
          onBlur={handleBlur}
        />
        {open && canAdd ? (
          <div className="search-results" role="listbox" aria-label="Search results">
            {isPending ? (
              <div className="search-results-pad">
                <Loader />
              </div>
            ) : null}
            {!isPending && isError ? (
              <div className="search-results-pad">
                <ErrorState message={getUserFacingApiMessage(error)} />
                <button type="button" className="link-button" onClick={() => void refetch()}>
                  Retry
                </button>
              </div>
            ) : null}
            {!isPending && !isError && items.length === 0 ? (
              <div className="search-results-pad muted">No matches.</div>
            ) : null}
            {!isPending && !isError
              ? items.map((p) => (
                  <button
                    key={p.sourceKey}
                    type="button"
                    role="option"
                    className="search-result-row"
                    onMouseDown={(e) => e.preventDefault()}
                    onClick={() => handlePick(p)}
                  >
                    <span className="search-result-name">{p.name}</span>
                    <span className="search-result-types">
                      {p.primaryTypeCode}
                      {p.secondaryTypeCode ? ` · ${p.secondaryTypeCode}` : ''}
                    </span>
                  </button>
                ))
              : null}
          </div>
        ) : null}
      </div>
      {!canAdd ? <p className="hint">Team is full (6).</p> : null}
    </div>
  )
}
