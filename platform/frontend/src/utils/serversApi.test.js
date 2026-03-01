import { beforeEach, describe, expect, it, vi } from 'vitest'
import { apiRequest } from './apiClient'
import {
  confirmServerPurchase,
  createServerPurchaseSession,
  fetchServerPanelUrl,
  fetchServerProvisioningStatus,
} from './serversApi'

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

describe('createServerPurchaseSession', () => {
  beforeEach(() => {
    apiRequest.mockReset()
  })

  it('returns checkout payload when API returns a valid checkout_url', async () => {
    apiRequest.mockResolvedValue({
      checkout_url: 'https://checkout.stripe.test/c/pay/cs_test_123',
      server_uuid: 'server-uuid',
    })

    const serverPayload = {
      plan: 'parrot',
      name: 'My Server',
      location: 'de',
      minecraft_version: '1.21.1',
      type: 'paper',
    }

    await expect(createServerPurchaseSession(serverPayload, 'jwt-token')).resolves.toEqual({
      checkout_url: 'https://checkout.stripe.test/c/pay/cs_test_123',
      server_uuid: 'server-uuid',
    })

    expect(apiRequest).toHaveBeenCalledWith('/api/servers/purchase', {
      method: 'POST',
      token: 'jwt-token',
      body: serverPayload,
    })
  })

  it('throws when API does not return checkout_url', async () => {
    apiRequest.mockResolvedValue({})

    await expect(createServerPurchaseSession({}, 'jwt-token')).rejects.toThrow(
      'Checkout URL is missing from API response.',
    )
  })
})

describe('fetchServerProvisioningStatus', () => {
  beforeEach(() => {
    apiRequest.mockReset()
  })

  it('returns provisioning status payload from API', async () => {
    apiRequest.mockResolvedValue({
      server_uuid: 'server-uuid',
      payment_confirmed: true,
      initialised: false,
      provisioned: false,
      status: 'provisioning',
      panel_url: null,
    })

    await expect(fetchServerProvisioningStatus('server-uuid', 'jwt-token')).resolves.toEqual({
      server_uuid: 'server-uuid',
      payment_confirmed: true,
      initialised: false,
      provisioned: false,
      status: 'provisioning',
      panel_url: null,
    })

    expect(apiRequest).toHaveBeenCalledWith('/api/servers/server-uuid/provisioning-status', {
      token: 'jwt-token',
    })
  })
})

describe('confirmServerPurchase', () => {
  beforeEach(() => {
    apiRequest.mockReset()
  })

  it('posts the session token and returns payload', async () => {
    apiRequest.mockResolvedValue({
      server_uuid: 'server-uuid',
      stripe_subscription_id: 'sub_123',
      checkout_status: 'complete',
      payment_status: 'paid',
    })

    await expect(
      confirmServerPurchase('server-uuid', 'cs_test_123', 'jwt-token'),
    ).resolves.toEqual({
      server_uuid: 'server-uuid',
      stripe_subscription_id: 'sub_123',
      checkout_status: 'complete',
      payment_status: 'paid',
    })

    expect(apiRequest).toHaveBeenCalledWith('/api/servers/server-uuid/purchase-confirmation', {
      method: 'POST',
      token: 'jwt-token',
      body: {
        session_id: 'cs_test_123',
      },
    })
  })
})
