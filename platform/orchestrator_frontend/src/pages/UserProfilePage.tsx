import { useCallback, useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import Header from '../components/Header'
import { useAuth } from '../context/useAuth'
import { fetchUser } from '../lib/usersApi'
import type { UserProfile } from '../lib/usersApi'

const roleBadge: Record<string, string> = {
  admin: 'bg-indigo-50 text-indigo-700',
  customer: 'bg-slate-100 text-slate-700',
}

const serverStatusBadge: Record<string, string> = {
  active: 'bg-emerald-50 text-emerald-700',
  provisioning: 'bg-blue-50 text-blue-700',
  provisioned: 'bg-blue-50 text-blue-700',
  new: 'bg-slate-100 text-slate-600',
  suspended: 'bg-red-50 text-red-700',
  cancelled: 'bg-slate-100 text-slate-500',
  deleted: 'bg-slate-100 text-slate-400',
  'past-due': 'bg-amber-50 text-amber-700',
}

export default function UserProfilePage() {
  const { id } = useParams<{ id: string }>()
  const { token } = useAuth()
  const [user, setUser] = useState<UserProfile | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const load = useCallback(async () => {
    if (!token || !id) return

    setIsLoading(true)
    setError(null)

    try {
      const response = await fetchUser(token, Number(id))
      setUser(response.data)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load user')
    } finally {
      setIsLoading(false)
    }
  }, [token, id])

  useEffect(() => {
    load()
  }, [load])

  if (isLoading) {
    return (
      <>
        <Header title="User Profile" />
        <div className="p-8">
          <div className="space-y-6">
            <div className="h-32 animate-pulse rounded-xl bg-slate-200" />
            <div className="h-48 animate-pulse rounded-xl bg-slate-200" />
          </div>
        </div>
      </>
    )
  }

  if (error || !user) {
    return (
      <>
        <Header title="User Profile" />
        <div className="p-8">
          <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {error ?? 'User not found.'}
          </div>
          <Link
            to="/users"
            className="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-700"
          >
            <svg className="size-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
            Back to Users
          </Link>
        </div>
      </>
    )
  }

  return (
    <>
      <Header
        title={user.name}
        description={user.email}
      />

      <div className="p-8">
        {/* Back link */}
        <Link
          to="/users"
          className="mb-6 inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-700"
        >
          <svg className="size-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
          </svg>
          Back to Users
        </Link>

        {/* Profile card */}
        <div className="rounded-xl border border-slate-200 bg-white p-6">
          <div className="flex items-start gap-5">
            <div className="flex size-14 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-lg font-semibold text-indigo-700">
              {user.first_name?.[0]?.toUpperCase() ?? user.name?.[0]?.toUpperCase() ?? '?'}
            </div>
            <div className="min-w-0 flex-1">
              <div className="flex items-center gap-3">
                <h2 className="text-lg font-semibold text-slate-900">
                  {user.name}
                </h2>
                <span
                  className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ${roleBadge[user.role] ?? 'bg-slate-100 text-slate-700'}`}
                >
                  {user.role}
                </span>
              </div>
              <p className="mt-0.5 text-sm text-slate-500">@{user.username}</p>
            </div>
          </div>

          <dl className="mt-6 grid grid-cols-1 gap-4 border-t border-slate-100 pt-6 sm:grid-cols-3">
            <div>
              <dt className="text-xs font-medium uppercase tracking-wider text-slate-400">
                Email
              </dt>
              <dd className="mt-1 text-sm text-slate-900">{user.email}</dd>
            </div>
            <div>
              <dt className="text-xs font-medium uppercase tracking-wider text-slate-400">
                Servers
              </dt>
              <dd className="mt-1 text-sm text-slate-900">
                {user.servers_count}
              </dd>
            </div>
            <div>
              <dt className="text-xs font-medium uppercase tracking-wider text-slate-400">
                Joined
              </dt>
              <dd className="mt-1 text-sm text-slate-900">
                {new Date(user.created_at).toLocaleDateString('en-US', {
                  year: 'numeric',
                  month: 'long',
                  day: 'numeric',
                })}
              </dd>
            </div>
          </dl>
        </div>

        {/* Servers */}
        <div className="mt-8">
          <div className="rounded-xl border border-slate-200 bg-white">
            <div className="border-b border-slate-200 px-6 py-4">
              <h3 className="text-base font-semibold text-slate-900">
                Servers
              </h3>
              <p className="text-sm text-slate-500">
                {user.servers.length === 0
                  ? 'This user has no servers.'
                  : `${user.servers.length} server${user.servers.length === 1 ? '' : 's'}`}
              </p>
            </div>

            {user.servers.length > 0 && (
              <table className="min-w-full divide-y divide-slate-200">
                <thead className="bg-slate-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                      UUID
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                      Plan
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                      Status
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                      Created
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {user.servers.map((server) => (
                    <tr key={server.id} className="hover:bg-slate-50">
                      <td className="whitespace-nowrap px-6 py-4 font-mono text-sm text-slate-600">
                        {server.uuid ?? '—'}
                      </td>
                      <td className="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                        {server.plan ?? '—'}
                      </td>
                      <td className="whitespace-nowrap px-6 py-4">
                        <span
                          className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${serverStatusBadge[server.status ?? ''] ?? 'bg-slate-100 text-slate-600'}`}
                        >
                          {server.status ?? 'unknown'}
                        </span>
                      </td>
                      <td className="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                        {new Date(server.created_at).toLocaleDateString(
                          'en-US',
                          {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric',
                          },
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </div>
        </div>
      </div>
    </>
  )
}

