/**
 * Styled checkbox with label — matches the Intera dark theme.
 *
 * @param {object}  props
 * @param {string}  props.label       – Visible label text
 * @param {string}  [props.id]        – Input id
 * @param {string}  [props.className] – Extra class on the wrapper
 * @param {object}  rest              – Forwarded to the <input> element
 */
export default function Checkbox({ label, id, className = '', ...rest }) {
  return (
    <label htmlFor={id} className={`ui-checkbox ${className}`.trim()}>
      <input id={id} type="checkbox" className="ui-checkbox-input" {...rest} />
      {label && <span className="ui-checkbox-label">{label}</span>}
    </label>
  )
}

