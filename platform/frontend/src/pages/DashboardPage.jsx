import { useAuth } from '../context/useAuth'

function DashboardPage() {
  const { user, logout } = useAuth()

  return (
    <main className="dashboard-card">
      <h1>Welcome back, {user?.name ?? 'user'}.</h1>
      <p>You are authenticated with JWT and ready for dashboard MVP work.</p>

      <dl className="profile-list">
        <div>
          <dt>Name</dt>
          <dd>{user?.name ?? '-'}</dd>
        </div>
        <div>
          <dt>Email</dt>
          <dd>{user?.email ?? '-'}</dd>
        </div>
      </dl>

      <button type="button" className="secondary-button" onClick={logout}>
        Log out
      </button>
    </main>
  )
}

export default DashboardPage
