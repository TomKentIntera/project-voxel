import { createContext } from 'react'
import type { AuthUser } from '../lib/authApi'

export interface AuthContextValue {
  user: AuthUser | null
  token: string | null
  isHydrating: boolean
  isAuthenticated: boolean
  login: (credentials: { email: string; password: string }) => Promise<AuthUser>
  logout: () => void
}

export const AuthContext = createContext<AuthContextValue | undefined>(undefined)

