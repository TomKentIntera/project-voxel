import { Link } from 'react-router-dom'

/**
 * Themed button with variant, size, and polymorphic element support.
 *
 * @param {object}  props
 * @param {'primary'|'secondary'|'outline'|'danger'|'dark'} [props.variant='primary']
 * @param {'sm'|'md'|'lg'} [props.size='md']
 * @param {boolean} [props.block=false]    – Full-width
 * @param {string}  [props.to]             – If set, renders a React-Router <Link>
 * @param {string}  [props.href]           – If set, renders an <a>
 * @param {string}  [props.className]      – Extra classes
 * @param {object}  rest                   – Forwarded to the root element
 */
export default function Button({
  variant = 'primary',
  size = 'md',
  block = false,
  to,
  href,
  className = '',
  children,
  ...rest
}) {
  const classes = [
    'ui-btn',
    `ui-btn-${variant}`,
    size !== 'md' && `ui-btn-${size}`,
    block && 'ui-btn-block',
    className,
  ]
    .filter(Boolean)
    .join(' ')

  if (to) {
    return (
      <Link to={to} className={classes} {...rest}>
        {children}
      </Link>
    )
  }

  if (href) {
    return (
      <a href={href} className={classes} {...rest}>
        {children}
      </a>
    )
  }

  return (
    <button type="button" className={classes} {...rest}>
      {children}
    </button>
  )
}

