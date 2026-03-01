import { useCallback, useEffect, useMemo, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import Header from '../components/Header'
import { useAuth } from '../context/useAuth'
import { fetchNode, generateNodeProvisioningCommand } from '../lib/nodesApi'
import type {
  NodePerformanceSample,
  NodeProfile,
  NodeProvisioningCommand,
} from '../lib/nodesApi'

function formatDate(value: string | null): string {
  if (!value) return '-'

  return new Date(value).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

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

function formatPercent(value: number | null): string {
  if (value === null) return '-'
  return `${value.toFixed(2)}%`
}

function formatMetric(value: number | null, unit: string): string {
  if (value === null) return '-'
  if (unit === '%') return `${value.toFixed(2)}%`
  return `${value.toFixed(2)} ${unit}`
}

function parseDateMs(value: string): number | null {
  const timestampMs = new Date(value).getTime()
  return Number.isFinite(timestampMs) ? timestampMs : null
}

function formatBytesPerSecond(value: number): string {
  if (value < 1024) return `${value.toFixed(0)} B/s`
  if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB/s`
  if (value < 1024 * 1024 * 1024) return `${(value / (1024 * 1024)).toFixed(1)} MB/s`
  return `${(value / (1024 * 1024 * 1024)).toFixed(2)} GB/s`
}

interface MetricLineChartProps {
  title: string
  unit: string
  color: string
  from: string
  to: string
  samples: NodePerformanceSample[]
  valueSelector: (sample: NodePerformanceSample) => number
}

function MetricLineChart({
  title,
  unit,
  color,
  from,
  to,
  samples,
  valueSelector,
}: MetricLineChartProps) {
  const points = useMemo(() => {
    return samples
      .map((sample) => {
        const timestampMs = new Date(sample.recorded_at).getTime()
        const value = valueSelector(sample)

        if (!Number.isFinite(timestampMs) || !Number.isFinite(value)) {
          return null
        }

        return { timestampMs, value }
      })
      .filter((point): point is { timestampMs: number; value: number } => point !== null)
      .sort((a, b) => a.timestampMs - b.timestampMs)
  }, [samples, valueSelector])

  const width = 720
  const height = 240
  const paddingLeft = 42
  const paddingRight = 12
  const paddingTop = 12
  const paddingBottom = 28
  const plotWidth = width - paddingLeft - paddingRight
  const plotHeight = height - paddingTop - paddingBottom

  const maxValue = points.reduce((currentMax, point) => Math.max(currentMax, point.value), 0)
  const yMax = Math.max(10, Math.ceil(maxValue / 10) * 10)

  const firstPointMs = points.length > 0 ? points[0].timestampMs : 0
  const lastPointMs =
    points.length > 0 ? points[points.length - 1].timestampMs : firstPointMs + 24 * 60 * 60 * 1000
  const fromMs = parseDateMs(from) ?? firstPointMs
  let toMs = parseDateMs(to) ?? lastPointMs

  if (toMs <= fromMs) {
    toMs = fromMs + 1
  }

  const toX = (timestampMs: number): number =>
    paddingLeft + ((timestampMs - fromMs) / (toMs - fromMs)) * plotWidth
  const toY = (value: number): number =>
    paddingTop + (1 - Math.min(Math.max(value, 0), yMax) / yMax) * plotHeight

  const chartPoints = points.map((point) => ({
    x: toX(point.timestampMs),
    y: toY(point.value),
    value: point.value,
    timestampMs: point.timestampMs,
  }))

  const polylinePoints = chartPoints.map((point) => `${point.x},${point.y}`).join(' ')
  const gridValues = Array.from({ length: 5 }, (_, index) => (yMax / 4) * index).reverse()

  const latestPoint = points.length > 0 ? points[points.length - 1] : null
  const average =
    points.length > 0
      ? points.reduce((total, point) => total + point.value, 0) / points.length
      : null

  return (
    <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
      <div className="mb-3 flex flex-wrap items-start justify-between gap-3">
        <div>
          <h4 className="text-sm font-semibold text-slate-900">{title}</h4>
          <p className="text-xs text-slate-500">Last 24 hours</p>
        </div>
        <div className="text-right">
          <p className="text-xs text-slate-500">Latest</p>
          <p className="text-sm font-semibold text-slate-900">
            {formatMetric(latestPoint?.value ?? null, unit)}
          </p>
        </div>
      </div>

      {chartPoints.length === 0 ? (
        <p className="py-8 text-sm text-slate-500">
          No telemetry samples available for this metric in the selected window.
        </p>
      ) : (
        <svg
          viewBox={`0 0 ${width} ${height}`}
          role="img"
          aria-label={`${title} line chart`}
          className="w-full"
        >
          {gridValues.map((gridValue) => {
            const y = toY(gridValue)
            return (
              <g key={`${title}-${gridValue}`}>
                <line
                  x1={paddingLeft}
                  y1={y}
                  x2={width - paddingRight}
                  y2={y}
                  stroke="#e2e8f0"
                  strokeWidth={1}
                />
                <text
                  x={4}
                  y={y + 4}
                  fill="#64748b"
                  fontSize={10}
                  fontFamily="ui-sans-serif, system-ui"
                >
                  {formatMetric(gridValue, unit)}
                </text>
              </g>
            )
          })}

          <polyline
            points={polylinePoints}
            fill="none"
            stroke={color}
            strokeWidth={2.5}
            strokeLinejoin="round"
            strokeLinecap="round"
          />

          {chartPoints.map((point, index) => (
            <circle
              key={`${title}-${point.timestampMs}-${index}`}
              cx={point.x}
              cy={point.y}
              r={3}
              fill={color}
            />
          ))}
        </svg>
      )}

      <div className="mt-2 flex items-center justify-between text-xs text-slate-500">
        <span>{formatDateTime(from)}</span>
        <span>{formatDateTime(to)}</span>
      </div>
      <p className="mt-1 text-xs text-slate-500">
        Average: <span className="font-medium text-slate-700">{formatMetric(average, unit)}</span>
      </p>
    </div>
  )
}

function PerformanceTable({ samples }: { samples: NodePerformanceSample[] }) {
  const rows = useMemo(() => [...samples].reverse(), [samples])

  if (rows.length === 0) {
    return (
      <p className="text-sm text-slate-500">
        No telemetry samples were recorded for this node in the last 24 hours.
      </p>
    )
  }

  return (
    <div className="max-h-96 overflow-auto rounded-lg border border-slate-200">
      <table className="min-w-full divide-y divide-slate-200">
        <thead className="sticky top-0 bg-slate-50">
          <tr>
            <th className="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
              Timestamp
            </th>
            <th className="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-slate-500">
              CPU
            </th>
            <th className="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-slate-500">
              I/O Wait
            </th>
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-100 bg-white">
          {rows.map((sample) => (
            <tr key={sample.recorded_at}>
              <td className="whitespace-nowrap px-4 py-2 text-sm text-slate-700">
                {formatDateTime(sample.recorded_at)}
              </td>
              <td className="whitespace-nowrap px-4 py-2 text-right font-mono text-sm text-slate-700">
                {sample.cpu_pct.toFixed(2)}%
              </td>
              <td className="whitespace-nowrap px-4 py-2 text-right font-mono text-sm text-slate-700">
                {sample.iowait_pct.toFixed(2)}%
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}

export default function NodeProfilePage() {
  const { id } = useParams<{ id: string }>()
  const { token } = useAuth()
  const [node, setNode] = useState<NodeProfile | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [isGeneratingProvisioningCommand, setIsGeneratingProvisioningCommand] = useState(false)
  const [provisioningCommand, setProvisioningCommand] = useState<NodeProvisioningCommand | null>(null)
  const [provisioningError, setProvisioningError] = useState<string | null>(null)
  const [copyStatus, setCopyStatus] = useState<'idle' | 'copied' | 'failed'>('idle')

  const load = useCallback(async () => {
    if (!token || !id) return

    setIsLoading(true)
    setError(null)

    try {
      const response = await fetchNode(token, id)
      setNode(response.data)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load node')
    } finally {
      setIsLoading(false)
    }
  }, [token, id])

  useEffect(() => {
    load()
  }, [load])

  const handleGenerateProvisioningCommand = useCallback(async () => {
    if (!token || !id) return

    setIsGeneratingProvisioningCommand(true)
    setProvisioningError(null)
    setCopyStatus('idle')

    try {
      const response = await generateNodeProvisioningCommand(token, id)
      setProvisioningCommand(response.data)
    } catch (err) {
      setProvisioningError(err instanceof Error ? err.message : 'Failed to generate provisioning command')
    } finally {
      setIsGeneratingProvisioningCommand(false)
    }
  }, [id, token])

  const handleCopyProvisioningCommand = useCallback(async () => {
    if (!provisioningCommand) return

    try {
      if (!navigator?.clipboard?.writeText) {
        throw new Error('Clipboard API is unavailable')
      }

      await navigator.clipboard.writeText(provisioningCommand.command)
      setCopyStatus('copied')
    } catch {
      setCopyStatus('failed')
    }
  }, [provisioningCommand])

  if (isLoading) {
    return (
      <>
        <Header title="Node Details" />
        <div className="p-8">
          <div className="space-y-6">
            <div className="h-32 animate-pulse rounded-xl bg-slate-200" />
            <div className="h-64 animate-pulse rounded-xl bg-slate-200" />
          </div>
        </div>
      </>
    )
  }

  if (error || !node) {
    return (
      <>
        <Header title="Node Details" />
        <div className="p-8">
          <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {error ?? 'Node not found.'}
          </div>
          <Link
            to="/nodes"
            className="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-700"
          >
            <svg className="size-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
            Back to Nodes
          </Link>
        </div>
      </>
    )
  }

  const performance = node.performance_last_24h
  const sampleCount = performance.samples.length

  return (
    <>
      <Header title={node.name} description={node.id} />

      <div className="space-y-8 p-8">
        <Link
          to="/nodes"
          className="inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-700"
        >
          <svg className="size-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
          </svg>
          Back to Nodes
        </Link>

        <div className="rounded-xl border border-slate-200 bg-white p-6">
          <div className="flex flex-wrap items-start justify-between gap-3">
            <div>
              <h2 className="text-lg font-semibold text-slate-900">Node Information</h2>
              <p className="mt-1 text-sm text-slate-500">
                Generate a one-time bootstrap command to install Docker, Wings, and the node monitor.
              </p>
            </div>
            <button
              type="button"
              onClick={() => void handleGenerateProvisioningCommand()}
              disabled={isGeneratingProvisioningCommand}
              className="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
              {isGeneratingProvisioningCommand ? 'Generating...' : 'Generate Bootstrap Command'}
            </button>
          </div>

          <dl className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
              <dt className="text-xs font-medium uppercase tracking-wider text-slate-400">Region</dt>
              <dd className="mt-1 text-sm text-slate-900">{node.region}</dd>
            </div>
            <div>
              <dt className="text-xs font-medium uppercase tracking-wider text-slate-400">IP Address</dt>
              <dd className="mt-1 font-mono text-sm text-slate-900">{node.ip_address}</dd>
            </div>
            <div>
              <dt className="text-xs font-medium uppercase tracking-wider text-slate-400">Last Active</dt>
              <dd className="mt-1 text-sm text-slate-900">{formatDateTime(node.last_active_at)}</dd>
            </div>
            <div>
              <dt className="text-xs font-medium uppercase tracking-wider text-slate-400">Created</dt>
              <dd className="mt-1 text-sm text-slate-900">{formatDate(node.created_at)}</dd>
            </div>
          </dl>

          {provisioningError && (
            <div className="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              {provisioningError}
            </div>
          )}

          {provisioningCommand && (
            <div className="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
              <p className="text-sm font-medium text-amber-900">One-time bootstrap command</p>
              <p className="mt-1 text-xs text-amber-700">
                Expires at {formatDateTime(provisioningCommand.expires_at)}. Running it once invalidates the URL.
              </p>
              <p className="mt-2 break-all rounded bg-white px-3 py-2 font-mono text-xs text-slate-700">
                {provisioningCommand.command}
              </p>
              <div className="mt-3 flex flex-wrap items-center gap-3">
                <button
                  type="button"
                  onClick={() => void handleCopyProvisioningCommand()}
                  className="inline-flex items-center rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-xs font-medium text-amber-800 transition-colors hover:bg-amber-100"
                >
                  Copy command
                </button>
                {copyStatus === 'copied' && (
                  <span className="text-xs font-medium text-emerald-700">Copied to clipboard.</span>
                )}
                {copyStatus === 'failed' && (
                  <span className="text-xs font-medium text-red-700">
                    Copy failed. Copy manually from the command block.
                  </span>
                )}
              </div>
            </div>
          )}
        </div>

        <div className="rounded-xl border border-slate-200 bg-white p-6">
          <div className="flex flex-wrap items-start justify-between gap-3">
            <div>
              <h2 className="text-lg font-semibold text-slate-900">Performance (Last 24 Hours)</h2>
              <p className="text-sm text-slate-500">
                {sampleCount === 0
                  ? 'No samples in this time window.'
                  : `${sampleCount} sample${sampleCount === 1 ? '' : 's'} from ${formatDateTime(performance.from)} to ${formatDateTime(performance.to)}.`}
              </p>
            </div>
            <p className="text-xs text-slate-500">
              Latest sample: {formatDateTime(performance.latest.recorded_at)}
            </p>
          </div>

          <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
              <p className="text-xs font-medium uppercase tracking-wider text-slate-500">Latest CPU</p>
              <p className="mt-1 text-lg font-semibold text-slate-900">
                {formatPercent(performance.latest.cpu_pct)}
              </p>
            </div>
            <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
              <p className="text-xs font-medium uppercase tracking-wider text-slate-500">Latest I/O Wait</p>
              <p className="mt-1 text-lg font-semibold text-slate-900">
                {formatPercent(performance.latest.iowait_pct)}
              </p>
            </div>
            <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
              <p className="text-xs font-medium uppercase tracking-wider text-slate-500">Average CPU</p>
              <p className="mt-1 text-lg font-semibold text-slate-900">
                {formatPercent(performance.averages.cpu_pct)}
              </p>
            </div>
            <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
              <p className="text-xs font-medium uppercase tracking-wider text-slate-500">Average I/O Wait</p>
              <p className="mt-1 text-lg font-semibold text-slate-900">
                {formatPercent(performance.averages.iowait_pct)}
              </p>
            </div>
          </div>

          <div className="mt-5 grid grid-cols-1 gap-4 xl:grid-cols-2">
            <MetricLineChart
              title="CPU Usage"
              unit="%"
              color="#4f46e5"
              from={performance.from}
              to={performance.to}
              samples={performance.samples}
              valueSelector={(sample) => sample.cpu_pct}
            />
            <MetricLineChart
              title="I/O Wait"
              unit="%"
              color="#d97706"
              from={performance.from}
              to={performance.to}
              samples={performance.samples}
              valueSelector={(sample) => sample.iowait_pct}
            />
          </div>

          <div className="mt-6">
            <PerformanceTable samples={performance.samples} />
          </div>
        </div>

        <div className="rounded-xl border border-slate-200 bg-white">
          <div className="border-b border-slate-200 px-6 py-4">
            <h3 className="text-base font-semibold text-slate-900">Pterodactyl Allocations</h3>
            <p className="text-sm text-slate-500">
              {node.allocations_count} allocation{node.allocations_count === 1 ? '' : 's'} total,{' '}
              {node.assigned_allocations_count} assigned, {node.unassigned_allocations_count} unassigned.
            </p>
          </div>

          {node.allocations_error ? (
            <div className="px-6 py-5">
              <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                {node.allocations_error}
              </div>
            </div>
          ) : node.allocations.length === 0 ? (
            <p className="px-6 py-5 text-sm text-slate-500">
              No allocations were returned for this node.
            </p>
          ) : (
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-slate-200">
                <thead className="bg-slate-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                      Allocation
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                      Alias
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                      Assigned Server
                    </th>
                    <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">
                      Panel Server ID
                    </th>
                    <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">
                      Status
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {node.allocations.map((entry) => {
                    const key = String(entry.id ?? `${entry.ip ?? 'unknown'}-${entry.port ?? 'unknown'}`)

                    return (
                      <tr key={key} className="hover:bg-slate-50">
                        <td className="whitespace-nowrap px-6 py-4">
                          <p className="font-mono text-sm text-slate-900">
                            {(entry.ip ?? '-')}:{entry.port ?? '-'}
                          </p>
                        </td>
                        <td className="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                          {entry.alias ?? '-'}
                        </td>
                        <td className="whitespace-nowrap px-6 py-4">
                          {entry.server?.id ? (
                            <Link
                              to={`/servers/${entry.server.id}`}
                              className="text-sm font-medium text-indigo-600 hover:text-indigo-700"
                            >
                              {entry.server.name}
                            </Link>
                          ) : entry.ptero_server_id ? (
                            <p className="text-sm text-slate-700">Panel server #{entry.ptero_server_id}</p>
                          ) : (
                            <p className="text-sm text-slate-500">-</p>
                          )}
                        </td>
                        <td className="whitespace-nowrap px-6 py-4 text-right font-mono text-sm text-slate-700">
                          {entry.ptero_server_id ?? '-'}
                        </td>
                        <td className="whitespace-nowrap px-6 py-4 text-right text-sm">
                          <span
                            className={
                              entry.assigned
                                ? 'rounded-full bg-indigo-50 px-2.5 py-1 text-indigo-700'
                                : 'rounded-full bg-slate-100 px-2.5 py-1 text-slate-600'
                            }
                          >
                            {entry.assigned ? 'Assigned' : 'Available'}
                          </span>
                        </td>
                      </tr>
                    )
                  })}
                </tbody>
              </table>
            </div>
          )}
        </div>

        <div className="rounded-xl border border-slate-200 bg-white">
          <div className="border-b border-slate-200 px-6 py-4">
            <h3 className="text-base font-semibold text-slate-900">Servers on Node</h3>
            <p className="text-sm text-slate-500">
              {node.servers_count} server{node.servers_count === 1 ? '' : 's'} currently mapped to this node.
            </p>
          </div>

          {node.servers.length === 0 ? (
            <p className="px-6 py-5 text-sm text-slate-500">
              No server telemetry is currently associated with this node.
            </p>
          ) : (
            <div className="overflow-x-auto">
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
                      Status
                    </th>
                    <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">
                      Players
                    </th>
                    <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">
                      CPU
                    </th>
                    <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">
                      Write I/O
                    </th>
                    <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">
                      Last Reported
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {node.servers.map((entry) => (
                    <tr key={entry.server_id} className="hover:bg-slate-50">
                      <td className="whitespace-nowrap px-6 py-4">
                        {entry.server?.id ? (
                          <Link
                            to={`/servers/${entry.server.id}`}
                            className="text-sm font-medium text-indigo-600 hover:text-indigo-700"
                          >
                            {entry.server.name}
                          </Link>
                        ) : (
                          <p className="text-sm font-medium text-slate-900">{entry.server_id}</p>
                        )}
                        <p className="font-mono text-xs text-slate-500">
                          {entry.server?.uuid ?? entry.server_id}
                        </p>
                      </td>
                      <td className="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                        {entry.server?.owner?.name ?? entry.server?.owner?.email ?? '-'}
                      </td>
                      <td className="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                        {entry.server?.status ?? 'unknown'}
                      </td>
                      <td className="whitespace-nowrap px-6 py-4 text-right font-mono text-sm text-slate-700">
                        {entry.players_online ?? '-'}
                      </td>
                      <td className="whitespace-nowrap px-6 py-4 text-right font-mono text-sm text-slate-700">
                        {entry.cpu_pct.toFixed(2)}%
                      </td>
                      <td className="whitespace-nowrap px-6 py-4 text-right font-mono text-sm text-slate-700">
                        {formatBytesPerSecond(entry.io_write_bytes_per_s)}
                      </td>
                      <td className="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-500">
                        {formatDateTime(entry.last_reported_at)}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>
    </>
  )
}
