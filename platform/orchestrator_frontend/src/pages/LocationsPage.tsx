import { Link } from 'react-router-dom'
import Header from '../components/Header'
import { useLocationsCache } from '../hooks/useLocationsCache'
import type { LocationCacheNode } from '../lib/locationsApi'

function formatMemoryMb(value: number): string {
  return `${value.toLocaleString()} MB`
}

function formatPercent(value: number): string {
  return `${value.toFixed(1)}%`
}

function parseLocationShort(shortCode: string): { region: string; country: string } {
  const [region = '', country = ''] = shortCode.split('.', 2)
  return {
    region: region.trim().toLowerCase(),
    country: country.trim().toLowerCase(),
  }
}

function countryCodeToFlagEmoji(countryCode: string): string {
  if (!/^[a-z]{2}$/i.test(countryCode)) {
    return '🌐'
  }

  const upper = countryCode.toUpperCase()
  const codePoints = [...upper].map((char) => 0x1f1e6 + (char.charCodeAt(0) - 65))

  return String.fromCodePoint(...codePoints)
}

function countryNameFromCode(countryCode: string): string {
  if (!/^[a-z]{2}$/i.test(countryCode)) {
    return countryCode.toUpperCase()
  }

  try {
    const displayNames = new Intl.DisplayNames(['en'], { type: 'region' })
    const resolved = displayNames.of(countryCode.toUpperCase())
    return resolved ?? countryCode.toUpperCase()
  } catch {
    return countryCode.toUpperCase()
  }
}

export default function LocationsPage() {
  const { payload, meta, isLoading, error, reload } = useLocationsCache()

  const nodesByLocation = new Map<string, LocationCacheNode[]>()
  for (const node of payload?.nodes ?? []) {
    const existing = nodesByLocation.get(node.location) ?? []
    existing.push(node)
    nodesByLocation.set(node.location, existing)
  }

  return (
    <>
      <Header
        title="Locations Cache"
        description="Location and node resource data from shared locations.json cache"
        actionLabel="View Raw JSON"
        actionHref="/locations/raw"
      />

      <div className="space-y-6 p-8">
        <div className="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3">
          <div className="text-sm text-slate-600">
            <p>
              Disk: <span className="font-medium text-slate-900">{meta?.disk ?? '-'}</span>
            </p>
            <p>
              Path: <span className="font-mono text-slate-900">{meta?.path ?? '-'}</span>
            </p>
          </div>
          <button
            type="button"
            onClick={() => void reload()}
            className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
          >
            Refresh
          </button>
        </div>

        {error && (
          <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {error}
          </div>
        )}

        <div className="space-y-4">
          {isLoading ? (
            <div className="rounded-xl border border-slate-200 bg-white p-8 text-sm text-slate-500">
              Loading locations...
            </div>
          ) : (payload?.locations ?? []).length === 0 ? (
            <div className="rounded-xl border border-slate-200 bg-white p-8 text-sm text-slate-500">
              No locations found in cache payload.
            </div>
          ) : (
            payload?.locations.map((location) => {
              const locationNodes = nodesByLocation.get(location.short) ?? []
              const parsed = parseLocationShort(location.short)
              const flag = countryCodeToFlagEmoji(parsed.country)
              const countryName = countryNameFromCode(parsed.country)
              const regionLabel = parsed.region !== '' ? parsed.region.toUpperCase() : '-'

              return (
                <div key={location.short} className="overflow-hidden rounded-xl border border-slate-200 bg-white">
                  <div className="border-b border-slate-200 px-5 py-4">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                      <div className="flex items-start gap-3">
                        <span className="mt-0.5 text-2xl" role="img" aria-label={`${countryName} flag`}>
                          {flag}
                        </span>
                        <div>
                          <h2 className="text-lg font-semibold text-slate-900">{countryName}</h2>
                          <p className="font-mono text-xs text-slate-500">
                            {location.short} <span className="text-slate-400">({regionLabel})</span>
                          </p>
                        </div>
                      </div>
                      <div className="grid grid-cols-2 gap-x-6 gap-y-1 text-sm text-slate-600 sm:grid-cols-3">
                        <span>Nodes: <strong>{location.nodeCount}</strong></span>
                        <span>Total: <strong>{formatMemoryMb(location.totalMemory)}</strong></span>
                        <span>Used: <strong>{formatMemoryMb(location.totalUsedMemory)}</strong></span>
                        <span>Free: <strong>{formatMemoryMb(location.totalFreeMemory)}</strong></span>
                        <span>Max Free Node: <strong>{formatMemoryMb(location.maxFreeMemory)}</strong></span>
                        <span>Used %: <strong>{formatPercent(location.totalMemoryUsedPercent)}</strong></span>
                      </div>
                    </div>
                  </div>

                  <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-slate-200">
                      <thead className="bg-slate-50">
                        <tr>
                          <th className="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                            Node
                          </th>
                          <th className="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                            FQDN
                          </th>
                          <th className="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-slate-500">
                            Memory
                          </th>
                          <th className="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-slate-500">
                            Allocated
                          </th>
                          <th className="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-slate-500">
                            Free
                          </th>
                          <th className="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-slate-500">
                            Used %
                          </th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-100 bg-white">
                        {locationNodes.length === 0 ? (
                          <tr>
                            <td colSpan={6} className="px-4 py-5 text-center text-sm text-slate-500">
                              No nodes listed for this location.
                            </td>
                          </tr>
                        ) : (
                          locationNodes.map((node) => (
                            <tr key={`${location.short}-${node.id}`}>
                              <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-900">
                                <div className="font-medium">{node.name}</div>
                                <div className="font-mono text-xs text-slate-500">{node.id}</div>
                              </td>
                              <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-600">
                                {node.fqdn}
                              </td>
                              <td className="whitespace-nowrap px-4 py-3 text-right font-mono text-sm text-slate-700">
                                {formatMemoryMb(node.memory)}
                              </td>
                              <td className="whitespace-nowrap px-4 py-3 text-right font-mono text-sm text-slate-700">
                                {formatMemoryMb(node.memoryAllocated)}
                              </td>
                              <td className="whitespace-nowrap px-4 py-3 text-right font-mono text-sm text-slate-700">
                                {formatMemoryMb(node.memoryFree)}
                              </td>
                              <td className="whitespace-nowrap px-4 py-3 text-right font-mono text-sm text-slate-700">
                                {formatPercent(node.memoryUsedPercent)}
                              </td>
                            </tr>
                          ))
                        )}
                      </tbody>
                    </table>
                  </div>
                </div>
              )
            })
          )}
        </div>

        <div className="text-sm text-slate-500">
          Need the raw payload? <Link className="text-indigo-600 hover:text-indigo-700" to="/locations/raw">Open raw JSON</Link>.
        </div>
      </div>
    </>
  )
}

