import type { ReferencePokemonItem } from '../../shared/types/api'
import { PokemonSprite } from '../../shared/pokemon/PokemonSprite'
import { Button } from '../../shared/ui/Button'
import { TypeBadge } from '../../shared/pokemon/TypeBadge'

const MAX_STARS = 6

function renderStars(rating: number): boolean[] {
  const safeRating = Math.min(MAX_STARS, Math.max(1, Math.round(rating)))
  return Array.from({ length: MAX_STARS }, (_, idx) => idx < safeRating)
}

function renderStarsNode(rating: number) {
  const stars = renderStars(rating)
  return (
    <>
      {stars.map((isFull, idx) => (
        <span key={idx} className={isFull ? 'stat-stars-full' : 'stat-stars-empty'}>
          {isFull ? '★' : '☆'}
        </span>
      ))}
    </>
  )
}

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
            <dl className="team-slot-stats" aria-label={`${pokemon.name} star stats`}>
              <div className="team-slot-stat-row">
                <dt>HP</dt>
                <dd className="stat-stars">{renderStarsNode(pokemon.hp)}</dd>
              </div>
              <div className="team-slot-stat-row">
                <dt>ATK</dt>
                <dd className="stat-stars">{renderStarsNode(pokemon.atk)}</dd>
              </div>
              <div className="team-slot-stat-row">
                <dt>DEF</dt>
                <dd className="stat-stars">{renderStarsNode(pokemon.def)}</dd>
              </div>
              <div className="team-slot-stat-row">
                <dt>SATK</dt>
                <dd className="stat-stars">{renderStarsNode(pokemon.satk)}</dd>
              </div>
              <div className="team-slot-stat-row">
                <dt>SDEF</dt>
                <dd className="stat-stars">{renderStarsNode(pokemon.sdef)}</dd>
              </div>
              <div className="team-slot-stat-row">
                <dt>SPE</dt>
                <dd className="stat-stars">{renderStarsNode(pokemon.spe)}</dd>
              </div>
            </dl>
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
