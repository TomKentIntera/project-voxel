import { useEffect } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import Navbar from '../components/home/Navbar'
import Footer from '../components/home/Footer'
import { setStoredReferralCode } from '../utils/referralStorage'

import '../styles/bootstrap.min.css'
import '../styles/legacy.css'
import '../styles/legacy-responsive.css'

export default function InviteReferralPage() {
  const navigate = useNavigate()
  const { referralCode = '' } = useParams()

  useEffect(() => {
    const trimmedCode = String(referralCode).trim()
    if (trimmedCode) {
      setStoredReferralCode(trimmedCode)
    }

    const timer = window.setTimeout(() => {
      navigate('/plans', { replace: true })
    }, 1000)

    return () => window.clearTimeout(timer)
  }, [navigate, referralCode])

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
                <h3>Referral code applied</h3>
                <p className="text-white mt-20">
                  Taking you to plans so your discount is applied at checkout...
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
