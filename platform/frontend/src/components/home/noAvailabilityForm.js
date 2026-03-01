export const OPEN_NOTIFICATION_FORM_EVENT = 'open-notification-form'

export function buildNotificationOptions(plans = [], locations = {}) {
  const seenPlans = new Set()
  const planOptions = []

  for (const plan of plans) {
    const title = plan?.title
    if (!title || seenPlans.has(title)) continue
    seenPlans.add(title)
    planOptions.push(title)
  }

  const regionOptions = Object.entries(locations).map(([key, location]) => ({
    value: key,
    label: location?.title ?? key,
  }))

  return { planOptions, regionOptions }
}

export function getInitialNotificationValues(planOptions = [], regionOptions = []) {
  return {
    selectedPlan: planOptions[0] ?? '',
    region: regionOptions[0]?.value ?? '',
  }
}

export function selectRequestedPlan(requestedPlan, planOptions = [], fallbackPlan = '') {
  if (requestedPlan && planOptions.includes(requestedPlan)) {
    return requestedPlan
  }

  if (fallbackPlan && planOptions.includes(fallbackPlan)) {
    return fallbackPlan
  }

  return planOptions[0] ?? ''
}

