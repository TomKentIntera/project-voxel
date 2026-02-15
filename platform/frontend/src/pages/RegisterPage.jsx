import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import AuthForm from '../components/AuthForm'
import { useAuth } from '../context/AuthContext'
import { getErrorMessage } from '../utils/getErrorMessage'

function RegisterPage() {
  const navigate = useNavigate()
  const { register } = useAuth()
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [errorMessage, setErrorMessage] = useState('')

  const submitRegister = async (values) => {
    setErrorMessage('')
    setIsSubmitting(true)

    try {
      await register(values)
      navigate('/', { replace: true })
    } catch (error) {
      setErrorMessage(getErrorMessage(error, 'Unable to create your account right now.'))
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <AuthForm
      mode="register"
      onSubmit={submitRegister}
      errorMessage={errorMessage}
      isSubmitting={isSubmitting}
    />
  )
}

export default RegisterPage
