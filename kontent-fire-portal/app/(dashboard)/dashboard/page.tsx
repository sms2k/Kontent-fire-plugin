'use client'

import Link from 'next/link'

export default function DashboardPage() {
  // Mock data - will be replaced with real data from Supabase
  const stats = {
    credits: {
      remaining: 2450,
      total: 5000,
      percentage: 49,
    },
    usage: {
      blogs: 12,
      images: 45,
      videos: 3,
      social: 28,
    },
    plan: {
      name: 'Pro',
      price: 99,
      renewal: '2025-12-15',
    },
  }

  const recentActivity = [
    { id: 1, type: 'blog', title: 'AI-Powered Marketing Strategies', credits: 50, time: '2 hours ago', status: 'completed' },
    { id: 2, type: 'image', title: 'Product showcase banner', credits: 10, time: '5 hours ago', status: 'completed' },
    { id: 3, type: 'social', title: 'Instagram post series', credits: 15, time: '1 day ago', status: 'completed' },
    { id: 4, type: 'video', title: 'YouTube intro video script', credits: 20, time: '2 days ago', status: 'completed' },
  ]

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Welcome back! 🔥</h1>
        <p className="text-gray-600 mt-1">Here's what's happening with your content creation.</p>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {/* Credits Card */}
        <div className="bg-white rounded-xl border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-4">
            <div className="w-12 h-12 rounded-lg bg-brand-100 flex items-center justify-center">
              <svg className="w-6 h-6 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <span className="text-xs font-medium text-green-600 bg-green-100 px-2 py-1 rounded-full">
              {stats.credits.percentage}% left
            </span>
          </div>
          <p className="text-sm text-gray-600 mb-1">Credits Remaining</p>
          <p className="text-3xl font-bold text-gray-900">{stats.credits.remaining.toLocaleString()}</p>
          <p className="text-xs text-gray-500 mt-2">of {stats.credits.total.toLocaleString()} total</p>
          <div className="w-full bg-gray-200 rounded-full h-2 mt-4">
            <div
              className="fire-gradient h-2 rounded-full transition-all duration-500"
              style={{ width: `${stats.credits.percentage}%` }}
            />
          </div>
        </div>

        {/* Blogs Created */}
        <div className="bg-white rounded-xl border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-4">
            <div className="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center">
              <svg className="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
          </div>
          <p className="text-sm text-gray-600 mb-1">Blogs Created</p>
          <p className="text-3xl font-bold text-gray-900">{stats.usage.blogs}</p>
          <p className="text-xs text-gray-500 mt-2">This month</p>
        </div>

        {/* Images Generated */}
        <div className="bg-white rounded-xl border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-4">
            <div className="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center">
              <svg className="w-6 h-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
          </div>
          <p className="text-sm text-gray-600 mb-1">Images Generated</p>
          <p className="text-3xl font-bold text-gray-900">{stats.usage.images}</p>
          <p className="text-xs text-gray-500 mt-2">This month</p>
        </div>

        {/* Social Posts */}
        <div className="bg-white rounded-xl border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-4">
            <div className="w-12 h-12 rounded-lg bg-pink-100 flex items-center justify-center">
              <svg className="w-6 h-6 text-pink-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
              </svg>
            </div>
          </div>
          <p className="text-sm text-gray-600 mb-1">Social Posts</p>
          <p className="text-3xl font-bold text-gray-900">{stats.usage.social}</p>
          <p className="text-xs text-gray-500 mt-2">This month</p>
        </div>
      </div>

      {/* Two Column Layout */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Recent Activity */}
        <div className="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-6">
            <h2 className="text-lg font-semibold text-gray-900">Recent Activity</h2>
            <Link href="/dashboard/usage" className="text-sm text-brand-600 hover:text-brand-700 font-medium">
              View all →
            </Link>
          </div>

          <div className="space-y-4">
            {recentActivity.map((activity) => {
              const icons = {
                blog: (
                  <div className="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                    <svg className="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                  </div>
                ),
                image: (
                  <div className="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                    <svg className="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                  </div>
                ),
                video: (
                  <div className="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center">
                    <svg className="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                  </div>
                ),
                social: (
                  <div className="w-10 h-10 rounded-lg bg-pink-100 flex items-center justify-center">
                    <svg className="w-5 h-5 text-pink-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                    </svg>
                  </div>
                ),
              }

              return (
                <div key={activity.id} className="flex items-center gap-4 p-4 rounded-lg hover:bg-gray-50 transition">
                  {icons[activity.type as keyof typeof icons]}
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium text-gray-900 truncate">{activity.title}</p>
                    <p className="text-xs text-gray-500">{activity.time}</p>
                  </div>
                  <div className="text-right">
                    <p className="text-sm font-semibold text-gray-900">{activity.credits}</p>
                    <p className="text-xs text-gray-500">credits</p>
                  </div>
                </div>
              )
            })}
          </div>
        </div>

        {/* Quick Actions & Plan Info */}
        <div className="space-y-6">
          {/* Current Plan */}
          <div className="bg-gradient-to-br from-brand-500 to-fire-500 rounded-xl p-6 text-white glow-brand">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-lg font-semibold">{stats.plan.name} Plan</h3>
              <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
              </svg>
            </div>
            <p className="text-3xl font-bold mb-2">${stats.plan.price}<span className="text-lg font-normal">/mo</span></p>
            <p className="text-white/80 text-sm mb-4">Renews on {new Date(stats.plan.renewal).toLocaleDateString()}</p>
            <Link
              href="/dashboard/billing"
              className="block w-full text-center py-2 px-4 bg-white/20 hover:bg-white/30 rounded-lg font-medium transition"
            >
              Manage Plan
            </Link>
          </div>

          {/* Quick Actions */}
          <div className="bg-white rounded-xl border border-gray-200 p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
            <div className="space-y-3">
              <button className="w-full flex items-center gap-3 px-4 py-3 bg-brand-50 hover:bg-brand-100 rounded-lg text-brand-700 font-medium transition">
                <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                </svg>
                New License Key
              </button>
              <button className="w-full flex items-center gap-3 px-4 py-3 bg-gray-50 hover:bg-gray-100 rounded-lg text-gray-700 font-medium transition">
                <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                View Docs
              </button>
              <button className="w-full flex items-center gap-3 px-4 py-3 bg-gray-50 hover:bg-gray-100 rounded-lg text-gray-700 font-medium transition">
                <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                Support
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
