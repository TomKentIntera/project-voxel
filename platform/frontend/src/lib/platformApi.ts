export type HealthResponse = {
  status: string
  service: string
  apiVersion: string
  timestamp: string
}

export type LegacyDomain = {
  key: string
  description: string
  legacyRoutes: string[]
  targetApi: string
  status: string
}

export type LegacyDomainResponse = {
  source: string
  domains: LegacyDomain[]
}

const apiBaseUrl = (import.meta.env.VITE_API_BASE_URL ?? '').replace(/\/$/, '')

async function getJson<T>(path: string): Promise<T> {
  const response = await fetch(`${apiBaseUrl}${path}`, {
    headers: {
      Accept: 'application/json',
    },
  })

  if (!response.ok) {
    throw new Error(`Request failed: ${response.status} ${response.statusText}`)
  }

  return (await response.json()) as T
}

export function fetchHealth(): Promise<HealthResponse> {
  return getJson<HealthResponse>('/api/v1/health')
}

export function fetchLegacyDomains(): Promise<LegacyDomainResponse> {
  return getJson<LegacyDomainResponse>('/api/v1/migration/legacy-domains')
}
