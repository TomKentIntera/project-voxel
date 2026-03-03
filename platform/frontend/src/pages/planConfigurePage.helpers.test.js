import { describe, expect, it } from 'vitest'
import {
  buildLocationOptions,
  buildSubdomainDomainOptions,
  findPlanByName,
  isValidSubdomainPrefix,
  sanitizeSubdomainPrefix,
} from './planConfigurePage.helpers'

describe('planConfigurePage helpers', () => {
  it('finds a plan by name case-insensitively', () => {
    const plans = [{ name: 'parrot' }, { name: 'Rabbit' }]

    expect(findPlanByName(plans, 'RABBIT')).toEqual({ name: 'Rabbit' })
    expect(findPlanByName(plans, 'missing')).toBeNull()
  })

  it('builds location options from plan location keys and filters unavailable', () => {
    const plan = {
      locations: ['de', 'fi', 'us'],
      availability: {
        de: true,
        fi: false,
      },
    }

    const locations = {
      de: { title: 'Germany (EU)' },
      fi: { title: 'Finland (EU)' },
      us: { title: 'United States' },
    }

    expect(buildLocationOptions(plan, locations)).toEqual([
      { value: 'de', label: 'Germany (EU)' },
      { value: 'us', label: 'United States' },
    ])
  })

  it('sanitizes subdomain prefix to lowercase alphanumeric and enforces length', () => {
    expect(sanitizeSubdomainPrefix('  My_Server-01! ')).toBe('myserver01')
    expect(sanitizeSubdomainPrefix('A'.repeat(40))).toBe('a'.repeat(24))
  })

  it('validates subdomain prefix format and max length', () => {
    expect(isValidSubdomainPrefix('myserver123')).toBe(true)
    expect(isValidSubdomainPrefix('')).toBe(false)
    expect(isValidSubdomainPrefix('invalid-name')).toBe(false)
    expect(isValidSubdomainPrefix('a'.repeat(25))).toBe(false)
  })

  it('builds normalized domain options and removes duplicates', () => {
    expect(
      buildSubdomainDomainOptions(['Intera.GG', 'intera.localhost', 'intera.gg', '', null]),
    ).toEqual([
      { value: 'intera.gg', label: 'intera.gg' },
      { value: 'intera.localhost', label: 'intera.localhost' },
    ])
  })
})

