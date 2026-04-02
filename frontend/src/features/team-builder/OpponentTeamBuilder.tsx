import { useCallback, useMemo } from 'react'

import type { ReferencePokemonItem } from '../../shared/types/api'
import { PokemonSearchInput } from '../reference-pokemon/PokemonSearchInput'
import { OpponentTeamSlot } from './OpponentTeamSlot'

type OpponentTeamBuilderProps = {
  team: ReferencePokemonItem[]
  disabled?: boolean
  onChange: (next: ReferencePokemonItem[]) => void
}

export function OpponentTeamBuilder({ team, disabled, onChange }: OpponentTeamBuilderProps) {
  const slots = useMemo(() => {
    const out: Array<ReferencePokemonItem | null> = [...team]
    while (out.length < 6) {
      out.push(null)
    }
    return out.slice(0, 6)
  }, [team])

  const removeAt = useCallback(
    (idx: number) => {
      const next = team.filter((_, i) => i !== idx)
      onChange(next)
    },
    [onChange, team],
  )

  const addPokemon = useCallback(
    (pokemon: ReferencePokemonItem) => {
      if (team.length >= 6) {
        return
      }
      onChange([...team, pokemon])
    },
    [onChange, team],
  )

  return (
    <div className="team-builder">
      <div className="team-slots">
        {slots.map((pokemon, idx) => (
          <OpponentTeamSlot
            key={`slot-${idx}`}
            index={idx}
            pokemon={pokemon}
            onRemove={() => removeAt(idx)}
          />
        ))}
      </div>
      <PokemonSearchInput disabled={disabled} teamSize={team.length} onPick={addPokemon} />
    </div>
  )
}
