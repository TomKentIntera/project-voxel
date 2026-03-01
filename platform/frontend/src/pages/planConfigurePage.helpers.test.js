import { describe, expect, it } from 'vitest'
import {
  buildLocationOptions,
  findPlanByName,
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
})

