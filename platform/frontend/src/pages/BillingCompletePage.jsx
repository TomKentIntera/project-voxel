import { useEffect, useMemo, useState } from 'react'
import { Navigate, useNavigate, useParams, useSearchParams } from 'react-router-dom'
import Navbar from '../components/home/Navbar'
import Footer from '../components/home/Footer'
import { Alert } from '../components/ui'
import { useAuth } from '../context/useAuth'
import { confirmServerPurchase } from '../utils/serversApi'
import { getErrorMessage } from '../utils/getErrorMessage'

import '../styles/bootstrap.min.css'
import '../styles/legacy.css'
import '../styles/legacy-responsive.css'

export default function BillingCompletePage() {
  const { token } = useAuth()
  const { serverUuid = '' } = useParams()
  const [searchParams] = useSearchParams()
  const navigate = useNavigate()
  const paymentToken = useMemo(
    () => searchParams.get('session_id') ?? searchParams.get('token') ?? '',
    [searchParams],
  )

  const [errorMessage, setErrorMessage] = useState('')

  useEffect(() => {
    if (!serverUuid || !paymentToken || !token) {
      return
    }

    let isCancelled = false

    const confirmReturnToken = async () => {
      try {
        await confirmServerPurchase(serverUuid, paymentToken, token)
        if (!isCancelled) {
          navigate(`/initialize/${serverUuid}`, { replace: true })
        }
      } catch (error) {
        if (isCancelled) return
        setErrorMessage(
          getErrorMessage(
            error,
            'Unable to confirm your checkout return token right now.',
          ),
        )
      }
    }

    confirmReturnToken()

    return () => {
      isCancelled = true
    }
  }, [navigate, paymentToken, serverUuid, token])

  if (!serverUuid || !paymentToken) {
    return <Navigate to="/dashboard" replace />
  }

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
                <h3>Completing your order</h3>
                {errorMessage && <Alert variant="danger">{errorMessage}</Alert>}
                <p className="text-white mt-20">
                  <i className="fas fa-spinner fa-spin text-white"></i>{' '}
                  Verifying your payment token...
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
      <Footer />
    </>
  )
}

