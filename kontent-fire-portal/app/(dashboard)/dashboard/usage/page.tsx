'use client'

export default function UsagePage() {
  // Mock data for charts
  const creditsData = {
    total: 5000,
    used: 2550,
    remaining: 2450,
    percentage: 49,
  }

  const usageByType = [
    { type: 'Blog Posts', credits: 1250, count: 50, percentage: 49 },
    { type: 'Images', credits: 450, count: 45, percentage: 18 },
    { type: 'Social Posts', credits: 420, count: 140, percentage: 16 },
    { type: 'Videos', credits: 300, count: 15, percentage: 12 },
    { type: 'SEO Analysis', credits: 130, count: 26, percentage: 5 },
  ]

  const recentTransactions = [
    { id: 1, operation: 'Auto-blog generation', credits: 75, date: '2024-12-01 14:32', site: 'myblog.com' },
    { id: 2, operation: 'Image generation (3x)', credits: 30, date: '2024-12-01 12:15', site: 'myblog.com' },
    { id: 3, operation: 'Blog post (long)', credits: 50, date: '2024-11-30 18:45', site: 'mystore.com' },
    { id: 4, operation: 'Social media posts', credits: 15, date: '2024-11-30 16:20', site: 'myblog.com' },
    { id: 5, operation: 'Image generation', credits: 10, date: '2024-11-30 14:10', site: 'mystore.com' },
    { id: 6, operation: 'SEO analysis', credits: 5, date: '2024-11-30 11:30', site: 'myblog.com' },
    { id: 7, operation: 'Blog post (short)', credits: 25, date: '2024-11-29 15:00', site: 'myblog.com' },
  ]

  const dailyUsage = [
    { date: 'Dec 1', credits: 175 },
    { date: 'Nov 30', credits: 110 },
    { date: 'Nov 29', credits: 95 },
    { date: 'Nov 28', credits: 150 },
    { date: 'Nov 27', credits: 80 },
    { date: 'Nov 26', credits: 120 },
    { date: 'Nov 25', credits: 95 },
  ]

  const maxDailyCredits = Math.max(...dailyUsage.map(d => d.credits))

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Usage & Analytics</h1>
        <p className="text-gray-600 mt-1">Track your credit consumption and API usage</p>
      </div>

      {/* Credits Overview */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div className="bg-white rounded-xl border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-sm font-medium text-gray-600">Total Credits</h3>
            <svg className="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <p className="text-3xl font-bold text-gray-900">{creditsData.total.toLocaleString()}</p>
          <p className="text-sm text-gray-500 mt-1">Monthly allocation</p>
        </div>

        <div className="bg-white rounded-xl border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-sm font-medium text-gray-600">Used</h3>
            <svg className="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 14l-7 7m0 0l-7-7m7 7V3" />
            </svg>
          </div>
          <p className="text-3xl font-bold text-red-600">{creditsData.used.toLocaleString()}</p>
          <p className="text-sm text-gray-500 mt-1">{creditsData.percentage}% of total</p>
        </div>

        <div className="bg-white rounded-xl border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-sm font-medium text-gray-600">Remaining</h3>
            <svg className="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <p className="text-3xl font-bold text-green-600">{creditsData.remaining.toLocaleString()}</p>
          <p className="text-sm text-gray-500 mt-1">{100 - creditsData.percentage}% available</p>
        </div>
      </div>

      {/* Usage by Type */}
      <div className="bg-white rounded-xl border border-gray-200 p-6">
        <h2 className="text-lg font-semibold text-gray-900 mb-6">Usage by Content Type</h2>
        <div className="space-y-4">
          {usageByType.map((item) => (
            <div key={item.type}>
              <div className="flex items-center justify-between mb-2">
                <div className="flex items-center gap-3">
                  <span className="text-sm font-medium text-gray-900">{item.type}</span>
                  <span className="text-xs text-gray-500">({item.count} items)</span>
                </div>
                <div className="text-right">
                  <span className="text-sm font-semibold text-gray-900">{item.credits}</span>
                  <span className="text-xs text-gray-500 ml-1">credits</span>
                </div>
              </div>
              <div className="relative">
                <div className="w-full bg-gray-200 rounded-full h-3">
                  <div
                    className="bg-gradient-to-r from-brand-500 to-fire-500 h-3 rounded-full transition-all duration-500"
                    style={{ width: `${item.percentage}%` }}
                  />
                </div>
                <span className="absolute right-2 top-0.5 text-[10px] font-medium text-white">
                  {item.percentage}%
                </span>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Daily Usage Chart */}
      <div className="bg-white rounded-xl border border-gray-200 p-6">
        <h2 className="text-lg font-semibold text-gray-900 mb-6">Daily Credit Usage (Last 7 Days)</h2>
        <div className="flex items-end justify-between gap-2 h-64">
          {dailyUsage.map((day, i) => {
            const height = (day.credits / maxDailyCredits) * 100
            return (
              <div key={i} className="flex-1 flex flex-col items-center gap-2">
                <div className="w-full flex flex-col items-center justify-end h-full">
                  <span className="text-xs font-medium text-gray-600 mb-1">{day.credits}</span>
                  <div
                    className="w-full bg-gradient-to-t from-brand-500 to-fire-400 rounded-t-lg transition-all duration-500 hover:opacity-80 cursor-pointer"
                    style={{ height: `${height}%`, minHeight: '20px' }}
                    title={`${day.date}: ${day.credits} credits`}
                  />
                </div>
                <span className="text-xs text-gray-500 mt-2">{day.date.split(' ')[1]}</span>
              </div>
            )
          })}
        </div>
      </div>

      {/* Recent Transactions */}
      <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div className="px-6 py-4 border-b border-gray-200">
          <h2 className="text-lg font-semibold text-gray-900">Recent Transactions</h2>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead>
              <tr className="bg-gray-50">
                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  Operation
                </th>
                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  Site
                </th>
                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  Date & Time
                </th>
                <th className="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  Credits
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {recentTransactions.map((transaction) => (
                <tr key={transaction.id} className="hover:bg-gray-50">
                  <td className="px-6 py-4 text-sm font-medium text-gray-900">
                    {transaction.operation}
                  </td>
                  <td className="px-6 py-4 text-sm text-gray-600">{transaction.site}</td>
                  <td className="px-6 py-4 text-sm text-gray-600">{transaction.date}</td>
                  <td className="px-6 py-4 text-right">
                    <span className="text-sm font-semibold text-red-600">-{transaction.credits}</span>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <div className="px-6 py-4 bg-gray-50 border-t border-gray-200">
          <button className="text-sm text-brand-600 hover:text-brand-700 font-medium">
            View All Transactions →
          </button>
        </div>
      </div>

      {/* Cost Breakdown */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div className="bg-white rounded-xl border border-gray-200 p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">Credit Costs</h3>
          <div className="space-y-3">
            <div className="flex justify-between items-center text-sm">
              <span className="text-gray-600">Blog Post (Short)</span>
              <span className="font-semibold text-gray-900">25 credits</span>
            </div>
            <div className="flex justify-between items-center text-sm">
              <span className="text-gray-600">Blog Post (Long)</span>
              <span className="font-semibold text-gray-900">50 credits</span>
            </div>
            <div className="flex justify-between items-center text-sm">
              <span className="text-gray-600">Auto-Blog (Full)</span>
              <span className="font-semibold text-gray-900">75 credits</span>
            </div>
            <div className="flex justify-between items-center text-sm">
              <span className="text-gray-600">Image Generation</span>
              <span className="font-semibold text-gray-900">10 credits</span>
            </div>
            <div className="flex justify-between items-center text-sm">
              <span className="text-gray-600">Video Script</span>
              <span className="font-semibold text-gray-900">20 credits</span>
            </div>
            <div className="flex justify-between items-center text-sm">
              <span className="text-gray-600">SEO Analysis</span>
              <span className="font-semibold text-gray-900">5 credits</span>
            </div>
            <div className="flex justify-between items-center text-sm">
              <span className="text-gray-600">Social Media Post</span>
              <span className="font-semibold text-gray-900">3 credits</span>
            </div>
          </div>
        </div>

        <div className="bg-gradient-to-br from-brand-50 to-fire-50 rounded-xl border border-brand-200 p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">Need More Credits?</h3>
          <p className="text-sm text-gray-700 mb-4">
            You're using credits at a great pace! Consider upgrading for more credits and additional features.
          </p>
          <div className="space-y-2 mb-4">
            <div className="flex items-center gap-2 text-sm text-gray-700">
              <svg className="w-4 h-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
              </svg>
              Enterprise: 20,000 credits/month
            </div>
            <div className="flex items-center gap-2 text-sm text-gray-700">
              <svg className="w-4 h-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
              </svg>
              Unlimited WordPress sites
            </div>
            <div className="flex items-center gap-2 text-sm text-gray-700">
              <svg className="w-4 h-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
              </svg>
              Priority support & API access
            </div>
          </div>
          <a
            href="/dashboard/billing"
            className="inline-block px-6 py-2 bg-gradient-to-r from-brand-500 to-fire-500 text-white rounded-lg font-medium hover:opacity-90 transition"
          >
            Upgrade Now
          </a>
        </div>
      </div>
    </div>
  )
}
