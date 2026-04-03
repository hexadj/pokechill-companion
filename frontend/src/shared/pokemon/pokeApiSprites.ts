/**
 * Resolve Pokémon artwork URLs via PokéAPI (sprites hosted on GitHub raw).
 * Avoids play.pokemonshowdown.com (ORB / naming mismatches with Pokechill keys).
 *
 * @see https://pokeapi.co/docs/v2
 */

const POKEAPI_POKEMON = 'https://pokeapi.co/api/v2/pokemon'

/** PascalCase / camelCase → kebab-case (e.g. MrMime → mr-mime, MegaAbomasnow → mega-abomasnow). */
export function toKebabCase(sourceKey: string): string {
  return sourceKey
    .trim()
    .replace(/([a-z0-9])([A-Z])/g, '$1-$2')
    .replace(/([A-Z])([A-Z][a-z])/g, '$1-$2')
    .toLowerCase()
}

/**
 * PokéAPI slugs to try for a Pokechill `sourceKey` (order matters).
 * Covers concatenated mega keys (megaabomasnow → abomasnow-mega).
 */
export function pokeApiSlugCandidates(sourceKey: string): string[] {
  const raw = sourceKey.trim()
  if (raw === '') {
    return []
  }

  const seen = new Set<string>()
  const add = (s: string): void => {
    const t = s.trim().toLowerCase()
    if (t !== '' && !seen.has(t)) {
      seen.add(t)
    }
  }

  add(toKebabCase(raw))
  add(raw.replace(/\s+/g, '-').replace(/_/g, '-').toLowerCase())

  const alnum = raw.toLowerCase().replace(/[^a-z0-9]/g, '')
  if (alnum.startsWith('mega') && alnum.length > 4) {
    const rest = alnum.slice(4)
    add(`${rest}-mega`)
  }

  const kebab = toKebabCase(raw)
  if (kebab.startsWith('mega-')) {
    const base = kebab.slice(5)
    if (base !== '') {
      add(`${base}-mega`)
    }
  }

  return [...seen]
}

type PokeApiPokemonJson = {
  sprites?: {
    front_default?: string | null
    other?: {
      home?: { front_default?: string | null }
      'official-artwork'?: { front_default?: string | null }
    }
  }
}

function pickSpriteUrl(data: PokeApiPokemonJson): string | null {
  const art = data.sprites?.other?.['official-artwork']?.front_default
  if (art) {
    return art
  }
  const home = data.sprites?.other?.home?.front_default
  if (home) {
    return home
  }
  const def = data.sprites?.front_default
  return def ?? null
}

/**
 * Returns a PNG URL from the PokéAPI sprite graph, or null if no slug matched.
 */
export async function fetchPokeApiSpriteUrl(sourceKey: string): Promise<string | null> {
  for (const slug of pokeApiSlugCandidates(sourceKey)) {
    const url = `${POKEAPI_POKEMON}/${encodeURIComponent(slug)}`
    const res = await fetch(url, { headers: { Accept: 'application/json' } })
    if (!res.ok) {
      continue
    }
    const data = (await res.json()) as PokeApiPokemonJson
    const sprite = pickSpriteUrl(data)
    if (sprite) {
      return sprite
    }
  }
  return null
}
