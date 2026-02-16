import { useQuery } from '@tanstack/react-query'
import { apiRequest } from '../utils/apiClient'
import { useCurrency } from '../stores/useCurrencyStore.jsx'

async function fetchPlans() {
  const data = await apiRequest('/api/plans')

  // Transform locations array into a keyed object for easy lookup
  const locationsMap = {}
  for (const loc of data.locations) {
    locationsMap[loc.key] = { title: loc.title, flag: loc.flag }
  }

  return {
    plans: data.plans,
    locations: locationsMap,
    planRecommender: data.planRecommender,
    modpacks: data.modpacks ?? [],
  }
}

/**
 * Get the display price for a plan in the given currency.
 */
function getPlanPrice(plans, planName, currency) {
  const plan = plans.find((p) => p.name === planName)
  if (!plan) return '0.00'
  const price = plan.displayPrice[currency]
  if (price == null) return '0.00'
  return price % 1 === 0 ? price.toFixed(0) : price.toFixed(2)
}

/**
 * Get the starting price (cheapest default plan).
 */
function getStartingPrice(plans, currency) {
  const defaultPlans = plans.filter((p) => p.showDefaultPlans)
  if (defaultPlans.length === 0) return '0.00'
  return getPlanPrice(plans, defaultPlans[0].name, currency)
}

/**
 * React Query hook for fetching plans data from the API.
 * Prices are automatically shown in the user's selected currency.
 */
export function usePlans() {
  const { currency, currencySymbol } = useCurrency()

  const query = useQuery({
    queryKey: ['plans'],
    queryFn: fetchPlans,
  })

  const plans = query.data?.plans ?? []

  return {
    ...query,
    plans,
    locations: query.data?.locations ?? {},
    planRecommender: query.data?.planRecommender ?? { players: [], versions: [], types: [] },
    modpacks: query.data?.modpacks ?? [],
    currency,
    currencySymbol,
    getPlanPrice: (planName) => getPlanPrice(plans, planName, currency),
    getStartingPrice: () => getStartingPrice(plans, currency),
  }
}
