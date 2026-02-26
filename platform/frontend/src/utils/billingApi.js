import { apiRequest } from './apiClient'

/**
 * Create a Stripe billing portal session for the authenticated user.
 */
export async function fetchBillingPortalUrl(token) {
  const payload = await apiRequest('/api/billing/portal-session', {
    method: 'POST',
    token,
  })

  if (!payload || typeof payload.portal_url !== 'string' || payload.portal_url === '') {
    throw new Error('Billing portal URL is missing from API response.')
  }

  return payload.portal_url
}

