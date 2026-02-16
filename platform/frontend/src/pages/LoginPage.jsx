import { useState } from 'react'
import { useLocation, useNavigate } from 'react-router-dom'
import Navbar from '../components/home/Navbar'
import { Input, Button, Checkbox, Alert } from '../components/ui'
import { useAuth } from '../context/useAuth'
import { getErrorMessage } from '../utils/getErrorMessage'

function LoginPage() {
  const navigate = useNavigate()
  const location = useLocation()
  const { login } = useAuth()
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [errorMessage, setErrorMessage] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')

  const targetPath = location.state?.from?.pathname ?? '/'

  const submitLogin = async (e) => {
    e.preventDefault()
    setErrorMessage('')
    setIsSubmitting(true)

    try {
      await login({ email, password })
      navigate(targetPath, { replace: true })
    } catch (error) {
      setErrorMessage(
        getErrorMessage(
          error,
          "Those details don't match anything we have on record. Please try again or reset your password.",
        ),
      )
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <>
      <Navbar />
      <div className="layout-text right-layout gray-layout padding-bottom60 padding-top60 full-height">
        <div className="container">
          <div className="row">
            {errorMessage && (
              <div className="col-xs-12 col-sm-8 col-md-6 col-sm-offset-2 col-md-offset-3">
                <Alert variant="danger">{errorMessage}</Alert>
              </div>
            )}
            <div className="col-xs-12 col-sm-8 col-md-6 col-sm-offset-2 col-md-offset-3">
              <form className="login" onSubmit={submitLogin}>
                <h3 className="text-center mb-20">Login to your Intera Account</h3>

                <Input
                  label="Email"
                  id="email"
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  required
                  autoFocus
                  autoComplete="email"
                />

                <Input
                  label="Password"
                  id="password"
                  type="password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  required
                  autoComplete="current-password"
                />

                <div style={{ marginTop: 20 }}>
                  <Checkbox id="remember_me" label="Remember me" name="remember" />
                </div>

                <div className="flex items-center justify-end mt-4">
                  <a
                    href="/forgot-password"
                    className="underline text-sm"
                    style={{ color: '#b7c0df' }}
                  >
                    Forgot your password?
                  </a>
                </div>

                <div className="flex flex-columns mt-4" style={{ gap: 8 }}>
                  <Button
                    variant="primary"
                    type="submit"
                    disabled={isSubmitting}
                    style={{ flex: 1, minWidth: 150 }}
                  >
                    {isSubmitting ? 'Please wait...' : 'Log in'}
                  </Button>
                  <Button
                    variant="secondary"
                    to="/register"
                    style={{ flex: 1, minWidth: 150 }}
                  >
                    Register
                  </Button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </>
  )
}

export default LoginPage
