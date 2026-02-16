import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import Navbar from '../components/home/Navbar'
import { Input, Button, Alert } from '../components/ui'
import { useAuth } from '../context/useAuth'
import { getErrorMessage } from '../utils/getErrorMessage'

function RegisterPage() {
  const navigate = useNavigate()
  const { register } = useAuth()
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [errorMessage, setErrorMessage] = useState('')
  const [formValues, setFormValues] = useState({
    username: '',
    firstName: '',
    lastName: '',
    email: '',
    password: '',
    passwordConfirmation: '',
  })

  const onFieldChange = (e) => {
    const { name, value } = e.target
    setFormValues((prev) => ({ ...prev, [name]: value }))
  }

  const submitRegister = async (e) => {
    e.preventDefault()
    setErrorMessage('')
    setIsSubmitting(true)

    try {
      await register(formValues)
      navigate('/', { replace: true })
    } catch (error) {
      setErrorMessage(getErrorMessage(error, 'Unable to create your account right now.'))
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
              <form className="register" onSubmit={submitRegister}>
                <h3 className="text-center mb-20">Create an Intera Account</h3>

                <Input
                  label="Username"
                  id="username"
                  name="username"
                  value={formValues.username}
                  onChange={onFieldChange}
                  required
                  autoFocus
                  autoComplete="username"
                />

                <Input
                  label="First Name"
                  id="first_name"
                  name="firstName"
                  value={formValues.firstName}
                  onChange={onFieldChange}
                  required
                  autoComplete="given-name"
                />

                <Input
                  label="Last Name"
                  id="last_name"
                  name="lastName"
                  value={formValues.lastName}
                  onChange={onFieldChange}
                  required
                  autoComplete="family-name"
                />

                <Input
                  label="Email"
                  id="email"
                  type="email"
                  name="email"
                  value={formValues.email}
                  onChange={onFieldChange}
                  required
                  autoComplete="email"
                />

                <Input
                  label="Password"
                  id="password"
                  type="password"
                  name="password"
                  value={formValues.password}
                  onChange={onFieldChange}
                  required
                  autoComplete="new-password"
                  minLength={8}
                />

                <Input
                  label="Confirm Password"
                  id="password_confirmation"
                  type="password"
                  name="passwordConfirmation"
                  value={formValues.passwordConfirmation}
                  onChange={onFieldChange}
                  required
                  autoComplete="new-password"
                  minLength={8}
                />

                <div className="flex items-center justify-end mt-4" style={{ marginTop: 30 }}>
                  <Button variant="secondary" to="/login" size="sm">
                    Already registered?
                  </Button>
                </div>

                <div style={{ marginTop: 16 }}>
                  <Button
                    variant="primary"
                    type="submit"
                    disabled={isSubmitting}
                    style={{ minWidth: 150 }}
                  >
                    {isSubmitting ? 'Please wait...' : 'Register'}
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

export default RegisterPage
