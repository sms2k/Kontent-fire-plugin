'use client'

export default function BillingPage() {
  // Mock data
  const currentPlan = {
    name: 'Pro',
    price: 99,
    interval: 'month',
    credits: 5000,
    features: [
      '5,000 AI credits per month',
      '5 WordPress sites',
      'Claude Sonnet 4.5 & GPT-4',
      'Imagen 4 & Veo 3',
      'Multi-platform publishing',
      'Priority support',
      'Advanced analytics',
    ],
    nextBilling: '2025-12-15',
    status: 'active',
  }

  const plans = [
    {
      name: 'Basic',
      price: 29,
      credits: 1000,
      sites: 1,
      popular: false,
      features: [
        '1,000 AI credits/month',
        '1 WordPress site',
        'All AI models',
        'Basic support',
        'Usage analytics',
      ],
    },
    {
      name: 'Pro',
      price: 99,
      credits: 5000,
      sites: 5,
      popular: true,
      features: [
        '5,000 AI credits/month',
        '5 WordPress sites',
        'All AI models',
        'Priority support',
        'Advanced analytics',
        'OAuth integrations',
      ],
    },
    {
      name: 'Enterprise',
      price: 299,
      credits: 20000,
      sites: 'Unlimited',
      popular: false,
      features: [
        '20,000 AI credits/month',
        'Unlimited sites',
        'All AI models',
        'White label options',
        'API access',
        'Dedicated support',
        'Custom integrations',
      ],
    },
  ]

  const invoices = [
    { id: 1, date: '2024-11-15', amount: 99, status: 'paid', invoice: 'INV-001' },
    { id: 2, date: '2024-10-15', amount: 99, status: 'paid', invoice: 'INV-002' },
    { id: 3, date: '2024-09-15', amount: 99, status: 'paid', invoice: 'INV-003' },
  ]

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Billing & Subscription</h1>
        <p className="text-gray-600 mt-1">Manage your plan and payment methods</p>
      </div>

      {/* Current Plan */}
      <div className="bg-gradient-to-br from-brand-500 to-fire-500 rounded-xl p-8 text-white">
        <div className="flex items-start justify-between mb-6">
          <div>
            <p className="text-white/80 text-sm mb-1">Current Plan</p>
            <h2 className="text-3xl font-bold">{currentPlan.name}</h2>
          </div>
          <span className="px-3 py-1 bg-white/20 rounded-full text-sm font-medium">
            {currentPlan.status === 'active' ? 'Active' : 'Inactive'}
          </span>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
          <div>
            <p className="text-white/80 text-sm mb-1">Monthly Price</p>
            <p className="text-2xl font-bold">${currentPlan.price}</p>
          </div>
          <div>
            <p className="text-white/80 text-sm mb-1">Credits per Month</p>
            <p className="text-2xl font-bold">{currentPlan.credits.toLocaleString()}</p>
          </div>
          <div>
            <p className="text-white/80 text-sm mb-1">Next Billing Date</p>
            <p className="text-2xl font-bold">{new Date(currentPlan.nextBilling).toLocaleDateString()}</p>
          </div>
        </div>

        <div className="flex gap-3">
          <button className="px-6 py-2 bg-white text-brand-600 rounded-lg font-medium hover:bg-white/90 transition">
            Update Payment Method
          </button>
          <button className="px-6 py-2 bg-white/10 text-white rounded-lg font-medium hover:bg-white/20 transition">
            Cancel Subscription
          </button>
        </div>
      </div>

      {/* Available Plans */}
      <div>
        <h2 className="text-xl font-semibold text-gray-900 mb-6">Available Plans</h2>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {plans.map((plan) => (
            <div
              key={plan.name}
              className={`relative bg-white rounded-xl border-2 p-6 ${
                plan.name === currentPlan.name
                  ? 'border-brand-500'
                  : 'border-gray-200 hover:border-gray-300'
              } transition`}
            >
              {plan.popular && (
                <div className="absolute -top-3 left-1/2 -translate-x-1/2">
                  <span className="px-3 py-1 bg-brand-500 text-white text-xs font-semibold rounded-full">
                    Most Popular
                  </span>
                </div>
              )}

              <div className="text-center mb-6">
                <h3 className="text-xl font-bold text-gray-900 mb-2">{plan.name}</h3>
                <div className="mb-4">
                  <span className="text-4xl font-bold text-gray-900">${plan.price}</span>
                  <span className="text-gray-600">/month</span>
                </div>
                <p className="text-sm text-gray-600">
                  {plan.credits.toLocaleString()} credits • {plan.sites} site{typeof plan.sites === 'number' && plan.sites > 1 ? 's' : ''}
                </p>
              </div>

              <ul className="space-y-3 mb-6">
                {plan.features.map((feature, i) => (
                  <li key={i} className="flex items-start gap-3 text-sm text-gray-700">
                    <svg className="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                    </svg>
                    {feature}
                  </li>
                ))}
              </ul>

              {plan.name === currentPlan.name ? (
                <button
                  disabled
                  className="w-full py-3 px-4 bg-gray-100 text-gray-400 rounded-lg font-medium cursor-not-allowed"
                >
                  Current Plan
                </button>
              ) : (
                <button className="w-full py-3 px-4 bg-gradient-to-r from-brand-500 to-fire-500 text-white rounded-lg font-medium hover:opacity-90 transition">
                  {plan.price > currentPlan.price ? 'Upgrade' : 'Downgrade'}
                </button>
              )}
            </div>
          ))}
        </div>
      </div>

      {/* Payment Method */}
      <div className="bg-white rounded-xl border border-gray-200 p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">Payment Method</h3>
        <div className="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
          <div className="flex items-center gap-4">
            <div className="w-12 h-8 bg-gradient-to-br from-blue-500 to-blue-700 rounded flex items-center justify-center">
              <svg className="w-8 h-8 text-white" viewBox="0 0 24 24" fill="currentColor">
                <path d="M0 4v16h24V4H0zm22 14H2V8h20v10z"/>
              </svg>
            </div>
            <div>
              <p className="font-medium text-gray-900">Visa ending in 4242</p>
              <p className="text-sm text-gray-600">Expires 12/2025</p>
            </div>
          </div>
          <button className="text-sm text-brand-600 hover:text-brand-700 font-medium">
            Update
          </button>
        </div>
      </div>

      {/* Billing History */}
      <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div className="px-6 py-4 border-b border-gray-200">
          <h3 className="text-lg font-semibold text-gray-900">Billing History</h3>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead>
              <tr className="bg-gray-50">
                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  Date
                </th>
                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  Invoice
                </th>
                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  Amount
                </th>
                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  Status
                </th>
                <th className="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {invoices.map((invoice) => (
                <tr key={invoice.id} className="hover:bg-gray-50">
                  <td className="px-6 py-4 text-sm text-gray-900">
                    {new Date(invoice.date).toLocaleDateString()}
                  </td>
                  <td className="px-6 py-4 text-sm text-gray-600">{invoice.invoice}</td>
                  <td className="px-6 py-4 text-sm font-medium text-gray-900">${invoice.amount}</td>
                  <td className="px-6 py-4">
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                      {invoice.status}
                    </span>
                  </td>
                  <td className="px-6 py-4 text-right">
                    <button className="text-sm text-brand-600 hover:text-brand-700 font-medium">
                      Download
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* Add Credits */}
      <div className="bg-blue-50 border border-blue-200 rounded-xl p-6">
        <div className="flex items-start gap-4">
          <div className="flex-shrink-0 w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center">
            <svg className="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <div className="flex-1">
            <h3 className="text-lg font-semibold text-gray-900 mb-2">Need More Credits?</h3>
            <p className="text-sm text-gray-700 mb-4">
              Running low on credits? Purchase additional credits without upgrading your plan.
            </p>
            <button className="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition">
              Buy Extra Credits
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
