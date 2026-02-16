import { useQuery } from '@tanstack/react-query'
import { apiRequest } from '../utils/apiClient'

async function fetchBanner() {
  return apiRequest('/api/banner')
}

/**
 * React Query hook for fetching the homepage banner from the API.
 */
export function useBanner() {
  const query = useQuery({
    queryKey: ['banner'],
    queryFn: fetchBanner,
  })

  return {
    ...query,
    visible: query.data?.visible ?? false,
    content: query.data?.content ?? '',
  }
}

