import { useState } from 'react'
import { useLocation, useNavigate } from 'react-router-dom'
import AuthForm from '../components/AuthForm'
import { useAuth } from '../context/useAuth'
import { getErrorMessage } from '../utils/getErrorMessage'

function LoginPage() {
  const navigate = useNavigate()
  const location = useLocation()
  const { login } = useAuth()
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [errorMessage, setErrorMessage] = useState('')

  const targetPath = location.state?.from?.pathname ?? '/'

  const submitLogin = async (values) => {
    setErrorMessage('')
    setIsSubmitting(true)

    try {
      await login({
        email: values.email,
        password: values.password,
      })

      navigate(targetPath, { replace: true })
    } catch (error) {
      setErrorMessage(getErrorMessage(error, 'Unable to log in right now.'))
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <AuthForm
      mode="login"
      onSubmit={submitLogin}
      errorMessage={errorMessage}
      isSubmitting={isSubmitting}
    />
  )
}

export default LoginPage
