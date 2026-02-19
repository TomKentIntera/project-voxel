import { Navigate, Route, Routes } from 'react-router-dom'
import { AuthProvider } from './context/AuthProvider'
import ProtectedRoute from './components/ProtectedRoute'
import Layout from './components/Layout'
import LoginPage from './pages/LoginPage'
import DashboardPage from './pages/DashboardPage'
import UsersPage from './pages/UsersPage'
import UserProfilePage from './pages/UserProfilePage'
import ServersPage from './pages/ServersPage'
import JobsPage from './pages/JobsPage'

function App() {
  return (
    <AuthProvider>
      <Routes>
        <Route path="/login" element={<LoginPage />} />
        <Route
          element={
            <ProtectedRoute>
              <Layout />
            </ProtectedRoute>
          }
        >
          <Route index element={<DashboardPage />} />
          <Route path="users" element={<UsersPage />} />
          <Route path="users/:id" element={<UserProfilePage />} />
          <Route path="servers" element={<ServersPage />} />
          <Route path="jobs" element={<JobsPage />} />
        </Route>
        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </AuthProvider>
  )
}

export default App
