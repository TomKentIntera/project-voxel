export default function Card({ as: Component = 'div', className = '', children }) {
  const classes = ['ui-card', className].filter(Boolean).join(' ')

  return <Component className={classes}>{children}</Component>
}
