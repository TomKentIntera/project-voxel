import { useState, useEffect, useCallback } from 'react'
import { Link, useLocation } from 'react-router-dom'
import CurrencySelector from './CurrencySelector'

const COLLAPSE_BREAKPOINT = 992

export default function Navbar() {
  const [menuOpen, setMenuOpen] = useState(false)
  const [isMobile, setIsMobile] = useState(
    () => typeof window !== 'undefined' && window.innerWidth <= COLLAPSE_BREAKPOINT
  )

  useEffect(() => {
    const onResize = () => {
      const mobile = window.innerWidth <= COLLAPSE_BREAKPOINT
      setIsMobile(mobile)
      if (!mobile) setMenuOpen(false)
    }
    window.addEventListener('resize', onResize)
    return () => window.removeEventListener('resize', onResize)
  }, [])

  const closeMenu = useCallback(() => {
    if (isMobile) setMenuOpen(false)
  }, [isMobile])

  // Build class names for collapse behavior
  // When mobile + closed: 'bootsnav-hidden' hides the nav with !important
  // When mobile + open: 'bootsnav-open' shows it with !important
  // When desktop: standard 'collapse' (Bootstrap shows it via its own rules)
  let collapseClass = 'navbar-collapse'
  if (isMobile) {
    collapseClass += menuOpen ? ' bootsnav-open' : ' bootsnav-hidden'
  } else {
    collapseClass += ' collapse'
  }

  const { pathname } = useLocation()

  return (
    <nav className="navbar navbar-default dark navbar-sticky no-background bootsnav">
      <div className="custom-width">
        <div className="navbar-header">
          <button
            type="button"
            className={`navbar-toggle${isMobile ? ' bootsnav-toggle-visible' : ''}`}
            onClick={() => setMenuOpen((prev) => !prev)}
            aria-label="Toggle navigation"
            aria-expanded={menuOpen}
          >
            <i className={`fa ${menuOpen ? 'fa-times' : 'fa-bars'}`}></i>
          </button>
          <Link className="navbar-brand" to="/" onClick={closeMenu}>
            <img src="/images/logo.png" className="logo" alt="Intera Games" />
          </Link>
        </div>

        <div className={collapseClass} id="navbar-menu">
          <ul className="nav navbar-nav navbar-right" data-in="fadeIn" data-out="fadeOut">
            <li>
              <Link to="/" className={pathname === '/' ? 'active' : ''} onClick={closeMenu}>Home</Link>
            </li>
            <li>
              <Link to="/plans" className={pathname === '/plans' ? 'active' : ''} onClick={closeMenu}>Plans</Link>
            </li>
            <li>
              <Link to="/modpack/vaulthunters" className={pathname.startsWith('/modpack/vaulthunters') ? 'active' : ''} onClick={closeMenu}>
                <img src="/images/vh_logo.png" alt="Vault Hunters" />
              </Link>
            </li>
            <li>
              <Link to="/faqs" className={pathname === '/faqs' ? 'active' : ''} onClick={closeMenu}>FAQs</Link>
            </li>
            <li>
              <Link to="/dashboard" className="btn btn-primary" onClick={closeMenu}>
                <i className="fa fa-user"></i> Client Area
              </Link>
            </li>
            <li>
              <CurrencySelector />
            </li>
          </ul>
        </div>
      </div>
    </nav>
  )
}
