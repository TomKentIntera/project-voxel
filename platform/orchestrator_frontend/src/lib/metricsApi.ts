import { apiRequest } from './apiClient'

export interface MetricItem {
  key: string
  label: string
  value: number
  format: 'number' | 'currency'
  prefix: string | null
  suffix: string | null
}

interface MetricsResponse {
  data: MetricItem[]
}

export function fetchMetrics(token: string): Promise<MetricsResponse> {
  return apiRequest<MetricsResponse>('/api/metrics', { token })
}

