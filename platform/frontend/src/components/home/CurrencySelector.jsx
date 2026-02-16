import { useCurrency } from '../../stores/useCurrencyStore.jsx'

export default function CurrencySelector() {
  const { currency, setCurrency, currencies, currentCurrency } = useCurrency()

  return (
    <div className="currency-select">
      <a>
        <span className="flag">
          <img
            src={`/images/flags/${currentCurrency.flag}`}
            width="48"
            alt={currentCurrency.label}
          />
        </span>
        {currentCurrency.symbol}
      </a>
      <div className="currency-container" style={{ display: 'none' }}>
        {currencies.map((curr) => (
          <span key={curr.symbol}>
            <a
              href="#"
              className={curr.symbol === currency ? 'selected' : undefined}
              onClick={(e) => {
                e.preventDefault()
                setCurrency(curr.symbol)
              }}
            >
              <span className="flag">
                <img src={`/images/flags/${curr.flag}`} width="48" alt={curr.label} />
              </span>
              <span className="currency">{curr.label}</span>
            </a>
          </span>
        ))}
      </div>
    </div>
  )
}

