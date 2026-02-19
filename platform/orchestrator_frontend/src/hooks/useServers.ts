import { useCallback, useEffect, useRef, useState } from 'react'
import { fetchServers } from '../lib/serversApi'
import type {
  ServerListItem,
  ServersParams,
} from '../lib/serversApi'
import type { PaginationMeta } from '../lib/usersApi'
import { useAuth } from '../context/useAuth'

interface UseServersReturn {
  servers: ServerListItem[]
  meta: PaginationMeta | null
  isLoading: boolean
  error: string | null
  params: ServersParams
  setSearch: (search: string) => void
  setStatus: (status: string) => void
  setPage: (page: number) => void
}

const DEFAULT_PER_PAGE = 15

export function useServers(): UseServersReturn {
  const { token } = useAuth()
  const [servers, setServers] = useState<ServerListItem[]>([])
  const [meta, setMeta] = useState<PaginationMeta | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [params, setParams] = useState<ServersParams>({
    page: 1,
    per_page: DEFAULT_PER_PAGE,
  })
  const searchTimer = useRef<ReturnType<typeof setTimeout> | null>(null)

  const load = useCallback(async () => {
    if (!token) return

    setIsLoading(true)
    setError(null)

    try {
      const response = await fetchServers(token, params)
      setServers(response.data)
      setMeta(response.meta)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load servers')
    } finally {
      setIsLoading(false)
    }
  }, [token, params])

  useEffect(() => {
    load()
  }, [load])

  const setSearch = useCallback((search: string) => {
    if (searchTimer.current) clearTimeout(searchTimer.current)

    searchTimer.current = setTimeout(() => {
      setParams((prev) => ({
        ...prev,
        search: search || undefined,
        page: 1,
      }))
    }, 300)
  }, [])

  const setStatus = useCallback((status: string) => {
    setParams((prev) => ({
      ...prev,
      status: status || undefined,
      page: 1,
    }))
  }, [])

  const setPage = useCallback((page: number) => {
    setParams((prev) => ({ ...prev, page }))
  }, [])

  return { servers, meta, isLoading, error, params, setSearch, setStatus, setPage }
}
