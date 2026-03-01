import { useMemo, useState } from 'react'
import { Link, Navigate, useParams } from 'react-router-dom'
import Navbar from '../components/home/Navbar'
import Footer from '../components/home/Footer'
import { Alert, Input, Select } from '../components/ui'
import { usePlans } from '../hooks/usePlans'
import { useAuth } from '../context/useAuth'
import { createServerPurchaseSession } from '../utils/serversApi'
import { getErrorMessage } from '../utils/getErrorMessage'
import {
  buildLocationOptions,
  findPlanByName,
  MINECRAFT_VERSION_OPTIONS,
  SERVER_TYPE_OPTIONS,
} from './planConfigurePage.helpers'

import '../styles/bootstrap.min.css'
import '../styles/legacy.css'
import '../styles/legacy-responsive.css'

export default function PlanConfigurePage() {
  const { planName } = useParams()
  const { token } = useAuth()
  const { plans, locations, isLoading, currencySymbol, getPlanPrice } = usePlans()
  const [serverName, setServerName] = useState('My Server')
  const [location, setLocation] = useState('')
  const [minecraftVersion, setMinecraftVersion] = useState('')
  const [serverType, setServerType] = useState('')
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [errorMessage, setErrorMessage] = useState('')

  const selectedPlan = useMemo(
    () => findPlanByName(plans, planName),
    [planName, plans],
  )
  const locationOptions = useMemo(
    () => buildLocationOptions(selectedPlan, locations),
    [locations, selectedPlan],
  )

  if (isLoading) {
    return <p className="auth-loading">Loading selected plan...</p>
  }

  if (!selectedPlan) {
    return <Navigate to="/plans" replace />
  }

  const canCreateServer = Boolean(
    token && location && minecraftVersion && serverType && !isSubmitting,
  )

  const submitPurchase = async (event) => {
    event.preventDefault()

    if (!canCreateServer) {
      return
    }

    setErrorMessage('')
    setIsSubmitting(true)

    try {
      const payload = await createServerPurchaseSession(
        {
          plan: selectedPlan.name,
          name: serverName,
          location,
          minecraft_version: minecraftVersion,
          type: serverType,
        },
        token,
      )

      window.location.assign(payload.checkout_url)
    } catch (error) {
      setErrorMessage(
        getErrorMessage(
          error,
          'Unable to start checkout right now. Please try again in a moment.',
        ),
      )
      setIsSubmitting(false)
    }
  }

  return (
    <>
      <Navbar />
      <div
        className="layout-text right-layout gray-layout padding-bottom60 padding-top60 section-header-bg full-height"
        style={{ backgroundImage: 'url(/images/headers/dark.png)' }}
      >
        <div className="container">
          <div className="row planSelector">
            <div className="col-sm-6">
              <h3 className="text-center">Selected Plan</h3>
              <div className="recommended-plan">
                <div className="table">
                  <div className="table-img plan-img">
                    <div className="plan-img-ar1">
                      <img
                        src={`/images/plan-images/${selectedPlan.icon}`}
                        className="img-center img-responsive"
                        alt={selectedPlan.title}
                      />
                    </div>
                  </div>
                  <div className="table-flags">
                    {selectedPlan.locations.map((locKey) => {
                      const loc = locations[locKey]
                      if (!loc) return null
                      const isAvailable = selectedPlan.availability?.[locKey] !== false
                      return (
                        <span
                          key={locKey}
                          className={`flag has-tooltip${isAvailable ? '' : ' unavailable'}`}
                          data-tooltip={
                            isAvailable ? loc.title : `${loc.title} (Unavailable)`
                          }
                        >
                          <img
                            src={`/images/flags/${loc.flag}.svg`}
                            width="48"
                            alt={loc.title}
                          />
                        </span>
                      )
                    })}
                  </div>
                  {selectedPlan.ribbon && (
                    <div className="table-ribbon">
                      <span className="ribbon">{selectedPlan.ribbon}</span>
                    </div>
                  )}
                  <div className="table-content">
                    <h4>{selectedPlan.title}</h4>
                    <p className="plan-ram">{selectedPlan.ram} GB</p>
                    <p className="plan-price">
                      {currencySymbol}
                      {getPlanPrice(selectedPlan.name)}/month
                    </p>
                  </div>
                </div>
              </div>
              <div className="max-w-330 text-white m-0-auto mt-20">
                <Link to="/plans" className="btn btn-green btn-medium w-100">
                  Change Plan
                </Link>
              </div>
            </div>

            <div className="col-sm-6">
              <div className="text-container">
                <h3>Configure Your Plan</h3>
                <div className="text-content">
                  <div className="text">
                    <p>
                      Your server will soon be ready - we just need some
                      information from you to get it started!
                    </p>
                  </div>
                </div>

                {errorMessage && <Alert variant="danger">{errorMessage}</Alert>}

                <form onSubmit={submitPurchase}>
                  <div className="mt-20">
                    <Input
                      label="What should we name this server?"
                      id="server-name"
                      labelClassName="ui-select-label"
                      value={serverName}
                      onChange={(event) => setServerName(event.target.value)}
                    />
                  </div>

                  <Select
                    label="Where would you like the server to be located?"
                    id="server-location"
                    placeholder="Select a location"
                    value={location}
                    onChange={(event) => setLocation(event.target.value)}
                    options={locationOptions}
                  />

                  <Select
                    label="What version will you be running?"
                    id="minecraft-version"
                    placeholder="Select a Minecraft version"
                    value={minecraftVersion}
                    onChange={(event) =>
                      setMinecraftVersion(event.target.value)
                    }
                    options={MINECRAFT_VERSION_OPTIONS}
                  />

                  <Select
                    label="What type of server will you be running?"
                    id="server-type"
                    placeholder="Select a server type"
                    value={serverType}
                    onChange={(event) => setServerType(event.target.value)}
                    options={SERVER_TYPE_OPTIONS}
                  />

                  <div className="mt-20">
                    <button
                      type="submit"
                      className="btn btn-green btn-medium w-100"
                      disabled={!canCreateServer}
                    >
                      {isSubmitting ? 'Redirecting to Checkout...' : 'Create Server'}
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
      <Footer />
    </>
  )
}

