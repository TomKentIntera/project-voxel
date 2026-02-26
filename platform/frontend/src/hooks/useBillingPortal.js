import { useState } from 'react'
import { useAuth } from '../context/useAuth'
import { fetchBillingPortalUrl } from '../utils/billingApi'
import { getErrorMessage } from '../utils/getErrorMessage'

export function useBillingPortal() {
  const { token } = useAuth()
  const [isOpeningBillingPortal, setIsOpeningBillingPortal] = useState(false)
  const [billingError, setBillingError] = useState('')

  async function openBillingPortal() {
    if (!token || isOpeningBillingPortal) {
      return
    }

    setBillingError('')
    setIsOpeningBillingPortal(true)

    try {
      const billingPortalUrl = await fetchBillingPortalUrl(token)
      window.location.assign(billingPortalUrl)
    } catch (error) {
      setBillingError(getErrorMessage(error, 'Unable to open billing right now.'))
    } finally {
      setIsOpeningBillingPortal(false)
    }
  }

  return {
    openBillingPortal,
    isOpeningBillingPortal,
    billingError,
  }
}

