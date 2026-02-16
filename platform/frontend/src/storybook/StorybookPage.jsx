import { useState, useEffect, useMemo } from 'react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { CurrencyProvider } from '../stores/useCurrencyStore.jsx'

// ── Components under showcase ───────────────────────────────────────
import Navbar from '../components/home/Navbar'
import Banner from '../components/home/Banner'
import Hero from '../components/home/Hero'
import PricingPlans from '../components/home/PricingPlans'
import PlanCard from '../components/home/PlanCard'
import NoAvailability from '../components/home/NoAvailability'
import Features from '../components/home/Features'
import FeaturesMain from '../components/home/FeaturesMain'
import LargerPlans from '../components/home/LargerPlans'
import PlanRecommender from '../components/home/PlanRecommender'
import FAQ from '../components/home/FAQ'
import PanelSection from '../components/home/PanelSection'
import CallToAction from '../components/home/CallToAction'
import CurrencySelector from '../components/home/CurrencySelector'
import Footer from '../components/home/Footer'
import { Input, Select, Button, Checkbox, Alert } from '../components/ui'

// ── Mock data ───────────────────────────────────────────────────────
import {
  mockPlansData,
  mockBannerData,
  mockFaqsData,
} from './mockData'

// ── Legacy styles (needed by home components) ───────────────────────
import '../styles/bootstrap.min.css'
import '../styles/legacy.css'
import '../styles/legacy-responsive.css'

// ── Storybook-specific styles ───────────────────────────────────────
import './storybook.css'

// ────────────────────────────────────────────────────────────────────
// Registry of every component we want to showcase.
// Each entry can specify `legacyTheme: true` if it was designed for
// the dark legacy theme, and an optional `description`.
// ────────────────────────────────────────────────────────────────────
const COMPONENTS = [
  {
    name: 'Navbar',
    legacyTheme: true,
    description: 'Top navigation bar with logo, links, and currency selector.',
    render: () => <Navbar />,
  },
  {
    name: 'Banner',
    legacyTheme: true,
    description: 'Dismissible promotional banner fetched from the API.',
    render: () => <Banner />,
  },
  {
    name: 'Hero',
    legacyTheme: true,
    description: 'Main hero section with headline and starting price.',
    render: () => <Hero />,
  },
  {
    name: 'PricingPlans',
    legacyTheme: true,
    description: 'Grid of default plan cards.',
    render: () => <PricingPlans />,
  },
  {
    name: 'PlanCard',
    legacyTheme: true,
    description: 'Individual pricing card for a single plan.',
    render: () => (
      <div className="row">
        <PlanCard plan={mockPlansData.plans[1]} />
      </div>
    ),
  },
  {
    name: 'NoAvailability',
    legacyTheme: true,
    description: 'CTA shown when a region is out of stock.',
    render: () => <NoAvailability />,
  },
  {
    name: 'Features',
    legacyTheme: true,
    description: 'Three-column feature checklist.',
    render: () => <Features />,
  },
  {
    name: 'FeaturesMain',
    legacyTheme: true,
    description: 'Icon grid explaining why to choose Intera.',
    render: () => <FeaturesMain />,
  },
  {
    name: 'LargerPlans',
    legacyTheme: true,
    description: 'Call-to-action linking to larger plans.',
    render: () => <LargerPlans />,
  },
  {
    name: 'PlanRecommender',
    legacyTheme: true,
    description: 'Interactive wizard that recommends a plan.',
    render: () => <PlanRecommender />,
  },
  {
    name: 'FAQ',
    legacyTheme: true,
    description: 'Accordion-style frequently asked questions.',
    render: () => <FAQ />,
  },
  {
    name: 'PanelSection',
    legacyTheme: true,
    description: 'Two-column section showcasing the control panel.',
    render: () => <PanelSection />,
  },
  {
    name: 'CallToAction',
    legacyTheme: true,
    description: 'Reusable CTA banner with colour and mob variants.',
    render: () => (
      <CallToAction color="green" mob="skeleton">
        <div className="col-sm-6">
          <h3>Ready to get started?</h3>
          <p>Just select a plan and be online in 5 minutes!</p>
        </div>
        <div className="col-sm-6">
          <div className="buttons">
            <a href="#" className="btn btn-outline btn-large">
              Select a plan <i className="fas fa-long-arrow-alt-right"></i>
            </a>
          </div>
        </div>
      </CallToAction>
    ),
  },
  {
    name: 'CurrencySelector',
    legacyTheme: true,
    description: 'Dropdown to switch display currency.',
    render: () => (
      <div style={{ background: '#1b2540', padding: '1rem 1.5rem', display: 'inline-block', borderRadius: 8 }}>
        <CurrencySelector />
      </div>
    ),
  },
  {
    name: 'Footer',
    legacyTheme: true,
    description: 'Site-wide footer with links and social icons.',
    render: () => <Footer />,
  },

  // ── UI Primitives ─────────────────────────────────────────────────
  {
    name: 'Input',
    legacyTheme: true,
    description: 'Text input with label — supports all native input types.',
    render: () => (
      <div style={{ maxWidth: 400, padding: 30 }}>
        <Input label="Email" id="sb-email" type="email" placeholder="you@example.com" />
        <Input label="Password" id="sb-password" type="password" placeholder="••••••••" />
        <Input label="Disabled" id="sb-disabled" disabled placeholder="Cannot edit" />
      </div>
    ),
  },
  {
    name: 'Select',
    legacyTheme: true,
    description: 'Dropdown select with label and placeholder.',
    render: () => (
      <div style={{ maxWidth: 400, padding: 30 }}>
        <Select
          label="Server Location"
          placeholder="Choose a location"
          options={[
            { value: 'de', label: 'Germany' },
            { value: 'fi', label: 'Finland' },
            { value: 'us', label: 'United States' },
          ]}
        />
        <Select
          label="Disabled Select"
          placeholder="Cannot change"
          disabled
          options={[]}
        />
      </div>
    ),
  },
  {
    name: 'Button',
    legacyTheme: true,
    description: 'Themed buttons — primary, secondary, outline, danger, dark; sm/md/lg sizes.',
    render: () => (
      <div style={{ padding: 30, display: 'flex', flexDirection: 'column', gap: 16 }}>
        <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap', alignItems: 'center' }}>
          <Button variant="primary">Primary</Button>
          <Button variant="secondary">Secondary</Button>
          <Button variant="outline">Outline</Button>
          <Button variant="danger">Danger</Button>
          <Button variant="dark">Dark</Button>
        </div>
        <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap', alignItems: 'center' }}>
          <Button variant="primary" size="sm">Small</Button>
          <Button variant="primary">Medium</Button>
          <Button variant="primary" size="lg">Large</Button>
        </div>
        <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap', alignItems: 'center' }}>
          <Button variant="primary" disabled>Disabled</Button>
          <Button variant="secondary" to="/">Link Button</Button>
        </div>
        <Button variant="primary" block>Block (full-width)</Button>
      </div>
    ),
  },
  {
    name: 'Checkbox',
    legacyTheme: true,
    description: 'Checkbox with label — for forms and toggles.',
    render: () => (
      <div style={{ padding: 30, display: 'flex', flexDirection: 'column', gap: 16 }}>
        <Checkbox id="sb-remember" label="Remember me" />
        <Checkbox id="sb-terms" label="I agree to the Terms of Service" />
        <Checkbox id="sb-disabled" label="Disabled checkbox" disabled />
      </div>
    ),
  },
  {
    name: 'Alert',
    legacyTheme: true,
    description: 'Alert banners — danger, success, info, warning variants.',
    render: () => (
      <div style={{ padding: 30, maxWidth: 500, display: 'flex', flexDirection: 'column', gap: 12 }}>
        <Alert variant="danger">Something went wrong. Please try again.</Alert>
        <Alert variant="success">Your account has been created successfully!</Alert>
        <Alert variant="info">Your server will be ready in approximately 5 minutes.</Alert>
        <Alert variant="warning">Your subscription expires in 3 days.</Alert>
      </div>
    ),
  },
]

// ────────────────────────────────────────────────────────────────────
// Build a QueryClient whose cache is pre-filled with mock data so
// components that use usePlans / useBanner / useFaqs render instantly.
// ────────────────────────────────────────────────────────────────────
function buildMockQueryClient() {
  const client = new QueryClient({
    defaultOptions: {
      queries: {
        staleTime: Infinity,
        retry: false,
        refetchOnWindowFocus: false,
        refetchOnMount: false,
      },
    },
  })

  // Seed the plans data (same shape that usePlans expects after fetchPlans)
  client.setQueryData(['plans'], {
    plans: mockPlansData.plans,
    locations: mockPlansData.locations,
    planRecommender: mockPlansData.planRecommender,
    modpacks: mockPlansData.modpacks,
  })

  // Seed the banner
  client.setQueryData(['banner'], mockBannerData)

  // Seed FAQs
  client.setQueryData(['faqs', 'homepage'], mockFaqsData)

  return client
}

// ────────────────────────────────────────────────────────────────────
// Page component
// ────────────────────────────────────────────────────────────────────
export default function StorybookPage() {
  const [activeIndex, setActiveIndex] = useState(0)
  const queryClient = useMemo(() => buildMockQueryClient(), [])
  const active = COMPONENTS[activeIndex]

  // Apply the legacy-theme class when the current component needs it
  useEffect(() => {
    if (active.legacyTheme) {
      document.body.classList.add('legacy-theme')
    } else {
      document.body.classList.remove('legacy-theme')
    }
    return () => document.body.classList.remove('legacy-theme')
  }, [active.legacyTheme])

  return (
    <QueryClientProvider client={queryClient}>
      <CurrencyProvider>
        <div className="storybook">
          {/* ── Sidebar ──────────────────────────────────── */}
          <aside className="storybook-sidebar">
            <div className="storybook-sidebar-header">
              <h1>📦 Component Storybook</h1>
              <p>{COMPONENTS.length} components</p>
            </div>

            <h2>Home / Legacy</h2>
            <ul className="storybook-nav">
              {COMPONENTS.map((comp, idx) => (
                <li key={comp.name}>
                  <button
                    className={idx === activeIndex ? 'active' : undefined}
                    onClick={() => setActiveIndex(idx)}
                  >
                    {comp.name}
                  </button>
                </li>
              ))}
            </ul>
          </aside>

          {/* ── Main preview area ────────────────────────── */}
          <main className="storybook-main">
            <div className="storybook-toolbar">
              <h3>{active.name}</h3>
              {active.description && (
                <span style={{ fontSize: '0.8rem', color: '#64748b' }}>
                  — {active.description}
                </span>
              )}
            </div>

            <div
              className={`storybook-preview${active.legacyTheme ? ' storybook-preview-dark' : ''}`}
            >
              {active.render()}
            </div>
          </main>
        </div>
      </CurrencyProvider>
    </QueryClientProvider>
  )
}

