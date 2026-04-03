import type { ReferencePokemonItem } from '../../shared/types/api'
import { PokemonSprite } from '../../shared/pokemon/PokemonSprite'
import { Button } from '../../shared/ui/Button'
import { TypeBadge } from '../../shared/pokemon/TypeBadge'

type OpponentTeamSlotProps = {
  index: number
  pokemon: ReferencePokemonItem | null
  onRemove: () => void
}

export function OpponentTeamSlot({ index, pokemon, onRemove }: OpponentTeamSlotProps) {
  return (
    <div className="team-slot">
      <div className="team-slot-index" aria-label={`Slot ${index + 1}`}>
        {index + 1}
      </div>
      <div className="team-slot-body">
        {pokemon ? (
          <>
            <PokemonSprite
              key={`${pokemon.sourceKey}-${index}`}
              sourceKey={pokemon.sourceKey}
              name={pokemon.name}
            />
            <div className="team-slot-types">
              <TypeBadge typeCode={pokemon.primaryTypeCode} />
              {pokemon.secondaryTypeCode ? <TypeBadge typeCode={pokemon.secondaryTypeCode} /> : null}
            </div>
            <div className="team-slot-name">{pokemon.name}</div>
            <div className="team-slot-meta muted" title="Pokechill division (informative)">
              Division {pokemon.division}
            </div>
          </>
        ) : (
          <>
            <div className="team-slot-sprite-fallback team-slot-sprite-fallback--empty" aria-hidden />
            <div className="team-slot-name team-slot-name--empty muted">Empty</div>
          </>
        )}
      </div>
      <div className="team-slot-actions">
        <Button
          type="button"
          variant="secondary"
          className="btn-slot-remove"
          disabled={!pokemon}
          onClick={onRemove}
        >
          Remove
        </Button>
      </div>
    </div>
  )
}
