import { useQuery } from '@tanstack/react-query'
import { apiRequest } from '../utils/apiClient'
import { useAuth } from '../context/useAuth'

/**
 * Fetch the authenticated user's servers from the API.
 */
async function fetchServers(token) {
  const data = await apiRequest('/api/servers', { token })
  return data.servers ?? []
}

/**
 * React Query hook for the current user's servers.
 * Only enabled when the user is authenticated.
 */
export function useServers() {
  const { token, isAuthenticated } = useAuth()

  const query = useQuery({
    queryKey: ['servers', token],
    queryFn: () => fetchServers(token),
    enabled: isAuthenticated,
  })

  return {
    ...query,
    servers: query.data ?? [],
  }
}

