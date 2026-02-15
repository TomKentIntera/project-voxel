import { createContext, useContext, useEffect, useMemo, useState } from 'react'
import { fetchCurrentUser, loginUser, registerUser } from '../utils/authApi'

const TOKEN_STORAGE_KEY = 'platform.auth.token'
const AuthContext = createContext(undefined)

function getStoredToken() {
  return window.localStorage.getItem(TOKEN_STORAGE_KEY)
}

export function AuthProvider({ children }) {
  const [token, setToken] = useState(() => getStoredToken())
  const [user, setUser] = useState(null)
  const [isHydrating, setIsHydrating] = useState(() => getStoredToken() !== null)

  useEffect(() => {
    if (!token) {
      setIsHydrating(false)
      setUser(null)
      return
    }

    let isCancelled = false
    setIsHydrating(true)

    fetchCurrentUser(token)
      .then((response) => {
        if (!isCancelled) {
          setUser(response.user)
        }
      })
      .catch(() => {
        if (!isCancelled) {
          window.localStorage.removeItem(TOKEN_STORAGE_KEY)
          setToken(null)
          setUser(null)
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
  }, [token])

  const persistToken = (nextToken) => {
    if (nextToken) {
      window.localStorage.setItem(TOKEN_STORAGE_KEY, nextToken)
    } else {
      window.localStorage.removeItem(TOKEN_STORAGE_KEY)
    }

    setToken(nextToken)
  }

  const login = async (credentials) => {
    const response = await loginUser(credentials)
    persistToken(response.token)
    setUser(response.user)

    return response.user
  }

  const register = async (payload) => {
    const response = await registerUser(payload)
    persistToken(response.token)
    setUser(response.user)

    return response.user
  }

  const logout = () => {
    persistToken(null)
    setUser(null)
  }

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
    [isHydrating, token, user],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const context = useContext(AuthContext)

  if (!context) {
    throw new Error('useAuth must be used inside an AuthProvider.')
  }

  return context
}
