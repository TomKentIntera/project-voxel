import { useQuery } from '@tanstack/react-query'
import { apiRequest } from '../utils/apiClient'

/**
 * React Query hook for fetching FAQs from the API.
 *
 * @param {object} [options]
 * @param {boolean} [options.homepageOnly=true] - When true, only FAQs marked for the homepage are returned.
 */
export function useFaqs({ homepageOnly = true } = {}) {
  const url = homepageOnly ? '/api/faqs?homepage_only=true' : '/api/faqs'

  const query = useQuery({
    queryKey: ['faqs', homepageOnly ? 'homepage' : 'all'],
    queryFn: () => apiRequest(url).then((data) => data.faqs),
  })

  return {
    ...query,
    faqs: query.data ?? [],
  }
}

