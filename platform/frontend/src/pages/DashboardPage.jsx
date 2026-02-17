import { useEffect } from 'react'
import { Link } from 'react-router-dom'
import Navbar from '../components/home/Navbar'
import Footer from '../components/home/Footer'
import AccountNav from '../components/dashboard/AccountNav'
import ServerCard from '../components/dashboard/ServerCard'
import { useAuth } from '../context/useAuth'
import { useServers } from '../hooks/useServers'

import '../styles/bootstrap.min.css'
import '../styles/legacy.css'
import '../styles/legacy-responsive.css'

export default function DashboardPage() {
  const { user } = useAuth()
  const { servers, isLoading } = useServers()

  useEffect(() => {
    document.body.classList.add('legacy-theme')
    return () => {
      document.body.classList.remove('legacy-theme')
    }
  }, [])

  return (
    <>
      <Navbar />

      {/* Header banner */}
      <div
        className="layout-text right-layout gray-layout padding-bottom60 padding-top60 section-header-bg"
        style={{ backgroundImage: 'url(/images/headers/dark.png)' }}
      >
        <div className="container">
          <div className="row">
            <div className="col-sm-12">
              <h2 className="text-center">Welcome To Your Account</h2>
            </div>
          </div>
        </div>
      </div>

      {/* Dashboard content */}
      <div className="pricing-tables custom-pricing padding-top50 padding-bottom50">
        <div className="custom-width">
          <div className="row">
            {/* Sidebar */}
            <div className="col-sm-4 col-md-3 mb-40px">
              <AccountNav />
            </div>

            {/* Server list */}
            <div className="col-sm-8 col-md-9 features-six">
              {isLoading ? (
                <div className="col-sm-12">
                  <p className="text-center">Loading your servers...</p>
                </div>
              ) : servers.length > 0 ? (
                servers.map((server) => (
                  <ServerCard key={server.id} server={server} />
                ))
              ) : (
                <div className="col-sm-12">
                  <p className="text-center">
                    You do not have any servers, check out{' '}
                    <Link to="/plans" className="green">
                      our plans
                    </Link>
                    !
                  </p>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>

      <Footer />
    </>
  )
}
