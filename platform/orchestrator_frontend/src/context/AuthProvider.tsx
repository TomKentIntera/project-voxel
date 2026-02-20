import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import type { ReactNode } from 'react'
import { AuthContext } from './auth-context'
import type { AuthUser } from '../lib/authApi'
import {
  fetchCurrentUser,
  loginUser,
  logoutUser,
  refreshAuthToken,
} from '../lib/authApi'

const TOKEN_KEY = 'orchestrator.auth.token'
const REFRESH_TOKEN_KEY = 'orchestrator.auth.refresh_token'
const EXPIRES_AT_KEY = 'orchestrator.auth.expires_at'

/** Refresh the token this many seconds before it actually expires. */
const REFRESH_BUFFER_SECONDS = 60

function getStoredToken(): string | null {
  return window.localStorage.getItem(TOKEN_KEY)
}

function getStoredRefreshToken(): string | null {
  return window.localStorage.getItem(REFRESH_TOKEN_KEY)
}

function getStoredExpiresAt(): number | null {
  const raw = window.localStorage.getItem(EXPIRES_AT_KEY)
  return raw ? Number(raw) : null
}

export function AuthProvider({ children }: { children: ReactNode }) {
  const [token, setToken] = useState<string | null>(() => getStoredToken())
  const [refreshToken, setRefreshToken] = useState<string | null>(
    () => getStoredRefreshToken(),
  )
  const [expiresAt, setExpiresAt] = useState<number | null>(
    () => getStoredExpiresAt(),
  )
  const [user, setUser] = useState<AuthUser | null>(null)
  const [isHydrating, setIsHydrating] = useState(
    () => getStoredToken() !== null,
  )

  const refreshTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null)

  const clearSession = useCallback(() => {
    window.localStorage.removeItem(TOKEN_KEY)
    window.localStorage.removeItem(REFRESH_TOKEN_KEY)
    window.localStorage.removeItem(EXPIRES_AT_KEY)
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

  const persistTokens = useCallback(
    (
      nextToken: string | null,
      nextRefreshToken: string | null,
      nextExpiresAt: number | null,
    ) => {
      if (nextToken) {
        window.localStorage.setItem(TOKEN_KEY, nextToken)
      } else {
        window.localStorage.removeItem(TOKEN_KEY)
      }

      if (nextRefreshToken) {
        window.localStorage.setItem(REFRESH_TOKEN_KEY, nextRefreshToken)
      } else {
        window.localStorage.removeItem(REFRESH_TOKEN_KEY)
      }

      if (nextExpiresAt) {
        window.localStorage.setItem(EXPIRES_AT_KEY, String(nextExpiresAt))
      } else {
        window.localStorage.removeItem(EXPIRES_AT_KEY)
      }

      setToken(nextToken)
      setRefreshToken(nextRefreshToken)
      setExpiresAt(nextExpiresAt)
    },
    [],
  )

  const applyAuthResponse = useCallback(
    (response: {
      token: string
      refresh_token: string
      expires_at: number
      user: AuthUser
    }) => {
      persistTokens(response.token, response.refresh_token, response.expires_at)
      setUser(response.user)
    },
    [persistTokens],
  )

  // Schedule a silent refresh before the access token expires.
  const scheduleRefresh = useCallback(
    (
      currentExpiresAt: number | null,
      currentRefreshToken: string | null,
      currentToken: string | null,
    ) => {
      if (refreshTimerRef.current) {
        clearTimeout(refreshTimerRef.current)
        refreshTimerRef.current = null
      }

      if (!currentExpiresAt || !currentRefreshToken || !currentToken) {
        return
      }

      const nowSeconds = Math.floor(Date.now() / 1000)
      const delaySeconds =
        currentExpiresAt - nowSeconds - REFRESH_BUFFER_SECONDS
      const delayMs = Math.max(0, delaySeconds) * 1000

      refreshTimerRef.current = setTimeout(async () => {
        try {
          const response = await refreshAuthToken(currentRefreshToken, currentToken)
          persistTokens(
            response.token,
            response.refresh_token,
            response.expires_at,
          )
          setUser(response.user)
          scheduleRefresh(response.expires_at, response.refresh_token, response.token)
        } catch {
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

    const storedExpiresAt = getStoredExpiresAt()
    const nowSeconds = Math.floor(Date.now() / 1000)
    const accessTokenExpired =
      !storedExpiresAt || storedExpiresAt <= nowSeconds

    const hydrate = async () => {
      try {
        if (accessTokenExpired) {
          const storedRefreshToken = getStoredRefreshToken()

          if (!storedRefreshToken || !token) {
            if (!isCancelled) clearSession()
            return
          }

          try {
            const refreshResponse = await refreshAuthToken(storedRefreshToken, token)
            if (isCancelled) return

            persistTokens(
              refreshResponse.token,
              refreshResponse.refresh_token,
              refreshResponse.expires_at,
            )
            setUser(refreshResponse.user)
            scheduleRefresh(
              refreshResponse.expires_at,
              refreshResponse.refresh_token,
              refreshResponse.token,
            )
            return
          } catch {
            if (!isCancelled) clearSession()
            return
          }
        }

        // Access token is still valid — fetch the current user.
        const response = await fetchCurrentUser(token)
        if (!isCancelled) {
          setUser(response.user)
        }
      } catch {
        if (!isCancelled) {
          clearSession()
        }
      } finally {
        if (!isCancelled) {
          setIsHydrating(false)
        }
      }
    }

    hydrate()

    return () => {
      isCancelled = true
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  // Whenever the token/expiry change, (re-)schedule the silent refresh.
  useEffect(() => {
    scheduleRefresh(expiresAt, refreshToken, token)

    return () => {
      if (refreshTimerRef.current) {
        clearTimeout(refreshTimerRef.current)
        refreshTimerRef.current = null
      }
    }
  }, [expiresAt, refreshToken, scheduleRefresh, token])

  const login = useCallback(
    async (credentials: { email: string; password: string }) => {
      const response = await loginUser(credentials)

      if (response.user.role !== 'admin') {
        throw new Error('Access denied. Administrator privileges required.')
      }

      applyAuthResponse(response)
      return response.user
    },
    [applyAuthResponse],
  )

  const logout = useCallback(() => {
    const currentToken = token
    const currentRefreshToken = refreshToken
    clearSession()

    if (currentRefreshToken && currentToken) {
      logoutUser(currentRefreshToken, currentToken).catch(() => {
        // Best-effort; the local session is already cleared.
      })
    }
  }, [clearSession, refreshToken, token])

  const value = useMemo(
    () => ({
      user,
      token,
      isHydrating,
      isAuthenticated: Boolean(token && user),
      login,
      logout,
    }),
    [isHydrating, login, logout, token, user],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

