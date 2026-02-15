const API_BASE_URL = (import.meta.env.VITE_API_BASE_URL ?? 'http://api.localhost').replace(/\/$/, '')

export class ApiError extends Error {
  constructor(message, status, payload) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.payload = payload
  }
}

function toUrl(path) {
  if (/^https?:\/\//i.test(path)) {
    return path
  }

  const normalizedPath = path.startsWith('/') ? path : `/${path}`
  return `${API_BASE_URL}${normalizedPath}`
}

export async function apiRequest(path, options = {}) {
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
  let payload = null

  if (rawPayload !== '') {
    try {
      payload = JSON.parse(rawPayload)
    } catch {
      payload = rawPayload
    }
  }

  if (!response.ok) {
    const message =
      (payload && typeof payload === 'object' && 'message' in payload && payload.message) ||
      `Request failed (${response.status})`

    throw new ApiError(message, response.status, payload)
  }

  return payload
}
