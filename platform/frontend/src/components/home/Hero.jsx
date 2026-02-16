import { usePlans } from '../../hooks/usePlans'

export default function Hero() {
  const { currencySymbol, getStartingPrice, isLoading } = usePlans()

  return (
    <div className="default-header shared-page">
      <div className="custom-width">
        <div className="row">
          <div className="col-sm-7">
            <div className="header-text">
              <h2>Premium Minecraft server hosting at affordable prices</h2>
              <p>
                Intera offers all the premium features you might need without
                charging the earth for them. Get your server off the ground in as
                little as 5 minutes!
              </p>
              <h4>Starting at</h4>
              <h3>
                {isLoading ? '...' : `${currencySymbol}${getStartingPrice()}/monthly`}
              </h3>
            </div>
            <div className="buttons">
              <a href="#plans" className="btn btn-green btn-large">
                Get Started Now
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
