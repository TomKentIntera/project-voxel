import { Navigate, useLocation } from 'react-router-dom'
import { useAuth } from '../context/useAuth'

export default function ProtectedRoute({
  children,
}: {
  children: React.ReactNode
}) {
  const { isAuthenticated, isHydrating } = useAuth()
  const location = useLocation()

  if (isHydrating) {
    return (
      <div className="flex h-screen items-center justify-center">
        <div className="text-sm text-slate-500">Checking your session...</div>
      </div>
    )
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace state={{ from: location }} />
  }

  return children
}

