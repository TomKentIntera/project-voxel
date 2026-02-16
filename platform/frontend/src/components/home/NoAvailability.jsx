export default function NoAvailability() {
  return (
    <div
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
              <a className="btn btn-outline btn-large">
                Get notified of availability!{' '}
                <i className="fas fa-long-arrow-alt-right"></i>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

