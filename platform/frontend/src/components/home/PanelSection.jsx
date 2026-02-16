export default function PanelSection() {
  return (
    <div className="layout-text right-layout padding-bottom60 padding-top60">
      <div className="container">
        <div className="row">
          <div className="col-lg-6 col-md-6 col-sm-12 col-xs-12">
            <img
              src="/images/panel.png"
              className="img-responsive"
              alt="Pterodactyl Panel"
            />
          </div>
          <div className="col-lg-6 col-md-6 col-sm-12 col-xs-12">
            <div className="text-container">
              <h3>Our Panel</h3>
              <div className="text-content">
                <i className="far fa-check-circle pull-left"></i>
                <div className="text">
                  <p>
                    We make use of the powerful Pterodactyl panel with custom
                    functionality to make managing your server easier than ever
                    before.
                  </p>
                </div>
              </div>
              <div className="text-content">
                <i className="far fa-check-circle pull-left"></i>
                <div className="text">
                  <p>
                    Manage your plugins, mods, files and settings all in the
                    panel, or use SFTP to manage even more!
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

