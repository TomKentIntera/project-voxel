import { useState } from 'react'
import { Link } from 'react-router-dom'
import Header from '../components/Header'
import Pagination from '../components/Pagination'
import { useServers } from '../hooks/useServers'

const statusBadge: Record<string, string> = {
  active: 'bg-emerald-50 text-emerald-700',
  provisioning: 'bg-blue-50 text-blue-700',
  provisioned: 'bg-blue-50 text-blue-700',
  new: 'bg-slate-100 text-slate-600',
  suspended: 'bg-red-50 text-red-700',
  cancelled: 'bg-slate-100 text-slate-500',
  deleted: 'bg-slate-100 text-slate-400',
  'past-due': 'bg-amber-50 text-amber-700',
}

export default function ServersPage() {
  const { servers, meta, isLoading, error, setSearch, setStatus, setPage } =
    useServers()
  const [searchValue, setSearchValue] = useState('')

  return (
    <>
      <Header
        title="Servers"
        description="Manage server instances across the platform"
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
              placeholder="Search by UUID, plan, status, or owner..."
              className="w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            />
          </div>

          {/* Status dropdown */}
          <select
            onChange={(e) => setStatus(e.target.value)}
            defaultValue=""
            className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          >
            <option value="">All statuses</option>
            <option value="new">New</option>
            <option value="provisioning">Provisioning</option>
            <option value="provisioned">Provisioned</option>
            <option value="active">Active</option>
            <option value="past-due">Past due</option>
            <option value="suspended">Suspended</option>
            <option value="cancelled">Cancelled</option>
            <option value="deleted">Deleted</option>
          </select>
        </div>

        {/* Error */}
        {error && (
          <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {error}
          </div>
        )}

        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white">
          <table className="min-w-full divide-y divide-slate-200">
            <thead className="bg-slate-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                  Server
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                  Owner
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                  Plan
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                  Status
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                  Events
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                  Created
                </th>
                <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">
                  <span className="sr-only">Actions</span>
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {isLoading ? (
                Array.from({ length: 5 }).map((_, i) => (
                  <tr key={i}>
                    <td className="px-6 py-4">
                      <div className="space-y-2">
                        <div className="h-4 w-32 animate-pulse rounded bg-slate-200" />
                        <div className="h-3 w-40 animate-pulse rounded bg-slate-100" />
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <div className="space-y-2">
                        <div className="h-4 w-24 animate-pulse rounded bg-slate-200" />
                        <div className="h-3 w-32 animate-pulse rounded bg-slate-100" />
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <div className="h-4 w-24 animate-pulse rounded bg-slate-200" />
                    </td>
                    <td className="px-6 py-4">
                      <div className="h-5 w-20 animate-pulse rounded-full bg-slate-200" />
                    </td>
                    <td className="px-6 py-4">
                      <div className="h-4 w-8 animate-pulse rounded bg-slate-200" />
                    </td>
                    <td className="px-6 py-4">
                      <div className="h-4 w-24 animate-pulse rounded bg-slate-200" />
                    </td>
                    <td className="px-6 py-4">
                      <div className="ml-auto h-4 w-12 animate-pulse rounded bg-slate-200" />
                    </td>
                  </tr>
                ))
              ) : servers.length === 0 ? (
                <tr>
                  <td
                    colSpan={7}
                    className="px-6 py-12 text-center text-sm text-slate-500"
                  >
                    No servers found.
                  </td>
                </tr>
              ) : (
                servers.map((server) => (
                  <tr key={server.id} className="hover:bg-slate-50">
                    <td className="whitespace-nowrap px-6 py-4">
                      <p className="text-sm font-medium text-slate-900">
                        {server.name}
                      </p>
                      <p className="font-mono text-xs text-slate-500">
                        {server.uuid}
                      </p>
                    </td>
                    <td className="whitespace-nowrap px-6 py-4">
                      {server.owner ? (
                        <>
                          <p className="text-sm text-slate-700">
                            {server.owner.name ?? 'Unknown owner'}
                          </p>
                          <p className="text-xs text-slate-500">
                            {server.owner.email ?? '-'}
                          </p>
                        </>
                      ) : (
                        <span className="text-sm text-slate-500">-</span>
                      )}
                    </td>
                    <td className="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                      {server.plan_title
                        ? `${server.plan_title}${typeof server.plan_ram === 'number' ? ` (${server.plan_ram} GB)` : ''}`
                        : (server.plan ?? '-')}
                    </td>
                    <td className="whitespace-nowrap px-6 py-4">
                      <span
                        className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ${statusBadge[server.status ?? ''] ?? 'bg-slate-100 text-slate-600'}`}
                      >
                        {server.status ?? 'unknown'}
                      </span>
                    </td>
                    <td className="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                      {server.events_count}
                    </td>
                    <td className="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                      {server.created_at
                        ? new Date(server.created_at).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric',
                          })
                        : '-'}
                    </td>
                    <td className="whitespace-nowrap px-6 py-4 text-right">
                      <Link
                        to={`/servers/${server.id}`}
                        title="View server details"
                        className="inline-flex rounded-lg p-2 text-slate-400 transition-colors hover:bg-slate-100 hover:text-indigo-600"
                      >
                        <svg
                          className="size-5"
                          fill="none"
                          viewBox="0 0 24 24"
                          strokeWidth={1.5}
                          stroke="currentColor"
                        >
                          <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"
                          />
                          <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                          />
                        </svg>
                      </Link>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>

          {meta && <Pagination meta={meta} onPageChange={setPage} />}
        </div>
      </div>
    </>
  )
}

