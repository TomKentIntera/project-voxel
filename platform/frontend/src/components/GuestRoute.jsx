import { Navigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

function GuestRoute({ children }) {
  const { isAuthenticated, isHydrating } = useAuth()

  if (isHydrating) {
    return <p className="auth-loading">Checking your session...</p>
  }

  if (isAuthenticated) {
    return <Navigate to="/" replace />
  }

  return children
}

export default GuestRoute
