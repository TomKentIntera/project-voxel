import { apiRequest } from './apiClient'

export interface UserItem {
  id: number
  username: string
  first_name: string
  last_name: string
  name: string
  email: string
  role: 'admin' | 'customer'
  servers_count: number
  created_at: string
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface UsersResponse {
  data: UserItem[]
  meta: PaginationMeta
}

export interface UsersParams {
  search?: string
  role?: string
  page?: number
  per_page?: number
}

export function fetchUsers(
  token: string,
  params: UsersParams = {},
): Promise<UsersResponse> {
  const query = new URLSearchParams()

  if (params.search) query.set('search', params.search)
  if (params.role) query.set('role', params.role)
  if (params.page) query.set('page', String(params.page))
  if (params.per_page) query.set('per_page', String(params.per_page))

  const qs = query.toString()
  return apiRequest<UsersResponse>(`/api/users${qs ? `?${qs}` : ''}`, { token })
}

