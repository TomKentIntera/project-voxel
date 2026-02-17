import { Link } from 'react-router-dom'

/**
 * Displays a single server in the client dashboard.
 *
 * @param {object} props
 * @param {object} props.server - Server data from the API
 */
export default function ServerCard({ server }) {
  const createdDate = server.created_at
    ? new Date(server.created_at).toISOString().split('T')[0]
    : '—'

  const planLabel = server.plan
    ? `${server.plan.title} Plan (${server.plan.ram} GB)`
    : 'Unknown Plan'

  const isSuspended = server.suspended
  const isPaid = server.stripe_tx_return

  return (
    <div className="col-sm-4">
      <div className="text-container mb-80" data-server-id={server.id}>
        <div className="text">
          <div className="img-content">
            <i className={`fa fa-server${isSuspended ? ' text-red' : ''}`}></i>
          </div>
          <h4>{server.name}</h4>
          <p className="server-date">Created: {createdDate}</p>
          <p className="mb-20">{planLabel}</p>

          {isPaid ? (
            isSuspended ? (
              <>
                <p>
                  <button className="btn btn-light-blue w-100 mb-10" disabled>
                    Suspended
                  </button>
                </p>
                <p>
                  <button className="btn btn-green btn-red btn-disabled w-100" disabled>
                    <i className="fas fa-times text-white button"></i> Cancel Server
                  </button>
                </p>
              </>
            ) : (
              <>
                <p>
                  <a href="/panel" className="btn btn-green w-100 mb-10">
                    <i className="fas fa-share text-white button"></i> Admin Panel
                  </a>
                </p>
                <p>
                  <Link to="/dashboard/billing" className="btn btn-green btn-red w-100">
                    <i className="fas fa-times text-white button"></i> Cancel Server
                  </Link>
                </p>
              </>
            )
          ) : (
            <>
              <p>
                <button className="btn btn-transparent w-100 mb-10" disabled>
                  &nbsp;
                </button>
              </p>
              <p>
                <button className="btn btn-light-blue w-100 awaiting-payment" disabled>
                  Awaiting payment confirmation
                </button>
              </p>
            </>
          )}
        </div>
      </div>
    </div>
  )
}

