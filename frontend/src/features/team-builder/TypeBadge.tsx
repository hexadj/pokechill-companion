import { formatTypeLabel, getTypeStyle } from '../../shared/pokemon/typeColors'

type TypeBadgeProps = {
  typeCode: string
}

export function TypeBadge({ typeCode }: TypeBadgeProps) {
  const { bg, fg } = getTypeStyle(typeCode)
  const label = formatTypeLabel(typeCode)

  return (
    <span
      className="type-badge"
      style={{ backgroundColor: bg, color: fg }}
      title={label}
    >
      {label}
    </span>
  )
}
