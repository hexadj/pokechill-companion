/**
 * Empty in dev with Vite proxy: requests go to same origin `/api/...`.
 * Set to full API origin (e.g. `http://127.0.0.1:8000`) when the UI is served
 * from another host without a reverse proxy.
 */
export function getApiBaseUrl(): string {
  const raw = import.meta.env.VITE_API_BASE_URL
  if (typeof raw === 'string' && raw.length > 0) {
    return raw.replace(/\/$/, '')
  }
  return ''
}

export function buildApiUrl(path: string): string {
  const base = getApiBaseUrl()
  const normalized = path.startsWith('/') ? path : `/${path}`
  if (base === '') {
    return normalized
  }
  return `${base}${normalized}`
}
