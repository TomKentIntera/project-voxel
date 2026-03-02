export const SERVER_TYPE_OPTIONS = [
  { value: 'vanilla', label: 'Vanilla' },
  { value: 'spigot', label: 'Spigot' },
  { value: 'paper', label: 'PaperMC' },
  { value: 'bukkit', label: 'Bukkit' },
  { value: 'forge', label: 'Forge/Forge Modpack' },
  { value: 'bedrock', label: 'Bedrock' },
]

export const MINECRAFT_VERSION_OPTIONS = [
  { value: '1.21.4', label: '1.21.4' },
  { value: '1.21.1', label: '1.21.1' },
  { value: '1.20.6', label: '1.20.6' },
  { value: '1.20.4', label: '1.20.4' },
  { value: '1.19.4', label: '1.19.4' },
  { value: '1.18.2', label: '1.18.2' },
]

export const SUBDOMAIN_PREFIX_MAX_LENGTH = 24

export function findPlanByName(plans = [], planName = '') {
  const normalizedPlanName = String(planName).trim().toLowerCase()
  if (!normalizedPlanName) return null

  return (
    plans.find(
      (plan) => String(plan?.name ?? '').trim().toLowerCase() === normalizedPlanName,
    ) ?? null
  )
}

export function buildLocationOptions(plan, locations) {
  if (!plan || !Array.isArray(plan.locations)) return []

  return plan.locations
    .filter((locKey) => plan.availability?.[locKey] !== false)
    .map((locKey) => {
      const location = locations?.[locKey]
      if (!location) return null
      return {
        value: locKey,
        label: location.title,
      }
    })
    .filter(Boolean)
}

export function sanitizeSubdomainPrefix(value = '') {
  return String(value)
    .toLowerCase()
    .replace(/[^a-z0-9]/g, '')
    .slice(0, SUBDOMAIN_PREFIX_MAX_LENGTH)
}

export function isValidSubdomainPrefix(value = '') {
  const normalized = String(value).trim().toLowerCase()
  return (
    normalized.length > 0 &&
    normalized.length <= SUBDOMAIN_PREFIX_MAX_LENGTH &&
    /^[a-z0-9]+$/.test(normalized)
  )
}

export function buildSubdomainDomainOptions(domains = []) {
  if (!Array.isArray(domains)) return []

  return domains
    .filter((domain) => typeof domain === 'string' && domain.trim() !== '')
    .map((domain) => domain.trim().toLowerCase())
    .filter((domain, index, allDomains) => allDomains.indexOf(domain) === index)
    .map((domain) => ({
      value: domain,
      label: domain,
    }))
}

