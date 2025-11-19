'use client'

import { useState } from 'react'

export default function LicensesPage() {
  const [showNewLicenseModal, setShowNewLicenseModal] = useState(false)
  const [copiedKey, setCopiedKey] = useState<string | null>(null)

  // Mock data - will be replaced with real data from Supabase
  const licenses = [
    {
      id: 1,
      key: 'KF-8A4B2C1D-9E7F6G8H-5I3J4K2L',
      siteUrl: 'https://myblog.com',
      siteName: 'My Awesome Blog',
      plan: 'Pro',
      status: 'active',
      activatedAt: '2024-01-15',
      lastUsed: '2 hours ago',
    },
    {
      id: 2,
      key: 'KF-2F3G4H5I-6J7K8L9M-0N1O2P3Q',
      siteUrl: 'https://mystore.com',
      siteName: 'E-commerce Store',
      plan: 'Pro',
      status: 'active',
      activatedAt: '2024-02-20',
      lastUsed: '1 day ago',
    },
    {
      id: 3,
      key: 'KF-4R5S6T7U-8V9W0X1Y-2Z3A4B5C',
      siteUrl: null,
      siteName: null,
      plan: 'Pro',
      status: 'inactive',
      activatedAt: null,
      lastUsed: 'Never',
    },
  ]

  const copyToClipboard = (key: string) => {
    navigator.clipboard.writeText(key)
    setCopiedKey(key)
    setTimeout(() => setCopiedKey(null), 2000)
  }

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">License Keys</h1>
          <p className="text-gray-600 mt-1">Manage your WordPress site licenses</p>
        </div>
        <button
          onClick={() => setShowNewLicenseModal(true)}
          className="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-brand-500 to-fire-500 text-white rounded-lg hover:opacity-90 transition font-medium"
        >
          <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
          </svg>
          Generate New License
        </button>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div className="bg-white rounded-lg border border-gray-200 p-4">
          <p className="text-sm text-gray-600">Total Licenses</p>
          <p className="text-2xl font-bold text-gray-900 mt-1">{licenses.length}</p>
        </div>
        <div className="bg-white rounded-lg border border-gray-200 p-4">
          <p className="text-sm text-gray-600">Active</p>
          <p className="text-2xl font-bold text-green-600 mt-1">
            {licenses.filter((l) => l.status === 'active').length}
          </p>
        </div>
        <div className="bg-white rounded-lg border border-gray-200 p-4">
          <p className="text-sm text-gray-600">Inactive</p>
          <p className="text-2xl font-bold text-gray-400 mt-1">
            {licenses.filter((l) => l.status === 'inactive').length}
          </p>
        </div>
        <div className="bg-white rounded-lg border border-gray-200 p-4">
          <p className="text-sm text-gray-600">Available Slots</p>
          <p className="text-2xl font-bold text-brand-600 mt-1">2 / 5</p>
        </div>
      </div>

      {/* Licenses Table */}
      <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead>
              <tr className="bg-gray-50 border-b border-gray-200">
                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  License Key
                </th>
                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  Site
                </th>
                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  Plan
                </th>
                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  Status
                </th>
                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  Last Used
                </th>
                <th className="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {licenses.map((license) => (
                <tr key={license.id} className="hover:bg-gray-50 transition">
                  <td className="px-6 py-4">
                    <div className="flex items-center gap-3">
                      <code className="text-sm font-mono text-gray-900 bg-gray-100 px-3 py-1 rounded">
                        {license.key.substring(0, 20)}...
                      </code>
                      <button
                        onClick={() => copyToClipboard(license.key)}
                        className="text-gray-400 hover:text-brand-600 transition"
                        title="Copy to clipboard"
                      >
                        {copiedKey === license.key ? (
                          <svg className="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                          </svg>
                        ) : (
                          <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                          </svg>
                        )}
                      </button>
                    </div>
                  </td>
                  <td className="px-6 py-4">
                    {license.siteUrl ? (
                      <div>
                        <p className="text-sm font-medium text-gray-900">{license.siteName}</p>
                        <a
                          href={license.siteUrl}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="text-xs text-brand-600 hover:text-brand-700"
                        >
                          {license.siteUrl}
                        </a>
                      </div>
                    ) : (
                      <span className="text-sm text-gray-400">Not activated</span>
                    )}
                  </td>
                  <td className="px-6 py-4">
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                      {license.plan}
                    </span>
                  </td>
                  <td className="px-6 py-4">
                    {license.status === 'active' ? (
                      <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <span className="w-1.5 h-1.5 rounded-full bg-green-600"></span>
                        Active
                      </span>
                    ) : (
                      <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                        <span className="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                        Inactive
                      </span>
                    )}
                  </td>
                  <td className="px-6 py-4 text-sm text-gray-600">{license.lastUsed}</td>
                  <td className="px-6 py-4 text-right space-x-2">
                    {license.status === 'active' && (
                      <button className="text-sm text-red-600 hover:text-red-700 font-medium">
                        Deactivate
                      </button>
                    )}
                    <button className="text-sm text-gray-600 hover:text-gray-700 font-medium">
                      Details
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* How to Use */}
      <div className="bg-blue-50 border border-blue-200 rounded-xl p-6">
        <div className="flex gap-4">
          <div className="flex-shrink-0">
            <svg className="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div className="flex-1">
            <h3 className="text-lg font-semibold text-gray-900 mb-2">How to Activate a License</h3>
            <ol className="space-y-2 text-sm text-gray-700">
              <li className="flex items-start gap-2">
                <span className="flex-shrink-0 w-5 h-5 rounded-full bg-blue-600 text-white text-xs flex items-center justify-center font-semibold mt-0.5">1</span>
                <span>Install the Kontent Fire plugin on your WordPress site</span>
              </li>
              <li className="flex items-start gap-2">
                <span className="flex-shrink-0 w-5 h-5 rounded-full bg-blue-600 text-white text-xs flex items-center justify-center font-semibold mt-0.5">2</span>
                <span>Copy your license key using the copy button above</span>
              </li>
              <li className="flex items-start gap-2">
                <span className="flex-shrink-0 w-5 h-5 rounded-full bg-blue-600 text-white text-xs flex items-center justify-center font-semibold mt-0.5">3</span>
                <span>Go to Kontent Fire → Settings in your WordPress admin</span>
              </li>
              <li className="flex items-start gap-2">
                <span className="flex-shrink-0 w-5 h-5 rounded-full bg-blue-600 text-white text-xs flex items-center justify-center font-semibold mt-0.5">4</span>
                <span>Paste the license key and click "Activate"</span>
              </li>
            </ol>
          </div>
        </div>
      </div>

      {/* New License Modal */}
      {showNewLicenseModal && (
        <div className="fixed inset-0 bg-gray-900/50 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-xl max-w-md w-full p-6">
            <div className="flex items-center justify-between mb-6">
              <h3 className="text-lg font-semibold text-gray-900">Generate New License</h3>
              <button
                onClick={() => setShowNewLicenseModal(false)}
                className="text-gray-400 hover:text-gray-600"
              >
                <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <p className="text-sm text-gray-600 mb-6">
              You have <strong>2 available license slots</strong> out of 5 on your Pro plan.
            </p>

            <div className="space-y-4">
              <button className="w-full py-3 px-4 bg-gradient-to-r from-brand-500 to-fire-500 text-white rounded-lg hover:opacity-90 transition font-medium">
                Generate License Key
              </button>
              <button
                onClick={() => setShowNewLicenseModal(false)}
                className="w-full py-3 px-4 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-medium"
              >
                Cancel
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
