/** Pokechill star ratings 1..6; bstSum and division are informative (not used for V1 scoring). */
export type ReferencePokemonItem = {
  sourceKey: string
  name: string
  primaryTypeCode: string
  secondaryTypeCode: string | null
  hp: number
  atk: number
  def: number
  satk: number
  sdef: number
  spe: number
  bstSum: number
  division: string
  /** Informative; from import obtainability pipeline. */
  isObtainable: boolean
  obtainabilityCode: string | null
}

export type RecommendationRequest = {
  opponentSourceKeys: string[]
  limit?: number
}

export type OpponentPokemonView = ReferencePokemonItem

export type MatchupView = {
  opponentSourceKey: string
  bestAttackTypeCode: string
  bestAttackCategory: string
  typeMultiplierX100: number
  physicalScore: number
  specialScore: number
  selectedScore: number
}

export type RecommendationView = {
  sourceKey: string
  name: string
  primaryTypeCode: string
  secondaryTypeCode: string | null
  score: number
  matchups: MatchupView[]
}

export type RecommendationResponse = {
  opponentTeam: OpponentPokemonView[]
  recommendations: RecommendationView[]
}

export type ListReferencePokemonResponse = {
  items: ReferencePokemonItem[]
}

export type ProblemJson = {
  type?: string
  title?: string
  status?: number
  detail?: string
  errors?: Record<string, string[]>
}
