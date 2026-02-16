import { useEffect } from 'react'
import Navbar from '../components/home/Navbar'
import Banner from '../components/home/Banner'
import Footer from '../components/home/Footer'
import PlanRecommender from '../components/home/PlanRecommender'
import PricingPlans from '../components/home/PricingPlans'
import LargerPlans from '../components/home/LargerPlans'
import Features from '../components/home/Features'
import NoAvailability from '../components/home/NoAvailability'
import FeaturesMain from '../components/home/FeaturesMain'

import '../styles/bootstrap.min.css'
import '../styles/legacy.css'
import '../styles/legacy-responsive.css'

export default function PlansPage() {
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
      <PlanRecommender showHeaderBg />
      <PricingPlans showLargerPlans />
      <LargerPlans />
      <Features />
      <NoAvailability />
      <FeaturesMain />
      <Footer />
    </>
  )
}

