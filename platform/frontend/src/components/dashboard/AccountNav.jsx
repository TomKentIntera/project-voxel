import { Link } from 'react-router-dom'
import { useAuth } from '../../context/useAuth'
import { useBillingPortal } from '../../hooks/useBillingPortal'

/**
 * Sidebar navigation for the client dashboard area.
 * Mirrors the legacy client area navigation.
 */
export default function AccountNav() {
  const { logout } = useAuth()
  const { openBillingPortal, isOpeningBillingPortal, billingError } = useBillingPortal()

  return (
    <div className="account-nav">
      <a
        href="/dashboard"
        onClick={(event) => {
          event.preventDefault()
          openBillingPortal()
        }}
        aria-disabled={isOpeningBillingPortal}
      >
        <i className="fas fa-money-check"></i>{' '}
        {isOpeningBillingPortal ? 'Opening Billing...' : 'Billing'}
      </a>
      <a href="/helpdesk">
        <i className="fas fa-question-circle"></i> Get Support
      </a>
      <Link to="/plans">
        <i className="fas fa-server"></i> New Server
      </Link>
      <button type="button" className="account-nav-logout" onClick={logout}>
        <i className="fas fa-door-open"></i> Logout
      </button>
      {billingError ? <p className="text-red mt-10">{billingError}</p> : null}
    </div>
  )
}

