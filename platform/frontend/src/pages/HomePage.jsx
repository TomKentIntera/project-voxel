import { useEffect } from 'react'
import Navbar from '../components/home/Navbar'
import Banner from '../components/home/Banner'
import Footer from '../components/home/Footer'
import Hero from '../components/home/Hero'
import PricingPlans from '../components/home/PricingPlans'
import NoAvailability from '../components/home/NoAvailability'
import Features from '../components/home/Features'
import LargerPlans from '../components/home/LargerPlans'
import PlanRecommender from '../components/home/PlanRecommender'
import FeaturesMain from '../components/home/FeaturesMain'
import FAQ from '../components/home/FAQ'
import PanelSection from '../components/home/PanelSection'
import CallToAction from '../components/home/CallToAction'

import '../styles/bootstrap.min.css'
import '../styles/legacy.css'
import '../styles/legacy-responsive.css'

export default function HomePage() {
  useEffect(() => {
    document.body.classList.add('legacy-theme')
    return () => {
      document.body.classList.remove('legacy-theme')
    }
  }, [])

  return (
    <>
      <Navbar />
      <Banner />
      <Hero />
      <PricingPlans />
      <NoAvailability />
      <Features />
      <LargerPlans />
      <PlanRecommender />
      <FeaturesMain />
      <FAQ />
      <PanelSection />
      <CallToAction color="green" mob="skeleton">
        <div className="col-sm-6">
          <h3>Ready to get started?</h3>
          <p>Just select a plan and be online in 5 minutes!</p>
        </div>
        <div className="col-sm-6">
          <div className="buttons">
            <a href="/plans" className="btn btn-outline btn-large">
              Select a plan{' '}
              <i className="fas fa-long-arrow-alt-right"></i>
            </a>
          </div>
        </div>
      </CallToAction>
      <Footer />
    </>
  )
}

