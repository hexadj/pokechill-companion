import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'

import { fetchPokeApiSpriteUrl } from './pokeApiSprites'

type PokemonSpriteVariant = 'team' | 'recommendation'

const variantClass: Record<PokemonSpriteVariant, { img: string; fallback: string }> = {
  team: { img: 'team-slot-sprite', fallback: 'team-slot-sprite-fallback' },
  recommendation: {
    img: 'recommendation-card-sprite',
    fallback: 'recommendation-card-sprite-fallback',
  },
}

type PokemonSpriteProps = {
  sourceKey: string
  name: string
  variant?: PokemonSpriteVariant
}

export function PokemonSprite({ sourceKey, name, variant = 'team' }: PokemonSpriteProps) {
  const classes = variantClass[variant]

  const { data: spriteUrl, isPending, isError } = useQuery({
    queryKey: ['poke-sprite', sourceKey],
    queryFn: () => fetchPokeApiSpriteUrl(sourceKey),
    staleTime: 1000 * 60 * 60 * 24 * 7,
    gcTime: 1000 * 60 * 60 * 24 * 30,
    retry: 1,
  })

  const [failedSpriteUrl, setFailedSpriteUrl] = useState<string | null>(null)

  const showFallback =
    isPending ||
    isError ||
    spriteUrl == null ||
    spriteUrl === '' ||
    failedSpriteUrl === spriteUrl

  if (showFallback) {
    return <div className={classes.fallback} aria-hidden />
  }

  return (
    <img
      src={spriteUrl}
      alt={name}
      className={classes.img}
      loading="lazy"
      decoding="async"
      onError={() => setFailedSpriteUrl(spriteUrl)}
    />
  )
}
