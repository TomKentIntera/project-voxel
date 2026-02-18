import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { AuthContext } from './auth-context'
import {
  fetchCurrentUser,
  loginUser,
  logoutUser,
  refreshAuthToken,
  registerUser,
} from '../utils/authApi'

const TOKEN_STORAGE_KEY = 'platform.auth.token'
const REFRESH_TOKEN_STORAGE_KEY = 'platform.auth.refresh_token'
const EXPIRES_AT_STORAGE_KEY = 'platform.auth.expires_at'

/** Refresh the token this many seconds before it actually expires. */
const REFRESH_BUFFER_SECONDS = 60

function getStoredToken() {
  return window.localStorage.getItem(TOKEN_STORAGE_KEY)
}

function getStoredRefreshToken() {
  return window.localStorage.getItem(REFRESH_TOKEN_STORAGE_KEY)
}

function getStoredExpiresAt() {
  const raw = window.localStorage.getItem(EXPIRES_AT_STORAGE_KEY)
  return raw ? Number(raw) : null
}

export function AuthProvider({ children }) {
  const [token, setToken] = useState(() => getStoredToken())
  const [refreshToken, setRefreshToken] = useState(() => getStoredRefreshToken())
  const [expiresAt, setExpiresAt] = useState(() => getStoredExpiresAt())
  const [user, setUser] = useState(null)
  const [isHydrating, setIsHydrating] = useState(() => getStoredToken() !== null)

  const refreshTimerRef = useRef(null)

  const clearSession = useCallback(() => {
    window.localStorage.removeItem(TOKEN_STORAGE_KEY)
    window.localStorage.removeItem(REFRESH_TOKEN_STORAGE_KEY)
    window.localStorage.removeItem(EXPIRES_AT_STORAGE_KEY)
    setToken(null)
    setRefreshToken(null)
    setExpiresAt(null)
    setUser(null)
    setIsHydrating(false)

    if (refreshTimerRef.current) {
      clearTimeout(refreshTimerRef.current)
      refreshTimerRef.current = null
    }
  }, [])

  const persistTokens = useCallback((nextToken, nextRefreshToken, nextExpiresAt) => {
    if (nextToken) {
      window.localStorage.setItem(TOKEN_STORAGE_KEY, nextToken)
    } else {
      window.localStorage.removeItem(TOKEN_STORAGE_KEY)
    }

    if (nextRefreshToken) {
      window.localStorage.setItem(REFRESH_TOKEN_STORAGE_KEY, nextRefreshToken)
    } else {
      window.localStorage.removeItem(REFRESH_TOKEN_STORAGE_KEY)
    }

    if (nextExpiresAt) {
      window.localStorage.setItem(EXPIRES_AT_STORAGE_KEY, String(nextExpiresAt))
    } else {
      window.localStorage.removeItem(EXPIRES_AT_STORAGE_KEY)
    }

    setToken(nextToken)
    setRefreshToken(nextRefreshToken)
    setExpiresAt(nextExpiresAt)
  }, [])

  const applyAuthResponse = useCallback(
    (response) => {
      persistTokens(response.token, response.refresh_token, response.expires_at)
      setUser(response.user)
    },
    [persistTokens],
  )

  // Schedule a silent refresh before the access token expires.
  const scheduleRefresh = useCallback(
    (currentExpiresAt, currentRefreshToken) => {
      if (refreshTimerRef.current) {
        clearTimeout(refreshTimerRef.current)
        refreshTimerRef.current = null
      }

      if (!currentExpiresAt || !currentRefreshToken) {
        return
      }

      const nowSeconds = Math.floor(Date.now() / 1000)
      const delaySeconds = currentExpiresAt - nowSeconds - REFRESH_BUFFER_SECONDS
      const delayMs = Math.max(0, delaySeconds) * 1000

      refreshTimerRef.current = setTimeout(async () => {
        try {
          const response = await refreshAuthToken(currentRefreshToken)
          persistTokens(response.token, response.refresh_token, response.expires_at)
          setUser(response.user)
          // Recursively schedule the next refresh.
          scheduleRefresh(response.expires_at, response.refresh_token)
        } catch {
          // Refresh failed — session is no longer valid.
          clearSession()
        }
      }, delayMs)
    },
    [clearSession, persistTokens],
  )

  // Hydrate user from stored token on mount.
  useEffect(() => {
    if (!token) {
      return
    }

    let isCancelled = false

    fetchCurrentUser(token)
      .then((response) => {
        if (!isCancelled) {
          setUser(response.user)
        }
      })
      .catch(() => {
        if (!isCancelled) {
          clearSession()
        }
      })
      .finally(() => {
        if (!isCancelled) {
          setIsHydrating(false)
        }
      })

    return () => {
      isCancelled = true
    }
  }, [clearSession, token])

  // Whenever the token/expiry change, (re-)schedule the silent refresh.
  useEffect(() => {
    scheduleRefresh(expiresAt, refreshToken)

    return () => {
      if (refreshTimerRef.current) {
        clearTimeout(refreshTimerRef.current)
        refreshTimerRef.current = null
      }
    }
  }, [expiresAt, refreshToken, scheduleRefresh])

  const login = useCallback(
    async (credentials) => {
      const response = await loginUser(credentials)
      applyAuthResponse(response)
      return response.user
    },
    [applyAuthResponse],
  )

  const register = useCallback(
    async (payload) => {
      const response = await registerUser(payload)
      applyAuthResponse(response)
      return response.user
    },
    [applyAuthResponse],
  )

  const logout = useCallback(() => {
    const currentRefreshToken = refreshToken
    clearSession()

    // Fire-and-forget: revoke the refresh token on the server.
    if (currentRefreshToken) {
      logoutUser(currentRefreshToken).catch(() => {
        // Best-effort; the local session is already cleared.
      })
    }
  }, [clearSession, refreshToken])

  const value = useMemo(
    () => ({
      user,
      token,
      isHydrating,
      isAuthenticated: Boolean(token && user),
      login,
      register,
      logout,
    }),
    [isHydrating, login, logout, register, token, user],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
