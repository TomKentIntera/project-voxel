import { usePlans } from '../../hooks/usePlans'
import PlanCard from './PlanCard'

/**
 * PricingPlans — renders a grid of plan cards.
 *
 * @param {object}  props
 * @param {boolean} [props.showLargerPlans]  Show all non-modpack plans (including ravager/guardian).
 * @param {string}  [props.modpack]          When set, only show plans that belong to this modpack slug.
 * @param {number}  [props.modId]            Modpack order ID appended to configure URL.
 */
export default function PricingPlans({
  showLargerPlans = false,
  modpack = null,
  modId = null,
}) {
  const { plans, isLoading } = usePlans()

  let filteredPlans
  if (modpack) {
    filteredPlans = plans.filter((plan) =>
      (plan.modpacks ?? []).includes(modpack),
    )
  } else if (showLargerPlans) {
    filteredPlans = plans.filter((plan) => (plan.modpacks ?? []).length === 0)
  } else {
    filteredPlans = plans.filter((plan) => plan.showDefaultPlans)
  }

  return (
    <div
      className="pricing-tables custom-pricing padding-top50 padding-bottom50"
      id="plans"
    >
      <div className="custom-width">
        <div className="row">
          <div className="main-title text-center">
            <h2>Our Minecraft Plans</h2>
            <p>
              We offer a range of plans to suit your needs. All of our plans come
              with the same features, so no need to pay extra to get access to
              basic features like MySQL databases, SFTP access or the plugin
              auto-installer.
            </p>
          </div>
          {isLoading ? (
            <div className="text-center">
              <p>Loading plans...</p>
            </div>
          ) : (
            filteredPlans.map((plan) => (
              <PlanCard key={plan.name} plan={plan} modId={modId} />
            ))
          )}
        </div>
      </div>
    </div>
  )
}
