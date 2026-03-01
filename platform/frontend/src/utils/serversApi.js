import { apiRequest } from './apiClient'

/**
 * Fetch a deep-link URL for the given server's panel page.
 */
export async function fetchServerPanelUrl(serverUuid, token) {
  const payload = await apiRequest(`/api/servers/${serverUuid}/panel-url`, { token })

  if (!payload || typeof payload.panel_url !== 'string' || payload.panel_url === '') {
    throw new Error('Panel URL is missing from API response.')
  }

  return payload.panel_url
}

/**
 * Create a pending server purchase and return the Stripe checkout URL.
 */
export async function createServerPurchaseSession(serverPayload, token) {
  const payload = await apiRequest('/api/servers/purchase', {
    method: 'POST',
    token,
    body: serverPayload,
  })

  if (!payload || typeof payload.checkout_url !== 'string' || payload.checkout_url === '') {
    throw new Error('Checkout URL is missing from API response.')
  }

  return payload
}

/**
 * Fetch provisioning/payment status for a specific server purchase flow.
 */
export async function fetchServerProvisioningStatus(serverUuid, token) {
  const payload = await apiRequest(`/api/servers/${serverUuid}/provisioning-status`, { token })

  if (!payload || typeof payload !== 'object') {
    throw new Error('Provisioning status payload is missing from API response.')
  }

  return payload
}

/**
 * Confirm Stripe return token and link checkout session to the pending server.
 */
export async function confirmServerPurchase(serverUuid, sessionId, token) {
  const payload = await apiRequest(`/api/servers/${serverUuid}/purchase-confirmation`, {
    method: 'POST',
    token,
    body: {
      session_id: sessionId,
    },
  })

  if (!payload || typeof payload.server_uuid !== 'string' || payload.server_uuid === '') {
    throw new Error('Purchase confirmation payload is missing from API response.')
  }

  return payload
}
