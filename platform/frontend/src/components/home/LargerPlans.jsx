import CallToAction from './CallToAction'

export default function LargerPlans() {
  return (
    <CallToAction
      variant="image"
      color="blue"
      mob="skeleton"
      title="Looking for something larger?"
      description="The plans above are standard plans that suit most servers. If you've got a larger server or need more resources, we do have larger plans available!"
      buttonText="See larger plans"
      buttonHref="/plans"
    />
  )
}
