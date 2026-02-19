import { useCallback, useEffect, useRef, useState } from 'react'
import { fetchUsers } from '../lib/usersApi'
import type { PaginationMeta, UserItem, UsersParams } from '../lib/usersApi'
import { useAuth } from '../context/useAuth'

interface UseUsersReturn {
  users: UserItem[]
  meta: PaginationMeta | null
  isLoading: boolean
  error: string | null
  params: UsersParams
  setSearch: (search: string) => void
  setRole: (role: string) => void
  setPage: (page: number) => void
}

const DEFAULT_PER_PAGE = 15

export function useUsers(): UseUsersReturn {
  const { token } = useAuth()
  const [users, setUsers] = useState<UserItem[]>([])
  const [meta, setMeta] = useState<PaginationMeta | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [params, setParams] = useState<UsersParams>({
    page: 1,
    per_page: DEFAULT_PER_PAGE,
  })

  // Debounce timer ref for search
  const searchTimer = useRef<ReturnType<typeof setTimeout> | null>(null)

  const load = useCallback(async () => {
    if (!token) return

    setIsLoading(true)
    setError(null)

    try {
      const response = await fetchUsers(token, params)
      setUsers(response.data)
      setMeta(response.meta)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load users')
    } finally {
      setIsLoading(false)
    }
  }, [token, params])

  useEffect(() => {
    load()
  }, [load])

  const setSearch = useCallback((search: string) => {
    // Debounce search by 300ms
    if (searchTimer.current) clearTimeout(searchTimer.current)
    searchTimer.current = setTimeout(() => {
      setParams((prev) => ({ ...prev, search: search || undefined, page: 1 }))
    }, 300)
  }, [])

  const setRole = useCallback((role: string) => {
    setParams((prev) => ({ ...prev, role: role || undefined, page: 1 }))
  }, [])

  const setPage = useCallback((page: number) => {
    setParams((prev) => ({ ...prev, page }))
  }, [])

  return { users, meta, isLoading, error, params, setSearch, setRole, setPage }
}

