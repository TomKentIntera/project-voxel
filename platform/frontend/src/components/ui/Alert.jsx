/**
 * Alert banner for messages — danger, success, info, warning variants.
 *
 * @param {object}  props
 * @param {'danger'|'success'|'info'|'warning'} [props.variant='danger']
 * @param {string}  [props.className] – Extra class on the wrapper
 * @param {React.ReactNode} props.children
 */
export default function Alert({ variant = 'danger', className = '', children }) {
  if (!children) return null

  return (
    <div className={`ui-alert ui-alert-${variant} ${className}`.trim()}>
      {typeof children === 'string' ? <p>{children}</p> : children}
    </div>
  )
}

