import { useState } from 'react'
import { Link } from 'react-router-dom'

const COPY_BY_MODE = {
  login: {
    title: 'Log in to your account',
    submitLabel: 'Log in',
    switchLabel: "Don't have an account?",
    switchLinkLabel: 'Create one',
    switchLinkTo: '/register',
  },
  register: {
    title: 'Create your account',
    submitLabel: 'Create account',
    switchLabel: 'Already have an account?',
    switchLinkLabel: 'Log in',
    switchLinkTo: '/login',
  },
}

function AuthForm({ mode, onSubmit, errorMessage, isSubmitting }) {
  const [formValues, setFormValues] = useState({
    name: '',
    email: '',
    password: '',
    passwordConfirmation: '',
  })

  const copy = COPY_BY_MODE[mode]
  const isRegisterMode = mode === 'register'

  const onFieldChange = (event) => {
    const { name, value } = event.target
    setFormValues((previousValues) => ({
      ...previousValues,
      [name]: value,
    }))
  }

  const submitForm = async (event) => {
    event.preventDefault()
    await onSubmit(formValues)
  }

  return (
    <main className="auth-card">
      <h1>{copy.title}</h1>

      {errorMessage ? <p className="form-error">{errorMessage}</p> : null}

      <form className="auth-form" onSubmit={submitForm}>
        {isRegisterMode ? (
          <label className="form-field" htmlFor="name">
            Name
            <input
              id="name"
              name="name"
              type="text"
              value={formValues.name}
              onChange={onFieldChange}
              autoComplete="name"
              required
            />
          </label>
        ) : null}

        <label className="form-field" htmlFor="email">
          Email
          <input
            id="email"
            name="email"
            type="email"
            value={formValues.email}
            onChange={onFieldChange}
            autoComplete="email"
            required
          />
        </label>

        <label className="form-field" htmlFor="password">
          Password
          <input
            id="password"
            name="password"
            type="password"
            value={formValues.password}
            onChange={onFieldChange}
            autoComplete={isRegisterMode ? 'new-password' : 'current-password'}
            minLength={8}
            required
          />
        </label>

        {isRegisterMode ? (
          <label className="form-field" htmlFor="passwordConfirmation">
            Confirm password
            <input
              id="passwordConfirmation"
              name="passwordConfirmation"
              type="password"
              value={formValues.passwordConfirmation}
              onChange={onFieldChange}
              autoComplete="new-password"
              minLength={8}
              required
            />
          </label>
        ) : null}

        <button type="submit" className="primary-button" disabled={isSubmitting}>
          {isSubmitting ? 'Please wait...' : copy.submitLabel}
        </button>
      </form>

      <p className="auth-switch">
        {copy.switchLabel} <Link to={copy.switchLinkTo}>{copy.switchLinkLabel}</Link>
      </p>
    </main>
  )
}

export default AuthForm
