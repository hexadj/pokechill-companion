type ErrorStateProps = {
  message: string
}

export function ErrorState({ message }: ErrorStateProps) {
  return (
    <div className="state state-error" role="alert">
      {message}
    </div>
  )
}
