import { useEffect } from 'react'
import { useParams } from 'react-router-dom'
import Navbar from '../components/home/Navbar'
import Banner from '../components/home/Banner'
import Footer from '../components/home/Footer'
import PricingPlans from '../components/home/PricingPlans'
import Features from '../components/home/Features'
import NoAvailability from '../components/home/NoAvailability'
import FeaturesMain from '../components/home/FeaturesMain'
import FAQ from '../components/home/FAQ'
import CallToAction from '../components/home/CallToAction'
import { usePlans } from '../hooks/usePlans'

import '../styles/bootstrap.min.css'
import '../styles/legacy.css'
import '../styles/legacy-responsive.css'

export default function ModpackPage() {
  const { slug } = useParams()
  const { modpacks, plans, currencySymbol, getPlanPrice, isLoading } =
    usePlans()

  useEffect(() => {
    document.body.classList.add('legacy-theme')
    window.scrollTo(0, 0)
    return () => {
      document.body.classList.remove('legacy-theme')
    }
  }, [])

  const modpack = modpacks.find((m) => m.slug === slug)

  if (isLoading) return null

  if (!modpack) {
    return (
      <>
        <Navbar />
        <div className="custom-width padding-top50 padding-bottom50 text-center">
          <h2>Modpack not found</h2>
        </div>
        <Footer />
      </>
    )
  }

  // Resolve starting price from the starting plan
  const startingPrice = getPlanPrice(modpack.startingPlan)

  return (
    <>
      <Navbar />
      <Banner />

      {/* Hero Header */}
      <div className={`default-header ${modpack.headerClass}`}>
        <div className="custom-width">
          <div className="row">
            <div className="col-sm-7">
              <div className="header-text">
                <h2>{modpack.heading}</h2>
                <p>{modpack.description}</p>
                <h4>Starting at</h4>
                <h3>
                  {currencySymbol}
                  {startingPrice}/monthly
                </h3>
              </div>
              <div className="buttons">
                <a href="#plans" className="btn btn-green btn-large">
                  Get Started Now
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Plans filtered by modpack */}
      <PricingPlans modpack={modpack.slug} modId={modpack.modId} />

      <Features />
      <NoAvailability />
      <FeaturesMain />
      <FAQ />
      <CallToAction />
      <Footer />
    </>
  )
}

