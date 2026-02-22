import { useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import Header from '../components/Header'
import Pagination from '../components/Pagination'
import { useAuth } from '../context/useAuth'
import { useNodes } from '../hooks/useNodes'
import { createNode, deleteNode } from '../lib/nodesApi'

function formatDateTime(value: string | null): string {
  if (!value) return '-'

  return new Date(value).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

export default function NodesPage() {
  const { token } = useAuth()
  const { nodes, meta, isLoading, error, setSearch, setPage, reload } = useNodes()
  const [searchValue, setSearchValue] = useState('')
  const [id, setId] = useState('')
  const [name, setName] = useState('')
  const [region, setRegion] = useState('')
  const [ipAddress, setIpAddress] = useState('')
  const [isCreating, setIsCreating] = useState(false)
  const [createError, setCreateError] = useState<string | null>(null)
  const [deleteError, setDeleteError] = useState<string | null>(null)
  const [deletingNodeId, setDeletingNodeId] = useState<string | null>(null)
  const [createdNodeToken, setCreatedNodeToken] = useState<{
    id: string
    token: string
  } | null>(null)

  const canCreate = useMemo(
    () => name.trim() !== '' && region.trim() !== '' && ipAddress.trim() !== '',
    [name, region, ipAddress],
  )

  const handleCreate = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    if (!token || !canCreate) return

    setIsCreating(true)
    setCreateError(null)

    try {
      const response = await createNode(token, {
        id: id.trim() || undefined,
        name: name.trim(),
        region: region.trim(),
        ip_address: ipAddress.trim(),
      })

      setCreatedNodeToken({
        id: response.data.id,
        token: response.data.node_token,
      })
      setId('')
      setName('')
      setRegion('')
      setIpAddress('')

      await reload()
    } catch (err) {
      setCreateError(err instanceof Error ? err.message : 'Failed to create node')
    } finally {
      setIsCreating(false)
    }
  }

  const handleDelete = async (nodeId: string) => {
    if (!token) return

    const shouldDelete = window.confirm(
      'Delete this node? This will remove the node and its telemetry data.',
    )
    if (!shouldDelete) return

    setDeletingNodeId(nodeId)
    setDeleteError(null)

    try {
      await deleteNode(token, nodeId)
      await reload()
    } catch (err) {
      setDeleteError(err instanceof Error ? err.message : 'Failed to delete node')
    } finally {
      setDeletingNodeId(null)
    }
  }

  return (
    <>
      <Header
        title="Nodes"
        description="View, add, and remove orchestrator telemetry nodes"
      />

      <div className="space-y-6 p-8">
        <div className="rounded-xl border border-slate-200 bg-white p-6">
          <h2 className="text-base font-semibold text-slate-900">Add Node</h2>
          <p className="mt-1 text-sm text-slate-500">
            Register a node and generate a one-time telemetry token.
          </p>

          <form onSubmit={handleCreate} className="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-4">
            <div>
              <label className="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-500">
                Node ID (optional)
              </label>
              <input
                type="text"
                value={id}
                onChange={(event) => setId(event.target.value)}
                placeholder="auto-generate"
                className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label className="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-500">
                Name
              </label>
              <input
                type="text"
                value={name}
                onChange={(event) => setName(event.target.value)}
                placeholder="Frankfurt Wings Node"
                className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label className="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-500">
                Region
              </label>
              <input
                type="text"
                value={region}
                onChange={(event) => setRegion(event.target.value)}
                placeholder="eu.de"
                className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label className="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-500">
                IP Address
              </label>
              <input
                type="text"
                value={ipAddress}
                onChange={(event) => setIpAddress(event.target.value)}
                placeholder="203.0.113.10"
                className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
            </div>
            <div className="lg:col-span-4">
              <button
                type="submit"
                disabled={!canCreate || isCreating}
                className="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
              >
                {isCreating ? 'Creating...' : 'Create Node'}
              </button>
            </div>
          </form>

          {createError && (
            <div className="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              {createError}
            </div>
          )}

          {createdNodeToken && (
            <div className="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
              <p className="text-sm font-medium text-amber-800">
                Node created: {createdNodeToken.id}
              </p>
              <p className="mt-1 text-xs text-amber-700">
                Save this token now. It is only shown once.
              </p>
              <p className="mt-2 break-all rounded bg-white px-3 py-2 font-mono text-xs text-slate-700">
                {createdNodeToken.token}
              </p>
            </div>
          )}
        </div>

        <div>
          <div className="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center">
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
                onChange={(event) => {
                  setSearchValue(event.target.value)
                  setSearch(event.target.value)
                }}
                placeholder="Search by node id, name, region, or IP..."
                className="w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
            </div>
          </div>

          {(error || deleteError) && (
            <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              {deleteError ?? error}
            </div>
          )}

          <div className="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <table className="min-w-full divide-y divide-slate-200">
              <thead className="bg-slate-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                    Node
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                    Region
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                    IP Address
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                    Last Active
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
                  Array.from({ length: 5 }).map((_, index) => (
                    <tr key={index}>
                      <td className="px-6 py-4">
                        <div className="space-y-2">
                          <div className="h-4 w-28 animate-pulse rounded bg-slate-200" />
                          <div className="h-3 w-48 animate-pulse rounded bg-slate-100" />
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <div className="h-4 w-16 animate-pulse rounded bg-slate-200" />
                      </td>
                      <td className="px-6 py-4">
                        <div className="h-4 w-28 animate-pulse rounded bg-slate-200" />
                      </td>
                      <td className="px-6 py-4">
                        <div className="h-4 w-32 animate-pulse rounded bg-slate-200" />
                      </td>
                      <td className="px-6 py-4">
                        <div className="h-4 w-32 animate-pulse rounded bg-slate-200" />
                      </td>
                      <td className="px-6 py-4">
                        <div className="ml-auto h-4 w-20 animate-pulse rounded bg-slate-200" />
                      </td>
                    </tr>
                  ))
                ) : nodes.length === 0 ? (
                  <tr>
                    <td colSpan={6} className="px-6 py-12 text-center text-sm text-slate-500">
                      No nodes found.
                    </td>
                  </tr>
                ) : (
                  nodes.map((node) => (
                    <tr key={node.id} className="hover:bg-slate-50">
                      <td className="whitespace-nowrap px-6 py-4">
                        <p className="text-sm font-medium text-slate-900">{node.name}</p>
                        <p className="font-mono text-xs text-slate-500">{node.id}</p>
                      </td>
                      <td className="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                        {node.region}
                      </td>
                      <td className="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                        {node.ip_address}
                      </td>
                      <td className="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                        {formatDateTime(node.last_active_at)}
                      </td>
                      <td className="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                        {formatDateTime(node.created_at)}
                      </td>
                      <td className="whitespace-nowrap px-6 py-4 text-right">
                        <div className="inline-flex items-center gap-2">
                          <Link
                            to={`/nodes/${encodeURIComponent(node.id)}`}
                            className="rounded-lg px-2.5 py-1.5 text-xs font-medium text-indigo-600 transition-colors hover:bg-indigo-50 hover:text-indigo-700"
                          >
                            View
                          </Link>
                          <button
                            type="button"
                            disabled={deletingNodeId === node.id}
                            onClick={() => void handleDelete(node.id)}
                            className="rounded-lg px-2.5 py-1.5 text-xs font-medium text-red-600 transition-colors hover:bg-red-50 hover:text-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                          >
                            {deletingNodeId === node.id ? 'Deleting...' : 'Delete'}
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>

            {meta && <Pagination meta={meta} onPageChange={setPage} />}
          </div>
        </div>
      </div>
    </>
  )
}
