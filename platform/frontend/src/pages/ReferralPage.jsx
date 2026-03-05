import { useEffect, useMemo, useState } from 'react'
import Navbar from '../components/home/Navbar'
import Footer from '../components/home/Footer'
import AccountNav from '../components/dashboard/AccountNav'
import { Alert, Card } from '../components/ui'
import { useAuth } from '../context/useAuth'
import { fetchMyReferralSummary } from '../utils/referralApi'
import { getErrorMessage } from '../utils/getErrorMessage'

import '../styles/bootstrap.min.css'
import '../styles/legacy.css'
import '../styles/legacy-responsive.css'
import '../styles/referral.css'

export default function ReferralPage() {
  const { token } = useAuth()
  const [referral, setReferral] = useState(null)
  const [isLoading, setIsLoading] = useState(true)
  const [errorMessage, setErrorMessage] = useState('')
  const [copyState, setCopyState] = useState('idle')

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

  const copyReferralLink = async () => {
    if (!referral?.link) {
      return
    }

    try {
      await navigator.clipboard.writeText(referral.link)
      setCopyState('copied')
      window.setTimeout(() => setCopyState('idle'), 1800)
    } catch {
      setCopyState('error')
      window.setTimeout(() => setCopyState('idle'), 1800)
    }
  }

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
                  <Card className="referral-intro-card">
                    <h3>How the referral program works</h3>
                    <p>
                      Share your referral link with friends. They receive a discount on their
                      first payment, and once they pay you receive referral credit directly on
                      your account for future invoices.
                    </p>
                  </Card>

                  <div className="referral-hero-card">
                    <h3>Refer Friends &amp; Earn Credit</h3>
                    <p>
                      Share your referral link. Friends get {referral.discount_percent}% off and
                      you earn {referral.referral_percent}% as account credit when they pay.
                    </p>
                    <div className="referral-link-row">
                      <input
                        className="referral-link-input"
                        type="text"
                        readOnly
                        value={referral.link}
                        aria-label="Referral link"
                      />
                      <button
                        type="button"
                        className="referral-copy-button"
                        onClick={copyReferralLink}
                      >
                        {copyState === 'copied'
                          ? 'Copied'
                          : copyState === 'error'
                            ? 'Copy failed'
                            : 'Copy Link'}
                      </button>
                    </div>
                  </div>

                  <div className="referral-stats-grid">
                    <Card className="referral-stat-card">
                      <div className="referral-stat-value">
                        {Number(referral.referred_users_count || 0)}
                      </div>
                      <p className="referral-stat-label">Referred Users</p>
                    </Card>
                    <Card className="referral-stat-card">
                      <div className="referral-stat-value">
                        {Number(referral.referred_user_servers_count || 0)}
                      </div>
                      <p className="referral-stat-label">Referred User Servers</p>
                    </Card>
                    <Card className="referral-stat-card">
                      <div className="referral-stat-value">
                        ${Number(referral.revenue_last_30_days || 0).toFixed(2)}
                      </div>
                      <p className="referral-stat-label">Referral Revenue (30 Days)</p>
                    </Card>
                  </div>

                  <Card className="referral-table-card">
                    <h3>Referral Transactions</h3>
                    {ledgerRows.length === 0 ? (
                      <p className="referral-empty-state">No referral earnings yet.</p>
                    ) : (
                      <div className="table-responsive">
                        <table className="table referral-table">
                          <thead>
                            <tr>
                              <th>Date</th>
                              <th>Plan Purchased</th>
                              <th>Referral Revenue</th>
                            </tr>
                          </thead>
                          <tbody>
                            {ledgerRows.map((entry) => (
                              <tr key={entry.id}>
                                <td>
                                  {entry.created_at
                                    ? new Date(entry.created_at).toLocaleDateString()
                                    : '-'}
                                </td>
                                <td>{entry.plan_purchased || '-'}</td>
                                <td>${Number(entry.amount || 0).toFixed(2)}</td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    )}
                  </Card>
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
