import { useCallback, useEffect, useState } from 'react'
import { useAuth } from '../context/useAuth'
import { fetchLocationsCache } from '../lib/locationsApi'
import type { LocationsCachePayload } from '../lib/locationsApi'

interface LocationsCacheMeta {
  disk: string
  path: string
  location_count: number
  node_count: number
}

interface UseLocationsCacheReturn {
  payload: LocationsCachePayload | null
  meta: LocationsCacheMeta | null
  isLoading: boolean
  error: string | null
  reload: () => Promise<void>
}

export function useLocationsCache(): UseLocationsCacheReturn {
  const { token } = useAuth()
  const [payload, setPayload] = useState<LocationsCachePayload | null>(null)
  const [meta, setMeta] = useState<LocationsCacheMeta | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const load = useCallback(async () => {
    if (!token) return

    setIsLoading(true)
    setError(null)

    try {
      const response = await fetchLocationsCache(token)
      setPayload(response.data)
      setMeta(response.meta)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load locations cache')
    } finally {
      setIsLoading(false)
    }
  }, [token])

  useEffect(() => {
    void load()
  }, [load])

  const reload = useCallback(async () => {
    await load()
  }, [load])

  return {
    payload,
    meta,
    isLoading,
    error,
    reload,
  }
}

