import { useEffect, useState } from 'react'
import './App.css'
import { fetchHealth, fetchLegacyDomains, type HealthResponse, type LegacyDomain } from './lib/platformApi'

function App() {
  const [health, setHealth] = useState<HealthResponse | null>(null)
  const [domains, setDomains] = useState<LegacyDomain[]>([])
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    async function bootstrap() {
      try {
        const [healthData, legacyData] = await Promise.all([fetchHealth(), fetchLegacyDomains()])
        setHealth(healthData)
        setDomains(legacyData.domains)
      } catch (bootstrapError) {
        const message = bootstrapError instanceof Error ? bootstrapError.message : 'Unknown error'
        setError(message)
      }
    }

    bootstrap()
  }, [])

  return (
    <main className="app">
      <header>
        <h1>Platform modernization groundwork</h1>
        <p>
          React frontend in <code>platform/frontend</code> connected to Laravel API in{' '}
          <code>platform/backend</code>.
        </p>
      </header>

      {error ? (
        <section className="card error">
          <h2>Bootstrap error</h2>
          <p>{error}</p>
          <p>
            Make sure the backend is running and reachable at <code>/api/v1/*</code>.
          </p>
        </section>
      ) : null}

      <section className="card">
        <h2>Backend health</h2>
        {health ? (
          <ul>
            <li>
              <strong>Status:</strong> {health.status}
            </li>
            <li>
              <strong>Service:</strong> {health.service}
            </li>
            <li>
              <strong>API version:</strong> {health.apiVersion}
            </li>
            <li>
              <strong>Timestamp:</strong> {health.timestamp}
            </li>
          </ul>
        ) : (
          <p>Loading...</p>
        )}
      </section>

      <section className="card">
        <h2>Legacy migration domains</h2>
        {domains.length === 0 ? (
          <p>Loading...</p>
        ) : (
          <div className="domain-list">
            {domains.map((domain) => (
              <article key={domain.key} className="domain-item">
                <h3>{domain.key}</h3>
                <p>{domain.description}</p>
                <p>
                  <strong>Target API:</strong> <code>{domain.targetApi}</code>
                </p>
                <p>
                  <strong>Status:</strong> {domain.status}
                </p>
                <details>
                  <summary>Legacy routes</summary>
                  <ul>
                    {domain.legacyRoutes.map((route) => (
                      <li key={route}>
                        <code>{route}</code>
                      </li>
                    ))}
                  </ul>
                </details>
              </article>
            ))}
          </div>
        )}
      </section>
    </main>
  )
}

export default App
