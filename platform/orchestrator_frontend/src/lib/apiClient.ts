const API_BASE_URL = (
  import.meta.env.VITE_API_BASE_URL ?? 'http://orchestrator.localhost'
).replace(/\/$/, '')

export class ApiError extends Error {
  status: number
  payload: unknown

  constructor(message: string, status: number, payload: unknown) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.payload = payload
  }
}

function toUrl(path: string): string {
  if (/^https?:\/\//i.test(path)) {
    return path
  }

  const normalizedPath = path.startsWith('/') ? path : `/${path}`
  return `${API_BASE_URL}${normalizedPath}`
}

interface RequestOptions {
  method?: string
  headers?: Record<string, string>
  body?: unknown
  token?: string
}

export async function apiRequest<T = unknown>(
  path: string,
  options: RequestOptions = {},
): Promise<T> {
  const { method = 'GET', headers = {}, body, token } = options
  const requestHeaders = new Headers(headers)

  requestHeaders.set('Accept', 'application/json')

  if (body !== undefined) {
    requestHeaders.set('Content-Type', 'application/json')
  }

  if (token) {
    requestHeaders.set('Authorization', `Bearer ${token}`)
  }

  const response = await fetch(toUrl(path), {
    method,
    headers: requestHeaders,
    body: body === undefined ? undefined : JSON.stringify(body),
  })

  const rawPayload = await response.text()
  let payload: unknown = null

  if (rawPayload !== '') {
    try {
      payload = JSON.parse(rawPayload)
    } catch {
      payload = rawPayload
    }
  }

  if (!response.ok) {
    const message =
      (payload &&
        typeof payload === 'object' &&
        'message' in payload &&
        typeof (payload as Record<string, unknown>).message === 'string' &&
        (payload as Record<string, unknown>).message) ||
      `Request failed (${response.status})`

    throw new ApiError(message as string, response.status, payload)
  }

  return payload as T
}

