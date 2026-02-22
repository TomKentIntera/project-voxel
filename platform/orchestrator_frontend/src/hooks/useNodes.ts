import { useCallback, useEffect, useRef, useState } from 'react'
import { useAuth } from '../context/useAuth'
import { fetchNodes } from '../lib/nodesApi'
import type { NodeListItem, NodesParams } from '../lib/nodesApi'
import type { PaginationMeta } from '../lib/usersApi'

interface UseNodesReturn {
  nodes: NodeListItem[]
  meta: PaginationMeta | null
  isLoading: boolean
  error: string | null
  params: NodesParams
  setSearch: (search: string) => void
  setPage: (page: number) => void
  reload: () => Promise<void>
}

const DEFAULT_PER_PAGE = 15

export function useNodes(): UseNodesReturn {
  const { token } = useAuth()
  const [nodes, setNodes] = useState<NodeListItem[]>([])
  const [meta, setMeta] = useState<PaginationMeta | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [params, setParams] = useState<NodesParams>({
    page: 1,
    per_page: DEFAULT_PER_PAGE,
  })
  const searchTimer = useRef<ReturnType<typeof setTimeout> | null>(null)

  const load = useCallback(async () => {
    if (!token) return

    setIsLoading(true)
    setError(null)

    try {
      const response = await fetchNodes(token, params)
      setNodes(response.data)
      setMeta(response.meta)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load nodes')
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

  const setPage = useCallback((page: number) => {
    setParams((prev) => ({ ...prev, page }))
  }, [])

  const reload = useCallback(async () => {
    await load()
  }, [load])

  return { nodes, meta, isLoading, error, params, setSearch, setPage, reload }
}
