import { usePlans } from '../../hooks/usePlans'

export default function PlanCard({ plan, modId = null }) {
  const { locations, currencySymbol, getPlanPrice } = usePlans()

  const availability = plan.availability ?? {}
  const hasAvailableLocation = plan.locations.some(
    (locKey) => availability[locKey] !== false,
  )

  return (
    <div
      className="col-lg-4 col-md-4 col-sm-4 col-xs-12 mb-20"
      data-plan={plan.name}
    >
      <div className="table">
        <div className="table-img plan-img">
          <div className="plan-img-ar1">
            <img
              src={`/images/plan-images/${plan.icon}`}
              className="img-center img-responsive"
              alt={plan.title}
            />
          </div>
        </div>

        <div className="table-flags">
          {plan.locations.map((locKey) => {
            const loc = locations[locKey]
            if (!loc) return null
            const available = availability[locKey] !== false
            return (
              <span
                key={locKey}
                className={`flag has-tooltip${available ? '' : ' unavailable'}`}
                data-tooltip={
                  available ? loc.title : `${loc.title} (Unavailable)`
                }
              >
                <img
                  src={`/images/flags/${loc.flag}.svg`}
                  width="48"
                  alt={loc.title}
                />
              </span>
            )
          })}
        </div>

        {plan.ribbon && (
          <div className="table-ribbon">
            <span className="ribbon">{plan.ribbon}</span>
          </div>
        )}

        <div className="table-content">
          <h4>{plan.title}</h4>
          <p className="plan-ram">{plan.ram} GB</p>

          <p className="plan-price" style={{ fontWeight: 'bold' }}>
            {currencySymbol}
            {getPlanPrice(plan.name)}/month
          </p>

          <div className="buttons">
            {hasAvailableLocation ? (
              <a
                href={
                  modId
                    ? `/plan/configure/${plan.name}/mod/${modId}`
                    : `/plan/configure/${plan.name}`
                }
                className="btn btn-green btn-medium btn-90"
              >
                Configure
              </a>
            ) : (
              <a
                className="btn btn-blue btn-medium btn-90 notification_open"
                data-plan={plan.title}
              >
                Get Notified
              </a>
            )}
          </div>
        </div>
      </div>
    </div>
  )
}
