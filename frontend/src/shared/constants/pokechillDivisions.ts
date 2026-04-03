/** Canonical Pokechill division codes (D–SSS); aligned with backend `PokechillDivisionCalculator::DIVISION_CODES`. */
export const POKECHILL_DIVISION_CODES = ['D', 'C', 'B', 'A', 'S', 'SS', 'SSS'] as const

export type PokechillDivisionCode = (typeof POKECHILL_DIVISION_CODES)[number]
