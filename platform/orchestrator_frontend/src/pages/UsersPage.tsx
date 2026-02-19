import Header from '../components/Header'

const mockUsers = [
  { id: 1, name: 'Alex Martinez', email: 'alex@example.com', servers: 3, plan: 'Premium', status: 'active', joined: '2025-11-14' },
  { id: 2, name: 'Sarah Chen', email: 'sarah@example.com', servers: 1, plan: 'Starter', status: 'active', joined: '2025-12-02' },
  { id: 3, name: 'Jordan Patel', email: 'jordan@example.com', servers: 5, plan: 'Enterprise', status: 'active', joined: '2025-10-29' },
  { id: 4, name: 'Sam Wilson', email: 'sam@example.com', servers: 0, plan: 'Starter', status: 'suspended', joined: '2026-01-08' },
  { id: 5, name: 'Taylor Kim', email: 'taylor@example.com', servers: 2, plan: 'Premium', status: 'active', joined: '2026-01-22' },
]

const statusColor: Record<string, string> = {
  active: 'bg-emerald-50 text-emerald-700',
  suspended: 'bg-red-50 text-red-700',
}

export default function UsersPage() {
  return (
    <>
      <Header title="Users" description="Manage platform users and accounts" />

      <div className="p-8">
        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white">
          <table className="min-w-full divide-y divide-slate-200">
            <thead className="bg-slate-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">User</th>
                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Plan</th>
                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Servers</th>
                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Status</th>
                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Joined</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {mockUsers.map((user) => (
                <tr key={user.id} className="hover:bg-slate-50">
                  <td className="whitespace-nowrap px-6 py-4">
                    <div>
                      <p className="text-sm font-medium text-slate-900">{user.name}</p>
                      <p className="text-sm text-slate-500">{user.email}</p>
                    </div>
                  </td>
                  <td className="whitespace-nowrap px-6 py-4 text-sm text-slate-600">{user.plan}</td>
                  <td className="whitespace-nowrap px-6 py-4 text-sm text-slate-600">{user.servers}</td>
                  <td className="whitespace-nowrap px-6 py-4">
                    <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${statusColor[user.status]}`}>
                      {user.status}
                    </span>
                  </td>
                  <td className="whitespace-nowrap px-6 py-4 text-sm text-slate-500">{user.joined}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </>
  )
}

