import { useCallback, useEffect, useMemo, useState } from 'react'
import { usePlans } from '../../hooks/usePlans'
import {
  buildNotificationOptions,
  getInitialNotificationValues,
  OPEN_NOTIFICATION_FORM_EVENT,
  selectRequestedPlan,
} from './noAvailabilityForm'

export default function NoAvailability() {
  const { plans, locations } = usePlans()
  const { planOptions, regionOptions } = useMemo(
    () => buildNotificationOptions(plans, locations),
    [plans, locations],
  )
  const initialValues = useMemo(
    () => getInitialNotificationValues(planOptions, regionOptions),
    [planOptions, regionOptions],
  )

  const [isFormOpen, setIsFormOpen] = useState(false)
  const [selectedPlan, setSelectedPlan] = useState('')
  const [email, setEmail] = useState('')
  const [region, setRegion] = useState('')

  useEffect(() => {
    if (!selectedPlan && initialValues.selectedPlan) {
      setSelectedPlan(initialValues.selectedPlan)
    }

    if (!region && initialValues.region) {
      setRegion(initialValues.region)
    }
  }, [initialValues, region, selectedPlan])

  const openNotificationForm = useCallback(
    (requestedPlan = '') => {
      setIsFormOpen(true)
      setSelectedPlan((currentPlan) =>
        selectRequestedPlan(
          requestedPlan,
          planOptions,
          currentPlan || initialValues.selectedPlan,
        ),
      )

      const section = document.querySelector('.notification_scroll')
      section?.scrollIntoView({ behavior: 'smooth', block: 'start' })
    },
    [initialValues.selectedPlan, planOptions],
  )

  useEffect(() => {
    const handleOpenNotification = (event) => {
      openNotificationForm(event?.detail?.plan ?? '')
    }

    window.addEventListener(OPEN_NOTIFICATION_FORM_EVENT, handleOpenNotification)
    return () => {
      window.removeEventListener(
        OPEN_NOTIFICATION_FORM_EVENT,
        handleOpenNotification,
      )
    }
  }, [openNotificationForm])

  return (
    <div
      id="availability-notification"
      className="call-to-action cta-blue cta-bg notification_scroll"
      style={{
        backgroundImage: 'url(/images/plan-images/skeleton.png)',
        backgroundSize: '140px',
        backgroundPosition: '140px 30px',
      }}
    >
      <div className="custom-width">
        <div className="row">
          <div className="col-sm-6">
            <h3>Out of stock in your region?</h3>
            <p>
              We&apos;re always getting new nodes in our regions. If we&apos;ve
              not got availability right now, we can let you know as soon as a
              node becomes available.
            </p>
          </div>
          <div className="col-sm-6">
            <div className="buttons">
              {!isFormOpen && (
                <button
                  type="button"
                  className="btn btn-outline btn-large notification_open main"
                  style={{ backgroundColor: 'transparent' }}
                  onClick={() => openNotificationForm()}
                >
                  Get notified of availability!{' '}
                  <i className="fas fa-long-arrow-alt-right"></i>
                </button>
              )}
            </div>
          </div>
        </div>
        {isFormOpen && (
          <div className="row notification_container">
            <div className="col-sm-12 mt-20">
              <p>
                Select a plan below and and enter your email. We&apos;ll let you
                know as soon as a node becomes available.
              </p>
            </div>
            <form method="POST" action="/availability">
              <div className="col-sm-4 mt-2">
                <select
                  name="plan"
                  className="notification_plan"
                  value={selectedPlan}
                  onChange={(event) => setSelectedPlan(event.target.value)}
                  required
                >
                  {planOptions.map((planTitle) => (
                    <option key={planTitle} value={planTitle}>
                      {planTitle}
                    </option>
                  ))}
                </select>
              </div>
              <div className="col-sm-3 mt-2">
                <input
                  type="email"
                  name="email"
                  className="block input"
                  placeholder="Your Email"
                  value={email}
                  onChange={(event) => setEmail(event.target.value)}
                  required
                />
              </div>
              <div className="col-sm-3 mt-2">
                <select
                  name="region"
                  value={region}
                  onChange={(event) => setRegion(event.target.value)}
                  required
                >
                  {regionOptions.map((location) => (
                    <option key={location.value} value={location.value}>
                      {location.label}
                    </option>
                  ))}
                </select>
              </div>
              <div className="col-sm-2 mt-2">
                <button type="submit" className="btn btn-green">
                  Get Notified
                </button>
              </div>
            </form>
          </div>
        )}
      </div>
    </div>
  )
}

