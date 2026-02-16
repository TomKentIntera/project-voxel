import { apiRequest } from './apiClient'

export function registerUser(payload) {
  return apiRequest('/api/auth/register', {
    method: 'POST',
    body: {
      username: payload.username,
      first_name: payload.firstName,
      last_name: payload.lastName,
      email: payload.email,
      password: payload.password,
      password_confirmation: payload.passwordConfirmation,
    },
  })
}

export function loginUser(payload) {
  return apiRequest('/api/auth/login', {
    method: 'POST',
    body: {
      email: payload.email,
      password: payload.password,
    },
  })
}

export function fetchCurrentUser(token) {
  return apiRequest('/api/auth/me', {
    token,
  })
}
