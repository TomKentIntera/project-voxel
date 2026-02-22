import { apiRequest } from './apiClient'
import type { PaginationMeta } from './usersApi'

export interface ServerOwner {
  id: number
  username: string | null
  name: string | null
  email: string | null
}

export interface ServerListItem {
  id: number
  uuid: string
  name: string
  created_at: string | null
  suspended: boolean
  status: string | null
  plan: string | null
  plan_title: string | null
  plan_ram: number | null
  events_count: number
  owner: ServerOwner | null
}

interface ServersResponse {
  data: ServerListItem[]
  meta: PaginationMeta
}

export interface ServersParams {
  search?: string
  status?: string
  page?: number
  per_page?: number
}

export function fetchServers(
  token: string,
  params: ServersParams = {},
): Promise<ServersResponse> {
  const query = new URLSearchParams()

  if (params.search) query.set('search', params.search)
  if (params.status) query.set('status', params.status)
  if (params.page) query.set('page', String(params.page))
  if (params.per_page) query.set('per_page', String(params.per_page))

  const qs = query.toString()
  return apiRequest<ServersResponse>(`/api/servers${qs ? `?${qs}` : ''}`, { token })
}

export interface ServerEventItem {
  id: number
  type: string
  label: string
  actor: ServerOwner | null
  meta: Record<string, unknown>
  created_at: string | null
}

export interface ServerPerformanceSample {
  recorded_at: string
  players_online: number | null
  cpu_pct: number
  io_write_bytes_per_s: number
}

export interface ServerPerformanceWindow {
  from: string
  to: string
  latest: {
    players_online: number | null
    cpu_pct: number | null
    io_write_bytes_per_s: number | null
    node_id: string | null
    recorded_at: string | null
  }
  averages: {
    players_online: number | null
    cpu_pct: number | null
    io_write_bytes_per_s: number | null
  }
  samples: ServerPerformanceSample[]
}

export interface ServerProfile {
  id: number
  uuid: string
  name: string
  created_at: string | null
  suspended: boolean
  status: string | null
  initialised: boolean
  ptero_id: string | null
  plan: string | null
  plan_title: string | null
  plan_ram: number | null
  events_count: number
  owner: ServerOwner | null
  events: ServerEventItem[]
  performance_last_24h: ServerPerformanceWindow
}

interface ServerProfileResponse {
  data: ServerProfile
}

export function fetchServer(
  token: string,
  id: number,
): Promise<ServerProfileResponse> {
  return apiRequest<ServerProfileResponse>(`/api/servers/${id}`, { token })
}
