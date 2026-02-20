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
