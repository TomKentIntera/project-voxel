import { Link } from 'react-router-dom'

export default function Footer() {
  return (
    <>
      {/* Dark Footer */}
      <footer className="light-footer dark-footer">
        <div className="custom-width">
          <div className="row">
            <div className="col-sm-2">
              <h4>Contact</h4>
              <div className="location-info">
                <h5>423 Birmingham Road, Redditch, UK</h5>
                <h5>
                  <i className="fa fa-envelope"></i> info@intera.digital
                </h5>
              </div>
            </div>
            <div className="col-sm-8">
              <h4>Important Links</h4>
              <ul>
                <li>
                  <a href="/plans">Hosting Plans</a>
                </li>
                <li>
                  <a href="/faqs">FAQs</a>
                </li>
                <li>
                  <a href="/helpdesk">Support</a>
                </li>
              </ul>
            </div>
            <div className="col-sm-2">
              <h4>Social Media</h4>
              <div className="social-media">
                <a href="#">
                  <i className="fab fa-facebook-f"></i>
                </a>
                <a href="#">
                  <i className="fab fa-google"></i>
                </a>
                <a href="#">
                  <i className="fab fa-linkedin-in"></i>
                </a>
                <a href="#">
                  <i className="fab fa-instagram"></i>
                </a>
              </div>
            </div>
          </div>
        </div>
      </footer>

      {/* Under Footer */}
      <div className="under-footer">
        <div className="custom-width">
          <div className="row">
            <div className="col-sm-8">
              <div className="under_footer_links">
                <a href="/helpdesk">Contact Us</a>
                <Link to="/terms">Terms of Services</Link>
                <Link to="/privacy-policy">Privacy Policy</Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  )
}

