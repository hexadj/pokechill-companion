/**
 * Resolve sprite URLs from Play Pokechill raw assets.
 */

const PLAY_POKECHILL_SPRITE_BASE_URL =
  'https://raw.githubusercontent.com/play-pokechill/play-pokechill.github.io/main/img/pkmn/sprite'

function splitSourceKeyWords(sourceKey: string): string[] {
  return sourceKey
    .trim()
    .replace(/([a-z0-9])([A-Z])/g, '$1 $2')
    .replace(/([A-Z])([A-Z][a-z])/g, '$1 $2')
    .replace(/[-_]+/g, ' ')
    .split(/\s+/)
    .filter(Boolean)
}

function capitalize(word: string): string {
  if (word === '') {
    return ''
  }
  return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
}

/**
 * Build candidate Play Pokechill sprite filenames for a source key.
 * Known formats include:
 * - pikachu.png
 * - megaCharizardX.png
 * - HisuianArcanine.png
 * - muk-alola.png
 */
export function playPokechillSpriteFilenameCandidates(sourceKey: string): string[] {
  const raw = sourceKey.trim()
  if (raw === '') {
    return []
  }

  const words = splitSourceKeyWords(raw)
  const lowerWords = words.map((w) => w.toLowerCase())
  const seen = new Set<string>()
  const out: string[] = []

  const add = (name: string): void => {
    const trimmed = name.trim()
    if (trimmed === '') {
      return
    }
    const key = trimmed.toLowerCase()
    if (seen.has(key)) {
      return
    }
    seen.add(key)
    out.push(trimmed)
  }

  add(raw)
  add(raw.replace(/\s+/g, ''))
  add(raw.replace(/\s+/g, '-'))
  add(raw.replace(/\s+/g, '').replace(/_/g, '-'))

  const lowerCompact = lowerWords.join('')
  const kebabLower = lowerWords.join('-')
  add(lowerCompact)
  add(kebabLower)

  if (words.length > 0) {
    const pascal = words.map((w) => capitalize(w)).join('')
    add(pascal)
    add(pascal.charAt(0).toLowerCase() + pascal.slice(1))
  }

  if (lowerWords[0] === 'mega' && lowerWords.length > 1) {
    const base = words.slice(1).map((w) => capitalize(w)).join('')
    add(`mega${base}`)
    add(`${lowerWords.slice(1).join('')}-mega`)
  }

  if (lowerWords.length > 1) {
    const last = lowerWords[lowerWords.length - 1]
    const regionLike = new Set(['alola', 'galar', 'paldea', 'hisui', 'hisuian'])
    if (regionLike.has(last)) {
      const baseLower = lowerWords.slice(0, -1).join('')
      add(`${baseLower}-${last}`)
    }
  }

  return out.map((name) => `${name}.png`)
}

/**
 * Return first reachable sprite URL from Play Pokechill assets, or null.
 */
export async function fetchPokeApiSpriteUrl(sourceKey: string): Promise<string | null> {
  for (const filename of playPokechillSpriteFilenameCandidates(sourceKey)) {
    const url = `${PLAY_POKECHILL_SPRITE_BASE_URL}/${encodeURIComponent(filename)}`
    const res = await fetch(url, { method: 'HEAD' })
    if (res.ok) {
      return url
    }
  }
  return null
}
