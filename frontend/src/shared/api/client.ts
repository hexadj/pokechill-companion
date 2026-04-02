import type { ProblemJson } from '../types/api'
import { buildApiUrl, getApiBaseUrl } from './config'

function networkFailureMessage(): string {
  const base = getApiBaseUrl()
  const hint =
    base === ''
      ? 'same-origin `/api` (in dev, Vite should proxy to http://127.0.0.1:8000)'
      : `configured base ${base}`
  return `Cannot reach the API (${hint}). Check that the Symfony backend is running (e.g. http://127.0.0.1:8000).`
}

export class ApiError extends Error {
  readonly status: number
  readonly problem: ProblemJson | null

  constructor(status: number, problem: ProblemJson | null, message?: string) {
    super(message ?? problem?.detail ?? problem?.title ?? `Request failed (${status})`)
    this.name = 'ApiError'
    this.status = status
    this.problem = problem
  }
}

function summarizeValidationErrors(problem: ProblemJson | null): string | null {
  if (!problem?.errors) {
    return null
  }
  const parts: string[] = []
  for (const [key, messages] of Object.entries(problem.errors)) {
    if (messages.length > 0) {
      parts.push(`${key}: ${messages.join(' ')}`)
    }
  }
  return parts.length > 0 ? parts.join(' · ') : null
}

export function getUserFacingApiMessage(error: unknown): string {
  if (error instanceof ApiError) {
    const fromFields = summarizeValidationErrors(error.problem)
    if (fromFields) {
      return fromFields
    }
    if (error.problem?.detail) {
      return error.problem.detail
    }
    return error.message
  }
  if (error instanceof Error) {
    return error.message
  }
  return 'Something went wrong.'
}

async function fetchText(url: string, init: RequestInit): Promise<{ response: Response; text: string }> {
  let response: Response
  try {
    response = await fetch(url, init)
  } catch {
    throw new ApiError(0, null, networkFailureMessage())
  }
  const text = await response.text()
  return { response, text }
}

function parseProblemJson(text: string): ProblemJson | null {
  if (text === '') {
    return null
  }
  try {
    return JSON.parse(text) as ProblemJson
  } catch {
    return null
  }
}

function parseSuccessJson<T>(text: string): T {
  if (text === '') {
    throw new Error('The API returned an empty success response.')
  }
  try {
    return JSON.parse(text) as T
  } catch {
    throw new Error('The API returned a success response that is not valid JSON.')
  }
}

export async function apiGetJson<T>(path: string): Promise<T> {
  const url = buildApiUrl(path)
  const { response, text } = await fetchText(url, {
    method: 'GET',
    headers: { Accept: 'application/json' },
  })

  if (!response.ok) {
    throw new ApiError(response.status, parseProblemJson(text))
  }

  return parseSuccessJson<T>(text)
}

export async function apiPostJson<TResponse, TBody extends object>(
  path: string,
  body: TBody,
): Promise<TResponse> {
  const url = buildApiUrl(path)
  const { response, text } = await fetchText(url, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(body),
  })

  if (!response.ok) {
    throw new ApiError(response.status, parseProblemJson(text))
  }

  return parseSuccessJson<TResponse>(text)
}
