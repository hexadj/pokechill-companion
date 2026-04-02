type InputProps = {
  id?: string
  value: string
  onChange: (value: string) => void
  placeholder?: string
  autoComplete?: string
  disabled?: boolean
  className?: string
  'aria-label'?: string
  onFocus?: () => void
  onBlur?: () => void
}

export function Input({
  id,
  value,
  onChange,
  placeholder,
  autoComplete = 'off',
  disabled,
  className = '',
  'aria-label': ariaLabel,
  onFocus,
  onBlur,
}: InputProps) {
  return (
    <input
      id={id}
      className={`input ${className}`.trim()}
      type="text"
      value={value}
      onChange={(e) => onChange(e.target.value)}
      placeholder={placeholder}
      autoComplete={autoComplete}
      disabled={disabled}
      aria-label={ariaLabel}
      onFocus={onFocus}
      onBlur={onBlur}
    />
  )
}
