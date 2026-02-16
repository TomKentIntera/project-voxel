/**
 * Styled <select> dropdown — matches the Intera dark theme.
 *
 * @param {object}   props
 * @param {string}   props.label          – Visible label text
 * @param {string}   [props.placeholder]  – First disabled option text
 * @param {Array}    props.options        – [{ value, label }, …]
 * @param {string}   [props.id]           – Select id
 * @param {string}   [props.className]    – Extra class on the wrapper
 * @param {object}   rest                 – Forwarded to the <select> element
 */
export default function Select({
  label,
  placeholder,
  options = [],
  id,
  className = '',
  ...rest
}) {
  return (
    <div className={`ui-field ${className}`.trim()}>
      {label && (
        <label className="ui-select-label" htmlFor={id}>
          {label}
        </label>
      )}
      <select id={id} className="ui-select" {...rest}>
        {placeholder && <option value="">{placeholder}</option>}
        {options.map((opt) => (
          <option key={opt.value} value={opt.value}>
            {opt.label}
          </option>
        ))}
      </select>
    </div>
  )
}

