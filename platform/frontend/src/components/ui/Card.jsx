function Card({ as: Component = 'div', className = '', children }) {
  const classes = ['ui-card', className].filter(Boolean).join(' ')

  return <Component className={classes}>{children}</Component>
}

function CardHeader({ as: Component = 'div', className = '', children }) {
  const classes = ['ui-card-header', className].filter(Boolean).join(' ')

  return <Component className={classes}>{children}</Component>
}

function CardBody({ as: Component = 'div', className = '', children }) {
  const classes = ['ui-card-body', className].filter(Boolean).join(' ')

  return <Component className={classes}>{children}</Component>
}

Card.Header = CardHeader
Card.Body = CardBody

export default Card
