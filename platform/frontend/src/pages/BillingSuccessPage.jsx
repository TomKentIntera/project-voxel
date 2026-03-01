import { useEffect, useMemo, useState } from 'react'
import { Link, Navigate, useSearchParams } from 'react-router-dom'
import Navbar from '../components/home/Navbar'
import Footer from '../components/home/Footer'
import { Alert } from '../components/ui'
import { useAuth } from '../context/useAuth'
import { confirmServerPurchase, fetchServerProvisioningStatus } from '../utils/serversApi'
import { getErrorMessage } from '../utils/getErrorMessage'

import '../styles/bootstrap.min.css'
import '../styles/legacy.css'
import '../styles/legacy-responsive.css'

const POLL_INTERVAL_MS = 2000

export default function BillingSuccessPage() {
  const { token } = useAuth()
  const [searchParams] = useSearchParams()
  const serverUuid = useMemo(() => searchParams.get('server_uuid') ?? '', [searchParams])
  const sessionId = useMemo(() => searchParams.get('session_id') ?? '', [searchParams])

  const [statusPayload, setStatusPayload] = useState(null)
  const [isConfirmingReturn, setIsConfirmingReturn] = useState(true)
  const [isPolling, setIsPolling] = useState(true)
  const [errorMessage, setErrorMessage] = useState('')

  useEffect(() => {
    if (!serverUuid || !sessionId || !token) {
      setIsConfirmingReturn(false)
      return
    }

    let isCancelled = false

    const confirmReturnToken = async () => {
      try {
        await confirmServerPurchase(serverUuid, sessionId, token)
      } catch (error) {
        if (isCancelled) return
        setErrorMessage(
          getErrorMessage(
            error,
            'Unable to confirm your checkout return token right now.',
          ),
        )
      } finally {
        if (!isCancelled) {
          setIsConfirmingReturn(false)
        }
      }
    }

    confirmReturnToken()

    return () => {
      isCancelled = true
    }
  }, [serverUuid, sessionId, token])

  useEffect(() => {
    if (!serverUuid || !token || isConfirmingReturn) {
      return
    }

    let isCancelled = false
    let intervalId = null

    const fetchStatus = async () => {
      try {
        const payload = await fetchServerProvisioningStatus(serverUuid, token)
        if (isCancelled) return

        setStatusPayload(payload)
        if (payload.provisioned) {
          setIsPolling(false)
          if (intervalId) {
            window.clearInterval(intervalId)
            intervalId = null
          }
        }
      } catch (error) {
        if (isCancelled) return
        setErrorMessage(
          getErrorMessage(
            error,
            'Unable to refresh provisioning status right now. Please try again shortly.',
          ),
        )
      }
    }

    fetchStatus()
    intervalId = window.setInterval(fetchStatus, POLL_INTERVAL_MS)

    return () => {
      isCancelled = true
      if (intervalId) {
        window.clearInterval(intervalId)
      }
    }
  }, [isConfirmingReturn, serverUuid, token])

  if (!serverUuid || !sessionId) {
    return <Navigate to="/dashboard" replace />
  }

  const paymentConfirmed = Boolean(statusPayload?.payment_confirmed)
  const initialised = Boolean(statusPayload?.initialised)
  const provisioned = Boolean(statusPayload?.provisioned)
  const panelUrl = typeof statusPayload?.panel_url === 'string' ? statusPayload.panel_url : ''

  return (
    <>
      <Navbar />
      <div
        className="layout-text right-layout gray-layout padding-bottom60 padding-top60 section-header-bg full-height"
        style={{ backgroundImage: 'url(/images/headers/dark.png)' }}
      >
        <div className="container">
          <div className="row">
            <div className="col-sm-10 col-sm-offset-1">
              <div className="text-container text-center">
                <h3>{provisioned ? "You're ready to game!" : 'Welcome to your server'}</h3>

                {errorMessage && <Alert variant="danger">{errorMessage}</Alert>}

                {!provisioned ? (
                  <div className="initialise-container mt-20">
                    <p className="text-white">
                      <i className="fas fa-spinner fa-spin text-white"></i>{' '}
                      Awaiting payment completion &amp; server initialisation...
                    </p>
                    {isConfirmingReturn && (
                      <p className="text-white">Confirming your checkout session...</p>
                    )}
                    <p className="text-white mt-10">
                      Payment confirmed: <strong>{paymentConfirmed ? 'Yes' : 'No'}</strong>
                    </p>
                    <p className="text-white">
                      Server provisioned: <strong>{initialised ? 'Yes' : 'No'}</strong>
                    </p>
                    {isPolling && <p className="text-white">Checking status every 2 seconds...</p>}
                  </div>
                ) : (
                  <div className="init_complete mt-20">
                    <p className="mb-20 text-white">
                      Your payment has completed and your server has finished installing.
                    </p>
                    {panelUrl ? (
                      <p>
                        <a href={panelUrl} className="btn btn-green btn-large">
                          Login to the control panel <i className="fas fa-lock-open text-white"></i>
                        </a>
                      </p>
                    ) : (
                      <p>
                        <Link to="/dashboard" className="btn btn-green btn-large">
                          Go to Dashboard
                        </Link>
                      </p>
                    )}
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
      <Footer />
    </>
  )
}

