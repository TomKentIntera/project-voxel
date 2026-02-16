import { useCallback, useEffect, useMemo, useState } from 'react'
import { AuthContext } from './auth-context'
import { fetchCurrentUser, loginUser, registerUser } from '../utils/authApi'

const TOKEN_STORAGE_KEY = 'platform.auth.token'

function getStoredToken() {
  return window.localStorage.getItem(TOKEN_STORAGE_KEY)
}

export function AuthProvider({ children }) {
  const [token, setToken] = useState(() => getStoredToken())
  const [user, setUser] = useState(null)
  const [isHydrating, setIsHydrating] = useState(() => getStoredToken() !== null)

  const clearSession = useCallback(() => {
    window.localStorage.removeItem(TOKEN_STORAGE_KEY)
    setToken(null)
    setUser(null)
    setIsHydrating(false)
  }, [])

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

  const persistToken = useCallback((nextToken) => {
    if (nextToken) {
      window.localStorage.setItem(TOKEN_STORAGE_KEY, nextToken)
    } else {
      window.localStorage.removeItem(TOKEN_STORAGE_KEY)
    }

    setToken(nextToken)
  }, [])

  const login = useCallback(
    async (credentials) => {
      const response = await loginUser(credentials)
      persistToken(response.token)
      setUser(response.user)

      return response.user
    },
    [persistToken],
  )

  const register = useCallback(
    async (payload) => {
      const response = await registerUser(payload)
      persistToken(response.token)
      setUser(response.user)

      return response.user
    },
    [persistToken],
  )

  const logout = useCallback(() => {
    clearSession()
  }, [clearSession])

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
