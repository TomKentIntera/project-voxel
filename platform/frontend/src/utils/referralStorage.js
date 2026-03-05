const REFERRAL_CODE_STORAGE_KEY = 'platform.referral.code'

export function setStoredReferralCode(code) {
  const trimmedCode = String(code ?? '').trim().toUpperCase()
  if (!trimmedCode) {
    return
  }

  window.localStorage.setItem(REFERRAL_CODE_STORAGE_KEY, trimmedCode)
}

export function getStoredReferralCode() {
  return String(window.localStorage.getItem(REFERRAL_CODE_STORAGE_KEY) ?? '').trim().toUpperCase()
}

export function clearStoredReferralCode() {
  window.localStorage.removeItem(REFERRAL_CODE_STORAGE_KEY)
}
