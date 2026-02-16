import { useEffect } from 'react'
import Navbar from '../components/home/Navbar'
import Banner from '../components/home/Banner'
import Footer from '../components/home/Footer'
import FAQ from '../components/home/FAQ'
import CallToAction from '../components/home/CallToAction'

import '../styles/bootstrap.min.css'
import '../styles/legacy.css'
import '../styles/legacy-responsive.css'

export default function FaqsPage() {
  useEffect(() => {
    document.body.classList.add('legacy-theme')
    window.scrollTo(0, 0)
    return () => {
      document.body.classList.remove('legacy-theme')
    }
  }, [])

  return (
    <>
      <Navbar />
      <Banner />

      {/* Header */}
      <div
        className="layout-text right-layout gray-layout padding-bottom60 padding-top60 section-header-bg"
        style={{ backgroundImage: 'url(/images/headers/dark.png)' }}
      >
        <div className="container">
          <div className="row planSelector">
            <div className="col-sm-12">
              <h2 className="text-center">Frequently Asked Questions</h2>
            </div>
          </div>
        </div>
      </div>

      {/* All FAQs */}
      <FAQ showTitle={false} homepageOnly={false} />

      {/* Call to action */}
      <CallToAction color="green" mob="skeleton">
        <div className="col-sm-6">
          <h3>Ready to get started?</h3>
          <p>Just select a plan and be online in 5 minutes!</p>
        </div>
        <div className="col-sm-6">
          <div className="buttons">
            <a href="/plans" className="btn btn-outline btn-large">
              Select a plan <i className="fas fa-long-arrow-alt-right"></i>
            </a>
          </div>
        </div>
      </CallToAction>

      <Footer />
    </>
  )
}

