/**
 * Builds a static gen5 sprite URL on Pokémon Showdown's CDN.
 * `sourceKey` matches Pokechill identifiers (e.g. `Pikachu`, `Charizard`); filenames use lowercase keys.
 * Some forms or special names may 404 — callers should handle `<img>` errors.
 */
const SHOWDOWN_GEN5_BASE = 'https://play.pokemonshowdown.com/sprites/gen5'

export function showdownGen5SpriteUrl(sourceKey: string): string {
  const key = sourceKey.trim().toLowerCase()
  return `${SHOWDOWN_GEN5_BASE}/${key}.png`
}
