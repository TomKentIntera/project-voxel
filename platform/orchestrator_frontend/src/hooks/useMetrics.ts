import { useCallback, useEffect, useState } from 'react'
import { fetchMetrics } from '../lib/metricsApi'
import type { MetricItem } from '../lib/metricsApi'
import { useAuth } from '../context/useAuth'

interface UseMetricsReturn {
  metrics: MetricItem[]
  isLoading: boolean
  error: string | null
  refresh: () => void
}

export function useMetrics(): UseMetricsReturn {
  const { token } = useAuth()
  const [metrics, setMetrics] = useState<MetricItem[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const load = useCallback(async () => {
    if (!token) return

    setIsLoading(true)
    setError(null)

    try {
      const response = await fetchMetrics(token)
      setMetrics(response.data)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load metrics')
    } finally {
      setIsLoading(false)
    }
  }, [token])

  useEffect(() => {
    load()
  }, [load])

  return { metrics, isLoading, error, refresh: load }
}

