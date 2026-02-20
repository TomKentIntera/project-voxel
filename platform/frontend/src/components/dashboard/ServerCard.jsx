import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useAuth } from '../../context/useAuth'
import { fetchServerPanelUrl } from '../../utils/serversApi'
import { getErrorMessage } from '../../utils/getErrorMessage'

/**
 * Displays a single server in the client dashboard.
 *
 * @param {object} props
 * @param {object} props.server - Server data from the API
 */
export default function ServerCard({ server }) {
  const { token } = useAuth()
  const [isOpeningPanel, setIsOpeningPanel] = useState(false)
  const [panelError, setPanelError] = useState('')

  const createdDate = server.created_at
    ? new Date(server.created_at).toISOString().split('T')[0]
    : '—'

  const planLabel = server.plan
    ? `${server.plan.title} Plan (${server.plan.ram} GB)`
    : 'Unknown Plan'

  const isSuspended = server.suspended
  const isPaid = server.stripe_tx_return

  async function handleOpenPanel() {
    if (!token || isOpeningPanel) {
      return
    }

    setPanelError('')
    setIsOpeningPanel(true)

    try {
      const panelUrl = await fetchServerPanelUrl(server.uuid, token)
      window.location.assign(panelUrl)
    } catch (error) {
      setPanelError(getErrorMessage(error, 'Unable to open the panel right now.'))
    } finally {
      setIsOpeningPanel(false)
    }
  }

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
                  <button
                    type="button"
                    className="btn btn-green w-100 mb-10"
                    onClick={handleOpenPanel}
                    disabled={isOpeningPanel}
                  >
                    <i className="fas fa-share text-white button"></i>{' '}
                    {isOpeningPanel ? 'Opening panel...' : 'Admin Panel'}
                  </button>
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

          {panelError ? <p className="text-red">{panelError}</p> : null}
        </div>
      </div>
    </div>
  )
}

