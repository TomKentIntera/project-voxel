import { describe, expect, it } from 'vitest'
import {
  buildNotificationOptions,
  getInitialNotificationValues,
  selectRequestedPlan,
} from './noAvailabilityForm'

describe('noAvailabilityForm helpers', () => {
  it('builds plan and region options from plans and locations', () => {
    const plans = [
      { title: 'Parrot' },
      { title: 'Fox' },
      { title: 'Parrot' },
      { title: '' },
      {},
    ]
    const locations = {
      de: { title: 'Germany (EU)' },
      us: { title: 'United States' },
    }

    const options = buildNotificationOptions(plans, locations)

    expect(options.planOptions).toEqual(['Parrot', 'Fox'])
    expect(options.regionOptions).toEqual([
      { value: 'de', label: 'Germany (EU)' },
      { value: 'us', label: 'United States' },
    ])
  })

  it('returns initial values from first plan and first location', () => {
    const initial = getInitialNotificationValues(
      ['Parrot', 'Fox'],
      [{ value: 'de', label: 'Germany (EU)' }],
    )

    expect(initial).toEqual({
      selectedPlan: 'Parrot',
      region: 'de',
    })
  })

  it('chooses requested plan when it exists, otherwise fallback then first option', () => {
    const planOptions = ['Parrot', 'Fox']

    expect(selectRequestedPlan('Fox', planOptions, 'Parrot')).toBe('Fox')
    expect(selectRequestedPlan('Unknown', planOptions, 'Parrot')).toBe('Parrot')
    expect(selectRequestedPlan('Unknown', planOptions, 'Missing')).toBe('Parrot')
  })
})

