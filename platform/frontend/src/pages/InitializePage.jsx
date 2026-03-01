import { useEffect, useState } from 'react'
import { Link, Navigate, useParams } from 'react-router-dom'
import Navbar from '../components/home/Navbar'
import Footer from '../components/home/Footer'
import { Alert } from '../components/ui'
import { useAuth } from '../context/useAuth'
import { fetchServerProvisioningStatus } from '../utils/serversApi'
import { getErrorMessage } from '../utils/getErrorMessage'

import '../styles/bootstrap.min.css'
import '../styles/legacy.css'
import '../styles/legacy-responsive.css'

const POLL_INTERVAL_MS = 2000

const STATUS_LABELS = {
  pending: "We're waiting for payment to complete.",
  provisioning: 'Our golems are hard at work provisioning your server.',
  provisioned: 'Your server is ready!',
}

export default function InitializePage() {
  const { token } = useAuth()
  const { serverUuid = '' } = useParams()
  const [statusPayload, setStatusPayload] = useState(null)
  const [errorMessage, setErrorMessage] = useState('')

  useEffect(() => {
    if (!serverUuid || !token) {
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
  }, [serverUuid, token])

  if (!serverUuid) {
    return <Navigate to="/dashboard" replace />
  }

  const stage = statusPayload?.stage ?? 'pending'
  const stageText = STATUS_LABELS[stage] ?? STATUS_LABELS.pending
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
            <div className="col-sm-8 col-sm-offset-2 col-md-6 col-md-offset-3">
              <div className="text-container text-center initialize-card">
                <h3>{provisioned ? "You're ready to game!" : 'Welcome to your server'}</h3>
                {errorMessage && <Alert variant="danger">{errorMessage}</Alert>}

                {!provisioned ? (
                  <div className="initialise-container mt-20">
                    <p className="text-white">
                      <i className="fas fa-spinner fa-spin text-white"></i>{' '}
                      {stageText}
                    </p>
                  </div>
                ) : (
                  <div className="init_complete mt-20">
                    <p className="mb-20 text-white">{STATUS_LABELS.provisioned}</p>
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

