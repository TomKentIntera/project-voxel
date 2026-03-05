import { apiRequest } from './apiClient'

export async function fetchMyReferralSummary(token) {
  const payload = await apiRequest('/api/referrals/me', { token })

  if (!payload || typeof payload.referral !== 'object' || payload.referral === null) {
    throw new Error('Referral data is missing from API response.')
  }

  return payload.referral
}
