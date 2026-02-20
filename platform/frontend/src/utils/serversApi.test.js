import { beforeEach, describe, expect, it, vi } from 'vitest'
import { apiRequest } from './apiClient'
import { fetchServerPanelUrl } from './serversApi'

vi.mock('./apiClient', () => ({
  apiRequest: vi.fn(),
}))

describe('fetchServerPanelUrl', () => {
  beforeEach(() => {
    apiRequest.mockReset()
  })

  it('returns the panel URL when API returns a valid payload', async () => {
    apiRequest.mockResolvedValue({
      panel_url: 'https://panel.example.test/server/abc123xy',
    })

    await expect(fetchServerPanelUrl('server-uuid', 'jwt-token')).resolves.toBe(
      'https://panel.example.test/server/abc123xy',
    )
    expect(apiRequest).toHaveBeenCalledWith('/api/servers/server-uuid/panel-url', {
      token: 'jwt-token',
    })
  })

  it('throws when API does not return panel_url', async () => {
    apiRequest.mockResolvedValue({})

    await expect(fetchServerPanelUrl('server-uuid', 'jwt-token')).rejects.toThrow(
      'Panel URL is missing from API response.',
    )
  })
})
