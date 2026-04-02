/** Background and foreground colors aligned with main-series Pokémon type palettes. */

export type TypeStyle = {
  bg: string
  fg: string
}

const DEFAULT_STYLE: TypeStyle = { bg: '#6b7280', fg: '#ffffff' }

/**
 * Lowercase English type codes as returned by the API (`fire`, `water`, …).
 */
const MAP: Record<string, TypeStyle> = {
  normal: { bg: '#A8A77A', fg: '#1f2937' },
  fire: { bg: '#EE8130', fg: '#ffffff' },
  water: { bg: '#6390F0', fg: '#ffffff' },
  electric: { bg: '#F7D02C', fg: '#1f2937' },
  grass: { bg: '#7AC74C', fg: '#1f2937' },
  ice: { bg: '#96D9D6', fg: '#1f2937' },
  fighting: { bg: '#C22E28', fg: '#ffffff' },
  poison: { bg: '#A33EA1', fg: '#ffffff' },
  ground: { bg: '#E2BF65', fg: '#1f2937' },
  flying: { bg: '#A98FF3', fg: '#1f2937' },
  psychic: { bg: '#F95587', fg: '#ffffff' },
  bug: { bg: '#A6B91A', fg: '#1f2937' },
  rock: { bg: '#B6A136', fg: '#1f2937' },
  ghost: { bg: '#735797', fg: '#ffffff' },
  dragon: { bg: '#6F35FC', fg: '#ffffff' },
  dark: { bg: '#705746', fg: '#ffffff' },
  steel: { bg: '#B7B7CE', fg: '#1f2937' },
  fairy: { bg: '#D685AD', fg: '#1f2937' },
}

export function getTypeStyle(typeCode: string): TypeStyle {
  const key = typeCode.trim().toLowerCase()
  return MAP[key] ?? DEFAULT_STYLE
}

export function formatTypeLabel(typeCode: string): string {
  const t = typeCode.trim().toLowerCase()
  if (t === '') {
    return ''
  }
  return t.charAt(0).toUpperCase() + t.slice(1)
}
