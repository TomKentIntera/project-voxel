/**
 * Mock data used by the component storybook so every component can
 * render without a live API connection.
 */

export const mockPlansData = {
  plans: [
    {
      name: 'panda',
      title: 'Panda',
      ram: 4,
      icon: 'panda.png',
      showDefaultPlans: true,
      ribbon: null,
      locations: ['uk', 'us'],
      bullets: ['Ideal for small servers', 'Up to 20 players'],
      displayPrice: { GBP: 5.99, USD: 7.49, EUR: 6.99 },
    },
    {
      name: 'fox',
      title: 'Fox',
      ram: 6,
      icon: 'fox.png',
      showDefaultPlans: true,
      ribbon: 'Popular',
      locations: ['uk', 'us', 'eu'],
      bullets: ['Great for modded servers', 'Up to 40 players'],
      displayPrice: { GBP: 8.99, USD: 11.49, EUR: 10.49 },
    },
    {
      name: 'dolphin',
      title: 'Dolphin',
      ram: 8,
      icon: 'dolphin.png',
      showDefaultPlans: true,
      ribbon: null,
      locations: ['uk'],
      bullets: ['Perfect for large servers', 'Up to 60 players'],
      displayPrice: { GBP: 11.99, USD: 14.99, EUR: 13.99 },
    },
  ],
  locations: {
    uk: { title: 'United Kingdom', flag: 'gb' },
    us: { title: 'United States', flag: 'us' },
    eu: { title: 'Europe', flag: 'eu' },
  },
  planRecommender: {
    players: [
      { label: '1-10' },
      { label: '10-20' },
      { label: '20-40' },
      { label: '40+' },
    ],
    versions: [
      { label: '1.20.x' },
      { label: '1.19.x' },
      { label: '1.18.x' },
    ],
    types: [
      { label: 'Vanilla' },
      { label: 'Modded' },
      { label: 'Plugin-based' },
    ],
  },
  modpacks: [],
}

export const mockBannerData = {
  visible: true,
  content:
    '<strong>Summer Sale!</strong> 20% off all plans for a limited time. Use code <strong>SUMMER20</strong> at checkout.',
}

export const mockFaqsData = [
  {
    title: 'How do I get started?',
    content:
      'Simply choose a plan, complete the checkout and your server will be set up within 5 minutes.',
  },
  {
    title: 'Can I upgrade my plan later?',
    content:
      'Absolutely! You can upgrade or downgrade your plan at any time from the client area.',
  },
  {
    title: 'Do you offer refunds?',
    content:
      'We offer a 48-hour money-back guarantee on all new orders. No questions asked.',
  },
  {
    title: 'What payment methods do you accept?',
    content: 'We accept PayPal, credit/debit cards and cryptocurrency.',
  },
]

