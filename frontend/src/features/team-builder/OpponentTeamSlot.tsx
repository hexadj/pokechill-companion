import type { ReferencePokemonItem } from '../../shared/types/api'
import { Button } from '../../shared/ui/Button'

type OpponentTeamSlotProps = {
  index: number
  pokemon: ReferencePokemonItem | null
  onRemove: () => void
}

export function OpponentTeamSlot({ index, pokemon, onRemove }: OpponentTeamSlotProps) {
  return (
    <div className="team-slot">
      <div className="team-slot-index">{index + 1}</div>
      <div className="team-slot-body">
        {pokemon ? (
          <>
            <div className="team-slot-name">{pokemon.name}</div>
            <div className="team-slot-meta muted">
              {pokemon.primaryTypeCode}
              {pokemon.secondaryTypeCode ? ` · ${pokemon.secondaryTypeCode}` : ''}
            </div>
          </>
        ) : (
          <div className="muted">Empty</div>
        )}
      </div>
      <div className="team-slot-actions">
        <Button
          type="button"
          variant="secondary"
          disabled={!pokemon}
          onClick={onRemove}
        >
          Remove
        </Button>
      </div>
    </div>
  )
}
