import { createContext, useContext, useState, useCallback, useMemo } from 'react'

const CURRENCIES = [
  { symbol: 'EUR', label: 'Euros (€)', flag: 'eu.svg', displaySymbol: '€' },
  { symbol: 'GBP', label: 'British Pounds (£)', flag: 'gb.svg', displaySymbol: '£' },
  { symbol: 'USD', label: 'US Dollars ($)', flag: 'us.svg', displaySymbol: '$' },
]

const DEFAULT_CURRENCY = 'GBP'

function loadCurrency() {
  try {
    const stored = localStorage.getItem('currency')
    if (stored && CURRENCIES.some((c) => c.symbol === stored)) {
      return stored
    }
  } catch {
    // localStorage unavailable
  }
  return DEFAULT_CURRENCY
}

const CurrencyContext = createContext(null)

export function CurrencyProvider({ children }) {
  const [currency, setCurrencyState] = useState(loadCurrency)

  const setCurrency = useCallback((symbol) => {
    try {
      localStorage.setItem('currency', symbol)
    } catch {
      // localStorage unavailable
    }
    setCurrencyState(symbol)
  }, [])

  const currentCurrency = useMemo(
    () => CURRENCIES.find((c) => c.symbol === currency) ?? CURRENCIES[0],
    [currency],
  )

  const value = useMemo(
    () => ({
      currency,
      setCurrency,
      currencies: CURRENCIES,
      currentCurrency,
      currencySymbol: currentCurrency.displaySymbol,
    }),
    [currency, setCurrency, currentCurrency],
  )

  return <CurrencyContext.Provider value={value}>{children}</CurrencyContext.Provider>
}

// eslint-disable-next-line react-refresh/only-export-components
export function useCurrency() {
  const context = useContext(CurrencyContext)
  if (!context) {
    throw new Error('useCurrency must be used within a CurrencyProvider')
  }
  return context
}
