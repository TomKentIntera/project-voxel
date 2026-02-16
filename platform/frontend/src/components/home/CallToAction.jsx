/**
 * Reusable Call-to-Action banner.
 *
 * ## Variants
 *
 * **"default"** – mob image as a subtle background; content via children
 * (typically two col-sm-6 columns).
 *
 * **"image"** – three-column flex layout: visible image | text | button.
 * Pass `title`, `description`, `buttonText`, `buttonHref` instead of children.
 *
 * @param {object}  props
 * @param {'default'|'image'} [props.variant='default'] - Layout variant.
 * @param {'blue'|'green'|'red'} [props.color]          - Background colour.
 * @param {string}  [props.mob='skeleton']               - Mob image name (no extension).
 * @param {string}  [props.title]                        - Heading text (image variant).
 * @param {string}  [props.description]                  - Body text (image variant).
 * @param {string}  [props.buttonText]                   - Button label (image variant).
 * @param {string}  [props.buttonHref]                   - Button URL (image variant).
 * @param {React.ReactNode} [props.children]             - Free-form content (default variant).
 */
export default function CallToAction({
  variant = 'default',
  color,
  mob = 'skeleton',
  title,
  description,
  buttonText,
  buttonHref = '#',
  children,
}) {
  const colorClass = color ? `cta-${color}` : ''

  if (variant === 'image') {
    return (
      <div className={`call-to-action cta-image ${colorClass}`.trim()}>
        <div className="custom-width">
          <div className="cta-image-inner">
            <div className="cta-image-col">
              <img
                src={`/images/plan-images/${mob}.png`}
                alt={mob}
                className="cta-image-mob"
              />
            </div>
            <div className="cta-image-text">
              {title && <h3>{title}</h3>}
              {description && <p>{description}</p>}
            </div>
            <div className="cta-image-action">
              <a href={buttonHref} className="btn btn-outline btn-large">
                {buttonText || 'Learn more'}{' '}
                <i className="fas fa-long-arrow-alt-right"></i>
              </a>
            </div>
          </div>
        </div>
      </div>
    )
  }

  // Default variant – mob as background image
  return (
    <div
      className={`call-to-action ${colorClass} cta-bg`.trim()}
      style={{
        backgroundImage: `url(/images/plan-images/${mob}.png)`,
        backgroundSize: '140px',
        backgroundPosition: '140px 30px',
      }}
    >
      <div className="custom-width">
        <div className="row">
          {children}
        </div>
      </div>
    </div>
  )
}
