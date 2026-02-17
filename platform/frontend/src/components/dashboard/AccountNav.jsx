import { Link } from 'react-router-dom'
import { useAuth } from '../../context/useAuth'

/**
 * Sidebar navigation for the client dashboard area.
 * Mirrors the legacy client area navigation.
 */
export default function AccountNav() {
  const { logout } = useAuth()

  return (
    <div className="account-nav">
      <Link to="/dashboard/billing">
        <i className="fas fa-money-check"></i> Billing
      </Link>
      <a href="/helpdesk">
        <i className="fas fa-question-circle"></i> Get Support
      </a>
      <Link to="/plans">
        <i className="fas fa-server"></i> New Server
      </Link>
      <button type="button" className="account-nav-logout" onClick={logout}>
        <i className="fas fa-door-open"></i> Logout
      </button>
    </div>
  )
}

