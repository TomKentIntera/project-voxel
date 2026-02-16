import { useState, useCallback } from 'react'
import { usePlans } from '../../hooks/usePlans'
import { apiRequest } from '../../utils/apiClient'
import { Select } from '../ui'

export default function PlanRecommender({ showHeaderBg = false }) {
  const { planRecommender, currencySymbol, getPlanPrice, isLoading } = usePlans()

  const [players, setPlayers] = useState('')
  const [version, setVersion] = useState('')
  const [serverType, setServerType] = useState('')

  const [recommended, setRecommended] = useState(null)
  const [fetching, setFetching] = useState(false)

  const fetchRecommendation = useCallback(
    async (p, v, t) => {
      if (!p || !v || !t) {
        setRecommended(null)
        return
      }
      setFetching(true)
      try {
        const data = await apiRequest(
          `/api/plans/recommend?players=${encodeURIComponent(p)}&version=${encodeURIComponent(v)}&type=${encodeURIComponent(t)}`,
        )
        setRecommended(data.plan)
      } catch {
        setRecommended(null)
      } finally {
        setFetching(false)
      }
    },
    [],
  )

  const handlePlayers = (e) => {
    const val = e.target.value
    setPlayers(val)
    fetchRecommendation(val, version, serverType)
  }

  const handleVersion = (e) => {
    const val = e.target.value
    setVersion(val)
    fetchRecommendation(players, val, serverType)
  }

  const handleType = (e) => {
    const val = e.target.value
    setServerType(val)
    fetchRecommendation(players, version, val)
  }

  if (isLoading) return null

  const price = recommended ? getPlanPrice(recommended.name) : null

  return (
    <div
      className={`layout-text right-layout gray-layout padding-bottom60 padding-top60${showHeaderBg ? ' section-header-bg' : ''}`}
      style={showHeaderBg ? { backgroundImage: 'url(/images/headers/dark.png)' } : undefined}
    >
      <div className="container">
        <div className="row planSelector">
          <div className="col-sm-6">
            <h3 className="text-center">Recommended Plan</h3>
            <div className="recommended-plan">
              <div className="table">
                <div className="table-img plan-img">
                  <img
                    src={
                      recommended
                        ? `/images/plan-images/${recommended.icon}`
                        : '/images/plan-images/parrot.png'
                    }
                    className="img-center img-responsive"
                    alt={recommended ? recommended.title : 'Please select a plan'}
                  />
                </div>
                <div className="table-content">
                  <h4>{recommended ? recommended.title : 'Select An Option'}</h4>
                  <p className="plan-ram">
                    {recommended
                      ? `${recommended.ram} GB`
                      : 'On the right'}
                  </p>
                  {recommended && (
                    <>
                      <p className="plan-price">
                        {currencySymbol}
                        {price}/month
                      </p>
                      <div className="buttons">
                        <a
                          href={`/plan/configure/${recommended.name}`}
                          className="btn btn-green btn-medium btn-90"
                        >
                          Configure
                        </a>
                      </div>
                    </>
                  )}
                </div>
              </div>
            </div>
          </div>
          <div className="col-sm-6">
            <div className="text-container">
              <h3>What plan do I need?</h3>
              <div className="text-content">
                <div className="text">
                  <p>
                    Not sure what plan is best for you? Use our plan helper below
                    to understand what plan might be best for your needs.
                  </p>
                </div>
              </div>

              <Select
                label="How many players will be on your server?"
                placeholder="Select number of players"
                value={players}
                onChange={handlePlayers}
                disabled={fetching}
                options={planRecommender.players.map((opt) => ({
                  value: opt.label,
                  label: opt.label,
                }))}
              />

              <Select
                label="What version will you be running?"
                placeholder="Select a Minecraft version"
                value={version}
                onChange={handleVersion}
                disabled={fetching}
                options={planRecommender.versions.map((opt) => ({
                  value: opt.label,
                  label: opt.label,
                }))}
              />

              <Select
                label="What type of server will you be running?"
                placeholder="Select a server type"
                value={serverType}
                onChange={handleType}
                disabled={fetching}
                options={planRecommender.types.map((opt) => ({
                  value: opt.label,
                  label: opt.label,
                }))}
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
