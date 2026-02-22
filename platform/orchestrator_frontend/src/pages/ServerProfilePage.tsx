import { useCallback, useEffect, useMemo, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import Header from '../components/Header'
import { useAuth } from '../context/useAuth'
import { fetchServer } from '../lib/serversApi'
import type {
  ServerEventItem,
  ServerPerformanceSample,
  ServerProfile,
} from '../lib/serversApi'

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

function formatDate(date: string | null): string {
  if (!date) return '-'

  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

function formatDateTime(date: string | null): string {
  if (!date) return 'Unknown time'

  return new Date(date).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

function parseDateMs(value: string): number | null {
  const timestampMs = new Date(value).getTime()
  return Number.isFinite(timestampMs) ? timestampMs : null
}

type MetricKind = 'percent' | 'count' | 'bytesPerSecond'

function formatMetricValue(value: number | null, kind: MetricKind): string {
  if (value === null) return '-'

  if (kind === 'percent') {
    return `${value.toFixed(2)}%`
  }

  if (kind === 'bytesPerSecond') {
    return formatBytesPerSecond(value)
  }

  return value.toFixed(2)
}

function formatBytesPerSecond(value: number): string {
  if (value < 1024) return `${value.toFixed(0)} B/s`
  if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB/s`
  if (value < 1024 * 1024 * 1024) return `${(value / (1024 * 1024)).toFixed(1)} MB/s`
  return `${(value / (1024 * 1024 * 1024)).toFixed(2)} GB/s`
}

interface MetricLineChartProps {
  title: string
  kind: MetricKind
  color: string
  from: string
  to: string
  samples: ServerPerformanceSample[]
  valueSelector: (sample: ServerPerformanceSample) => number | null
}

function MetricLineChart({
  title,
  kind,
  color,
  from,
  to,
  samples,
  valueSelector,
}: MetricLineChartProps) {
  const points = useMemo(() => {
    return samples
      .map((sample) => {
        const timestampMs = parseDateMs(sample.recorded_at)
        const rawValue = valueSelector(sample)

        if (timestampMs === null || rawValue === null || !Number.isFinite(rawValue)) {
          return null
        }

        return { timestampMs, value: rawValue }
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
    points.length > 0
      ? points[points.length - 1].timestampMs
      : firstPointMs + 24 * 60 * 60 * 1000
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
            {formatMetricValue(latestPoint?.value ?? null, kind)}
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
                  {formatMetricValue(gridValue, kind)}
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
        Average:{' '}
        <span className="font-medium text-slate-700">
          {formatMetricValue(average, kind)}
        </span>
      </p>
    </div>
  )
}

function formatMetaValue(value: unknown): string {
  if (value === null) return 'null'
  if (value === undefined) return 'undefined'
  if (
    typeof value === 'string' ||
    typeof value === 'number' ||
    typeof value === 'boolean'
  ) {
    return String(value)
  }

  try {
    const encoded = JSON.stringify(value)
    return encoded ?? String(value)
  } catch {
    return '[unserializable]'
  }
}

function EventTimelineItem({
  event,
  isLast,
}: {
  event: ServerEventItem
  isLast: boolean
}) {
  const metaEntries = Object.entries(event.meta)

  return (
    <li className="flex gap-4">
      <div className="flex shrink-0 flex-col items-center">
        <span className="mt-1.5 size-2.5 rounded-full bg-indigo-500" />
        {!isLast && <span className="mt-1 w-px flex-1 bg-slate-200" />}
      </div>
      <div className="min-w-0 flex-1 pb-6">
        <div className="flex flex-wrap items-center gap-2">
          <p className="text-sm font-semibold text-slate-900">{event.label}</p>
          <span className="rounded-full bg-slate-100 px-2 py-0.5 font-mono text-xs text-slate-500">
            {event.type}
          </span>
        </div>
        <p className="mt-1 text-xs text-slate-500">
          {event.actor?.name ?? 'System'} - {formatDateTime(event.created_at)}
        </p>

        {metaEntries.length > 0 && (
          <dl className="mt-3 grid grid-cols-1 gap-1 rounded-lg bg-slate-50 p-3 sm:grid-cols-2">
            {metaEntries.map(([key, value]) => (
              <div key={key} className="truncate text-xs text-slate-600">
                <dt className="mr-1 inline font-medium uppercase text-slate-400">
                  {key}:
                </dt>
                <dd className="inline">{formatMetaValue(value)}</dd>
              </div>
            ))}
          </dl>
        )}
      </div>
    </li>
  )
}

export default function ServerProfilePage() {
  const { id } = useParams<{ id: string }>()
  const { token } = useAuth()
  const [server, setServer] = useState<ServerProfile | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const load = useCallback(async () => {
    if (!token || !id) return

    setIsLoading(true)
    setError(null)

    try {
      const response = await fetchServer(token, Number(id))
      setServer(response.data)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load server')
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
        <Header title="Server Details" />
        <div className="p-8">
          <div className="space-y-6">
            <div className="h-32 animate-pulse rounded-xl bg-slate-200" />
            <div className="h-64 animate-pulse rounded-xl bg-slate-200" />
          </div>
        </div>
      </>
    )
  }

  if (error || !server) {
    return (
      <>
        <Header title="Server Details" />
        <div className="p-8">
          <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {error ?? 'Server not found.'}
          </div>
          <Link
            to="/servers"
            className="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-700"
          >
            <svg
              className="size-4"
              fill="none"
              viewBox="0 0 24 24"
              strokeWidth={2}
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M15.75 19.5 8.25 12l7.5-7.5"
              />
            </svg>
            Back to Servers
          </Link>
        </div>
      </>
    )
  }

  return (
    <>
      <Header title={server.name} description={server.uuid} />

      <div className="p-8">
        <Link
          to="/servers"
          className="mb-6 inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-700"
        >
          <svg
            className="size-4"
            fill="none"
            viewBox="0 0 24 24"
            strokeWidth={2}
            stroke="currentColor"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              d="M15.75 19.5 8.25 12l7.5-7.5"
            />
          </svg>
          Back to Servers
        </Link>

        <div className="rounded-xl border border-slate-200 bg-white p-6">
          <div className="flex items-start gap-5">
            <div className="flex size-14 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-700">
              <svg
                className="size-7"
                fill="none"
                viewBox="0 0 24 24"
                strokeWidth={1.5}
                stroke="currentColor"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.737 5.1a3.375 3.375 0 0 1 2.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 0 1 .9 2.7m0 0a3 3 0 0 1-3 3m0 3h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Zm-3 6h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Z"
                />
              </svg>
            </div>
            <div className="min-w-0 flex-1">
              <div className="flex flex-wrap items-center gap-3">
                <h2 className="text-lg font-semibold text-slate-900">
                  {server.name}
                </h2>
                <span
                  className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ${statusBadge[server.status ?? ''] ?? 'bg-slate-100 text-slate-700'}`}
                >
                  {server.status ?? 'unknown'}
                </span>
              </div>
              <p className="mt-0.5 font-mono text-sm text-slate-500">
                {server.uuid}
              </p>
            </div>
          </div>

          <dl className="mt-6 grid grid-cols-1 gap-4 border-t border-slate-100 pt-6 sm:grid-cols-3">
            <div>
              <dt className="text-xs font-medium uppercase tracking-wider text-slate-400">
                Owner
              </dt>
              <dd className="mt-1 text-sm text-slate-900">
                {server.owner ? (
                  <Link
                    to={`/users/${server.owner.id}`}
                    className="text-indigo-600 hover:text-indigo-700"
                  >
                    {server.owner.name ?? server.owner.email ?? 'Unknown owner'}
                  </Link>
                ) : (
                  'System / Unknown'
                )}
              </dd>
            </div>
            <div>
              <dt className="text-xs font-medium uppercase tracking-wider text-slate-400">
                Plan
              </dt>
              <dd className="mt-1 text-sm text-slate-900">
                {server.plan_title
                  ? `${server.plan_title}${typeof server.plan_ram === 'number' ? ` (${server.plan_ram} GB)` : ''}`
                  : (server.plan ?? '-')}
              </dd>
            </div>
            <div>
              <dt className="text-xs font-medium uppercase tracking-wider text-slate-400">
                Created
              </dt>
              <dd className="mt-1 text-sm text-slate-900">
                {formatDate(server.created_at)}
              </dd>
            </div>
            <div>
              <dt className="text-xs font-medium uppercase tracking-wider text-slate-400">
                Events
              </dt>
              <dd className="mt-1 text-sm text-slate-900">
                {server.events_count}
              </dd>
            </div>
            <div>
              <dt className="text-xs font-medium uppercase tracking-wider text-slate-400">
                Pterodactyl ID
              </dt>
              <dd className="mt-1 text-sm text-slate-900">
                {server.ptero_id || '-'}
              </dd>
            </div>
            <div>
              <dt className="text-xs font-medium uppercase tracking-wider text-slate-400">
                Initialized
              </dt>
              <dd className="mt-1 text-sm text-slate-900">
                {server.initialised ? 'Yes' : 'No'}
              </dd>
            </div>
          </dl>
        </div>

        <div className="mt-8 rounded-xl border border-slate-200 bg-white p-6">
          <div className="flex flex-wrap items-start justify-between gap-3">
            <div>
              <h3 className="text-base font-semibold text-slate-900">
                Performance (Last 24 Hours)
              </h3>
              <p className="text-sm text-slate-500">
                {server.performance_last_24h.samples.length === 0
                  ? 'No telemetry samples in this window.'
                  : `${server.performance_last_24h.samples.length} sample${server.performance_last_24h.samples.length === 1 ? '' : 's'} captured from ${formatDateTime(server.performance_last_24h.from)} to ${formatDateTime(server.performance_last_24h.to)}.`}
              </p>
            </div>
            <div className="text-right text-xs text-slate-500">
              <p>Node: {server.performance_last_24h.latest.node_id ?? '-'}</p>
              <p>
                Latest at{' '}
                {formatDateTime(server.performance_last_24h.latest.recorded_at)}
              </p>
            </div>
          </div>

          <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
              <p className="text-xs font-medium uppercase tracking-wider text-slate-500">
                Latest Players
              </p>
              <p className="mt-1 text-lg font-semibold text-slate-900">
                {formatMetricValue(
                  server.performance_last_24h.latest.players_online,
                  'count',
                )}
              </p>
            </div>
            <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
              <p className="text-xs font-medium uppercase tracking-wider text-slate-500">
                Latest CPU
              </p>
              <p className="mt-1 text-lg font-semibold text-slate-900">
                {formatMetricValue(server.performance_last_24h.latest.cpu_pct, 'percent')}
              </p>
            </div>
            <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
              <p className="text-xs font-medium uppercase tracking-wider text-slate-500">
                Latest Write I/O
              </p>
              <p className="mt-1 text-lg font-semibold text-slate-900">
                {formatMetricValue(
                  server.performance_last_24h.latest.io_write_bytes_per_s,
                  'bytesPerSecond',
                )}
              </p>
            </div>
          </div>

          <div className="mt-5 grid grid-cols-1 gap-4 xl:grid-cols-3">
            <MetricLineChart
              title="Players Online"
              kind="count"
              color="#16a34a"
              from={server.performance_last_24h.from}
              to={server.performance_last_24h.to}
              samples={server.performance_last_24h.samples}
              valueSelector={(sample) => sample.players_online}
            />
            <MetricLineChart
              title="CPU Usage"
              kind="percent"
              color="#4f46e5"
              from={server.performance_last_24h.from}
              to={server.performance_last_24h.to}
              samples={server.performance_last_24h.samples}
              valueSelector={(sample) => sample.cpu_pct}
            />
            <MetricLineChart
              title="Write I/O"
              kind="bytesPerSecond"
              color="#d97706"
              from={server.performance_last_24h.from}
              to={server.performance_last_24h.to}
              samples={server.performance_last_24h.samples}
              valueSelector={(sample) => sample.io_write_bytes_per_s}
            />
          </div>
        </div>

        <div className="mt-8 rounded-xl border border-slate-200 bg-white">
          <div className="border-b border-slate-200 px-6 py-4">
            <h3 className="text-base font-semibold text-slate-900">
              Event Timeline
            </h3>
            <p className="text-sm text-slate-500">
              {server.events.length === 0
                ? 'No events recorded for this server yet.'
                : `${server.events.length} recent event${server.events.length === 1 ? '' : 's'}`}
            </p>
          </div>

          <div className="px-6 py-5">
            {server.events.length === 0 ? (
              <p className="text-sm text-slate-500">
                Timeline entries will appear here when server actions occur.
              </p>
            ) : (
              <ol>
                {server.events.map((event, index) => (
                  <EventTimelineItem
                    key={event.id}
                    event={event}
                    isLast={index === server.events.length - 1}
                  />
                ))}
              </ol>
            )}
          </div>
        </div>
      </div>
    </>
  )
}
