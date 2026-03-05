import { useEffect, useMemo, useState } from 'react'
import Navbar from '../components/home/Navbar'
import Footer from '../components/home/Footer'
import AccountNav from '../components/dashboard/AccountNav'
import { Alert } from '../components/ui'
import { useAuth } from '../context/useAuth'
import { fetchMyReferralSummary } from '../utils/referralApi'
import { getErrorMessage } from '../utils/getErrorMessage'

import '../styles/bootstrap.min.css'
import '../styles/legacy.css'
import '../styles/legacy-responsive.css'

export default function ReferralPage() {
  const { token } = useAuth()
  const [referral, setReferral] = useState(null)
  const [isLoading, setIsLoading] = useState(true)
  const [errorMessage, setErrorMessage] = useState('')

  useEffect(() => {
    if (!token) {
      return
    }

    let cancelled = false

    const load = async () => {
      setIsLoading(true)
      setErrorMessage('')

      try {
        const payload = await fetchMyReferralSummary(token)
        if (!cancelled) {
          setReferral(payload)
        }
      } catch (error) {
        if (!cancelled) {
          setErrorMessage(
            getErrorMessage(error, 'Unable to load your referral data right now.'),
          )
        }
      } finally {
        if (!cancelled) {
          setIsLoading(false)
        }
      }
    }

    load()

    return () => {
      cancelled = true
    }
  }, [token])

  const ledgerRows = useMemo(() => {
    if (!referral || !Array.isArray(referral.ledger)) {
      return []
    }

    return referral.ledger
  }, [referral])

  return (
    <>
      <Navbar />
      <div
        className="layout-text right-layout gray-layout padding-bottom60 padding-top60 section-header-bg"
        style={{ backgroundImage: 'url(/images/headers/dark.png)' }}
      >
        <div className="container">
          <div className="row">
            <div className="col-sm-12">
              <h2 className="text-center">Referral Program</h2>
            </div>
          </div>
        </div>
      </div>

      <div className="pricing-tables custom-pricing padding-top50 padding-bottom50">
        <div className="custom-width">
          <div className="row">
            <div className="col-sm-4 col-md-3 mb-40px">
              <AccountNav />
            </div>
            <div className="col-sm-8 col-md-9 features-six">
              {errorMessage && <Alert variant="danger">{errorMessage}</Alert>}
              {isLoading ? (
                <p className="text-center text-white">Loading referral details...</p>
              ) : referral ? (
                <>
                  <div className="text-container mb-20">
                    <h3>Your Referral Link</h3>
                    <p className="text-white">
                      Share this link and your friend gets {referral.discount_percent}% off.
                    </p>
                    <p className="text-white">
                      <strong>{referral.link}</strong>
                    </p>
                    <p className="text-white mb-0">
                      You earn {referral.referral_percent}% as account credit when they pay.
                    </p>
                  </div>

                  <div className="text-container mb-20">
                    <h3>Earnings</h3>
                    <p className="text-white">
                      Last {referral.period_days} days: <strong>${Number(referral.earned_last_period || 0).toFixed(2)}</strong>
                    </p>
                    <p className="text-white mb-0">
                      All time: <strong>${Number(referral.earned_all_time || 0).toFixed(2)}</strong>
                    </p>
                  </div>

                  <div className="text-container">
                    <h3>Referral Ledger (Last {referral.period_days} days)</h3>
                    {ledgerRows.length === 0 ? (
                      <p className="text-white mb-0">No referral earnings yet.</p>
                    ) : (
                      <div className="table-responsive">
                        <table className="table text-white">
                          <thead>
                            <tr>
                              <th>From</th>
                              <th>Email</th>
                              <th>Server</th>
                              <th>Amount</th>
                              <th>Earned At</th>
                            </tr>
                          </thead>
                          <tbody>
                            {ledgerRows.map((entry) => (
                              <tr key={entry.id}>
                                <td>{entry.from_user?.name || 'Unknown'}</td>
                                <td>{entry.from_user?.email || '-'}</td>
                                <td>{entry.server_uuid || '-'}</td>
                                <td>${Number(entry.amount || 0).toFixed(2)}</td>
                                <td>
                                  {entry.created_at
                                    ? new Date(entry.created_at).toLocaleString()
                                    : '-'}
                                </td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    )}
                  </div>
                </>
              ) : null}
            </div>
          </div>
        </div>
      </div>

      <Footer />
    </>
  )
}
