/**
 * Text input with label — matches the Intera dark theme.
 *
 * @param {object}  props
 * @param {string}  props.label        – Visible label text
 * @param {string}  [props.id]         – Input id (also used for htmlFor)
 * @param {string}  [props.type]       – Input type (text, email, password, …)
 * @param {string}  [props.className]  – Extra class on the wrapper
 * @param {object}  rest               – Forwarded to the <input> element
 */
export default function Input({ label, id, type = 'text', className = '', ...rest }) {
  return (
    <div className={`ui-field ${className}`.trim()}>
      {label && (
        <label className="ui-label" htmlFor={id}>
          {label}
        </label>
      )}
      <input id={id} type={type} className="ui-input" {...rest} />
    </div>
  )
}

