import { useState } from 'react'
import Header from '../components/Header'
import Pagination from '../components/Pagination'
import { useUsers } from '../hooks/useUsers'

const roleBadge: Record<string, string> = {
  admin: 'bg-indigo-50 text-indigo-700',
  customer: 'bg-slate-100 text-slate-700',
}

export default function UsersPage() {
  const { users, meta, isLoading, error, setSearch, setRole, setPage } =
    useUsers()

  // Keep local search input value so it updates immediately (debounced in hook)
  const [searchValue, setSearchValue] = useState('')

  return (
    <>
      <Header
        title="Users"
        description="Manage platform users and accounts"
      />

      <div className="p-8">
        {/* Filters */}
        <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center">
          {/* Search */}
          <div className="relative flex-1">
            <svg
              className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400"
              fill="none"
              viewBox="0 0 24 24"
              strokeWidth={2}
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"
              />
            </svg>
            <input
              type="text"
              value={searchValue}
              onChange={(e) => {
                setSearchValue(e.target.value)
                setSearch(e.target.value)
              }}
              placeholder="Search by name or email…"
              className="w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            />
          </div>

          {/* Role dropdown */}
          <select
            onChange={(e) => setRole(e.target.value)}
            defaultValue=""
            className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          >
            <option value="">All roles</option>
            <option value="admin">Admin</option>
            <option value="customer">Customer</option>
          </select>
        </div>

        {/* Error */}
        {error && (
          <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {error}
          </div>
        )}

        {/* Table */}
        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white">
          <table className="min-w-full divide-y divide-slate-200">
            <thead className="bg-slate-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                  User
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                  Role
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                  Servers
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                  Joined
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {isLoading ? (
                Array.from({ length: 5 }).map((_, i) => (
                  <tr key={i}>
                    <td className="px-6 py-4">
                      <div className="space-y-2">
                        <div className="h-4 w-36 animate-pulse rounded bg-slate-200" />
                        <div className="h-3 w-48 animate-pulse rounded bg-slate-100" />
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <div className="h-5 w-16 animate-pulse rounded-full bg-slate-200" />
                    </td>
                    <td className="px-6 py-4">
                      <div className="h-4 w-6 animate-pulse rounded bg-slate-200" />
                    </td>
                    <td className="px-6 py-4">
                      <div className="h-4 w-24 animate-pulse rounded bg-slate-200" />
                    </td>
                  </tr>
                ))
              ) : users.length === 0 ? (
                <tr>
                  <td
                    colSpan={4}
                    className="px-6 py-12 text-center text-sm text-slate-500"
                  >
                    No users found.
                  </td>
                </tr>
              ) : (
                users.map((user) => (
                  <tr key={user.id} className="hover:bg-slate-50">
                    <td className="whitespace-nowrap px-6 py-4">
                      <div className="flex items-center gap-3">
                        <div className="flex size-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-600">
                          {user.first_name?.[0]?.toUpperCase() ??
                            user.name?.[0]?.toUpperCase() ??
                            '?'}
                        </div>
                        <div>
                          <p className="text-sm font-medium text-slate-900">
                            {user.name}
                          </p>
                          <p className="text-sm text-slate-500">{user.email}</p>
                        </div>
                      </div>
                    </td>
                    <td className="whitespace-nowrap px-6 py-4">
                      <span
                        className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ${roleBadge[user.role] ?? 'bg-slate-100 text-slate-700'}`}
                      >
                        {user.role}
                      </span>
                    </td>
                    <td className="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                      {user.servers_count}
                    </td>
                    <td className="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                      {new Date(user.created_at).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                      })}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>

          {/* Pagination */}
          {meta && <Pagination meta={meta} onPageChange={setPage} />}
        </div>
      </div>
    </>
  )
}
