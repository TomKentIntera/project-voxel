import { beforeEach, describe, expect, it, vi } from 'vitest'
import { apiRequest } from './apiClient'
import { fetchBillingPortalUrl } from './billingApi'

vi.mock('./apiClient', () => ({
  apiRequest: vi.fn(),
}))

describe('fetchBillingPortalUrl', () => {
  beforeEach(() => {
    apiRequest.mockReset()
  })

  it('returns the portal URL when API returns a valid payload', async () => {
    apiRequest.mockResolvedValue({
      portal_url: 'https://billing.example.test/session/abc123',
    })

    await expect(fetchBillingPortalUrl('jwt-token')).resolves.toBe(
      'https://billing.example.test/session/abc123',
    )
    expect(apiRequest).toHaveBeenCalledWith('/api/billing/portal-session', {
      method: 'POST',
      token: 'jwt-token',
    })
  })

  it('throws when API does not return portal_url', async () => {
    apiRequest.mockResolvedValue({})

    await expect(fetchBillingPortalUrl('jwt-token')).rejects.toThrow(
      'Billing portal URL is missing from API response.',
    )
  })
})

