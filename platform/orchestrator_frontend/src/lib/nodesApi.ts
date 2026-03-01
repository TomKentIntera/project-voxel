import { apiRequest } from './apiClient'
import type { PaginationMeta } from './usersApi'

export interface NodeListItem {
  id: string
  name: string
  region: string
  ip_address: string
  last_active_at: string | null
  last_used_at: string | null
  created_at: string | null
  updated_at: string | null
}

export interface NodesParams {
  search?: string
  page?: number
  per_page?: number
}

interface NodesResponse {
  data: NodeListItem[]
  meta: PaginationMeta
}

export function fetchNodes(
  token: string,
  params: NodesParams = {},
): Promise<NodesResponse> {
  const query = new URLSearchParams()

  if (params.search) query.set('search', params.search)
  if (params.page) query.set('page', String(params.page))
  if (params.per_page) query.set('per_page', String(params.per_page))

  const qs = query.toString()
  return apiRequest<NodesResponse>(`/api/nodes${qs ? `?${qs}` : ''}`, { token })
}

export interface NodePerformanceSample {
  recorded_at: string
  cpu_pct: number
  iowait_pct: number
}

export interface NodePerformanceWindow {
  from: string
  to: string
  latest: {
    cpu_pct: number | null
    iowait_pct: number | null
    recorded_at: string | null
  }
  averages: {
    cpu_pct: number | null
    iowait_pct: number | null
  }
  samples: NodePerformanceSample[]
}

export interface NodeServerOwner {
  id: number
  username: string | null
  name: string | null
  email: string | null
}

export interface NodeLinkedServer {
  id: number
  uuid: string
  name: string
  status: string | null
  plan: string | null
  owner: NodeServerOwner | null
}

export interface NodeTelemetryServer {
  server_id: string
  players_online: number | null
  cpu_pct: number
  io_write_bytes_per_s: number
  last_reported_at: string | null
  server: NodeLinkedServer | null
}

export interface NodeAllocation {
  id: number | string | null
  ip: string | null
  alias: string | null
  port: number | null
  assigned: boolean
  ptero_server_id: number | null
  server: NodeLinkedServer | null
}

export interface NodeProfile extends NodeListItem {
  performance_last_24h: NodePerformanceWindow
  servers: NodeTelemetryServer[]
  servers_count: number
  allocations: NodeAllocation[]
  allocations_count: number
  assigned_allocations_count: number
  unassigned_allocations_count: number
  allocations_error: string | null
}

interface NodeProfileResponse {
  data: NodeProfile
}

export function fetchNode(
  token: string,
  id: string,
): Promise<NodeProfileResponse> {
  return apiRequest<NodeProfileResponse>(`/api/nodes/${encodeURIComponent(id)}`, {
    token,
  })
}

export interface CreateNodePayload {
  id?: string
  name: string
  region: string
  ip_address: string
  ptero_location_id: number
  fqdn: string
  scheme: 'http' | 'https'
  behind_proxy: boolean
  maintenance_mode?: boolean
  memory: number
  memory_overallocate?: number
  disk: number
  disk_overallocate?: number
  upload_size: number
  daemon_sftp: number
  daemon_listen: number
  allocation_ip?: string
  allocation_alias?: string | null
  allocation_ports: string[]
}

interface CreateNodeResponse {
  data: NodeListItem & { node_token: string }
}

export function createNode(
  token: string,
  payload: CreateNodePayload,
): Promise<CreateNodeResponse> {
  return apiRequest<CreateNodeResponse>('/api/nodes', {
    method: 'POST',
    token,
    body: payload,
  })
}

export interface NodeProvisioningCommand {
  node_id: string
  expires_at: string
  bootstrap_url: string
  command: string
}

interface NodeProvisioningCommandResponse {
  data: NodeProvisioningCommand
}

export function generateNodeProvisioningCommand(
  token: string,
  id: string,
  ttlMinutes?: number,
): Promise<NodeProvisioningCommandResponse> {
  const body = ttlMinutes ? { ttl_minutes: ttlMinutes } : undefined

  return apiRequest<NodeProvisioningCommandResponse>(
    `/api/nodes/${encodeURIComponent(id)}/provisioning-command`,
    {
      method: 'POST',
      token,
      body,
    },
  )
}

interface DeleteNodeResponse {
  message: string
}

export function deleteNode(
  token: string,
  id: string,
): Promise<DeleteNodeResponse> {
  return apiRequest<DeleteNodeResponse>(`/api/nodes/${encodeURIComponent(id)}`, {
    method: 'DELETE',
    token,
  })
}
