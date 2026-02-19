import { apiRequest } from './apiClient'

export interface AuthUser {
  id: number
  username: string
  name: string
  first_name: string
  last_name: string
  email: string
  role: string
}

export interface AuthResponse {
  token: string
  refresh_token: string
  token_type: string
  expires_in: number
  expires_at: number
  user: AuthUser
}

export interface MeResponse {
  user: AuthUser
}

export function loginUser(payload: {
  email: string
  password: string
}): Promise<AuthResponse> {
  return apiRequest<AuthResponse>('/api/auth/login', {
    method: 'POST',
    body: {
      email: payload.email,
      password: payload.password,
    },
  })
}

export function refreshAuthToken(
  refreshToken: string,
): Promise<AuthResponse> {
  return apiRequest<AuthResponse>('/api/auth/refresh', {
    method: 'POST',
    body: { refresh_token: refreshToken },
  })
}

export function logoutUser(refreshToken: string): Promise<unknown> {
  return apiRequest('/api/auth/logout', {
    method: 'POST',
    body: { refresh_token: refreshToken },
  })
}

export function fetchCurrentUser(token: string): Promise<MeResponse> {
  return apiRequest<MeResponse>('/api/auth/me', {
    token,
  })
}

