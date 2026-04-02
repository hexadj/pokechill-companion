type EmptyStateProps = {
  title: string
  description?: string
}

export function EmptyState({ title, description }: EmptyStateProps) {
  return (
    <div className="state state-empty">
      <p className="state-empty-title">{title}</p>
      {description ? <p className="state-empty-desc">{description}</p> : null}
    </div>
  )
}
