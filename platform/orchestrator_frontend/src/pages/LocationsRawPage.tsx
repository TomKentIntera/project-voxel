import { useCallback, useEffect, useState } from 'react'
import Header from '../components/Header'
import { useAuth } from '../context/useAuth'
import { fetchLocationsCacheRaw } from '../lib/locationsApi'
import type { LocationsCachePayload } from '../lib/locationsApi'

export default function LocationsRawPage() {
  const { token } = useAuth()
  const [payload, setPayload] = useState<LocationsCachePayload | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const load = useCallback(async () => {
    if (!token) return

    setIsLoading(true)
    setError(null)

    try {
      const response = await fetchLocationsCacheRaw(token)
      setPayload(response)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load raw JSON')
    } finally {
      setIsLoading(false)
    }
  }, [token])

  useEffect(() => {
    void load()
  }, [load])

  return (
    <>
      <Header
        title="Locations Raw JSON"
        description="Raw locations.json payload from shared cache storage"
        actionLabel="Back to Locations"
        actionHref="/locations"
      />

      <div className="space-y-4 p-8">
        <div>
          <button
            type="button"
            onClick={() => void load()}
            className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
          >
            Refresh
          </button>
        </div>

        {error && (
          <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {error}
          </div>
        )}

        <div className="overflow-auto rounded-xl border border-slate-200 bg-slate-950 p-4">
          <pre className="text-xs leading-5 text-slate-100">
            {isLoading
              ? 'Loading...'
              : JSON.stringify(payload ?? {}, null, 2)}
          </pre>
        </div>
      </div>
    </>
  )
}

