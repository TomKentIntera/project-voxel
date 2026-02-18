import { describe, expect, it } from 'vitest'
import { getErrorMessage } from './getErrorMessage'

describe('getErrorMessage', () => {
  const fallbackMessage = 'Something went wrong'

  it('returns the fallback when error is not an object', () => {
    expect(getErrorMessage(null, fallbackMessage)).toBe(fallbackMessage)
    expect(getErrorMessage('oops', fallbackMessage)).toBe(fallbackMessage)
  })

  it('returns payload message when present', () => {
    const error = {
      payload: {
        message: 'API payload message',
      },
    }

    expect(getErrorMessage(error, fallbackMessage)).toBe('API payload message')
  })

  it('returns first payload validation message when available', () => {
    const error = {
      payload: {
        errors: {
          email: ['The email field is required.'],
          password: ['The password field is required.'],
        },
      },
    }

    expect(getErrorMessage(error, fallbackMessage)).toBe('The email field is required.')
  })

  it('falls back to top-level error message', () => {
    const error = new Error('Top-level error message')

    expect(getErrorMessage(error, fallbackMessage)).toBe('Top-level error message')
  })
})
