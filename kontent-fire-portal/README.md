# Kontent Fire SaaS Portal

Customer portal for Kontent Fire WordPress plugin subscription management.

## 🎯 Features

- **User Authentication** - Secure signup/login with Supabase Auth
- **Subscription Management** - Stripe-powered billing (Basic $29, Pro $99, Enterprise $299)
- **License Key Management** - Generate, activate, and deactivate licenses
- **Credit Tracking** - Monitor API usage and credit consumption
- **OAuth Proxy** - Secure social media platform connections
- **API Proxy** - Route AI API calls (Claude, OpenAI, Gemini) with credit tracking
- **Usage Analytics** - Detailed reporting and insights
- **Multi-site Management** - Manage multiple WordPress installations

## 🏗️ Tech Stack

- **Frontend**: Next.js 14 (App Router)
- **Backend**: Supabase (PostgreSQL + Edge Functions)
- **Payments**: Stripe
- **Deployment**: Vercel
- **Authentication**: Supabase Auth
- **Database**: PostgreSQL (via Supabase)

## 📁 Project Structure

```
kontent-fire-portal/
├── app/                    # Next.js App Router
│   ├── (auth)/            # Auth routes (login, register)
│   ├── (dashboard)/       # Protected dashboard routes
│   ├── api/               # API routes for WordPress plugin
│   └── layout.tsx         # Root layout
├── components/            # React components
│   ├── ui/               # Shadcn UI components
│   ├── dashboard/        # Dashboard-specific components
│   └── auth/             # Auth-related components
├── lib/                   # Utility functions
│   ├── supabase/         # Supabase client & queries
│   ├── stripe/           # Stripe integration
│   └── utils.ts          # General utilities
├── supabase/             # Supabase configuration
│   ├── migrations/       # Database migrations
│   └── functions/        # Edge functions
└── public/               # Static assets
```

## 🚀 Getting Started

### Prerequisites

- Node.js 18+
- Supabase account
- Stripe account
- Vercel account (for deployment)

### Environment Variables

Create a `.env.local` file:

```env
# Supabase
NEXT_PUBLIC_SUPABASE_URL=your_supabase_url
NEXT_PUBLIC_SUPABASE_ANON_KEY=your_supabase_anon_key
SUPABASE_SERVICE_ROLE_KEY=your_service_role_key

# Stripe
NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY=your_stripe_publishable_key
STRIPE_SECRET_KEY=your_stripe_secret_key
STRIPE_WEBHOOK_SECRET=your_stripe_webhook_secret

# Plans
STRIPE_PRICE_ID_BASIC=price_xxx
STRIPE_PRICE_ID_PRO=price_xxx
STRIPE_PRICE_ID_ENTERPRISE=price_xxx

# API Keys (for proxy)
ANTHROPIC_API_KEY=your_claude_api_key
OPENAI_API_KEY=your_openai_api_key
GOOGLE_AI_API_KEY=your_gemini_api_key

# OAuth (for proxy)
FACEBOOK_APP_ID=your_facebook_app_id
FACEBOOK_APP_SECRET=your_facebook_app_secret
# ... other OAuth credentials

# App
NEXT_PUBLIC_APP_URL=https://app.kontentfire.com
```

### Installation

```bash
npm install
```

### Database Setup

```bash
# Initialize Supabase locally (optional)
npx supabase init

# Run migrations
npx supabase db push

# Or apply schema.sql directly in Supabase dashboard
```

### Development

```bash
npm run dev
```

Open [http://localhost:3000](http://localhost:3000)

### Deployment

```bash
# Deploy to Vercel
vercel

# Set environment variables in Vercel dashboard
```

## 🔐 API Endpoints for WordPress Plugin

### License Management

- `POST /api/license/validate` - Validate license key
- `POST /api/license/activate` - Activate license for a site
- `POST /api/license/deactivate` - Deactivate license

### Credit Management

- `GET /api/credits/balance` - Get current credit balance
- `POST /api/credits/deduct` - Deduct credits for operation

### OAuth Proxy

- `POST /api/oauth/initiate` - Start OAuth flow
- `POST /api/oauth/exchange` - Exchange code for token
- `POST /api/oauth/disconnect` - Disconnect platform
- `POST /api/oauth/refresh` - Refresh expired token

### API Proxy (AI Services)

- `POST /api/proxy/claude` - Proxy to Claude API
- `POST /api/proxy/openai` - Proxy to OpenAI API
- `POST /api/proxy/gemini` - Proxy to Gemini API

All endpoints require valid license key in request headers.

## 💳 Subscription Plans

| Plan | Price | Credits | Features |
|------|-------|---------|----------|
| **Basic** | $29/mo | 1,000 | 1 site, Basic support |
| **Pro** | $99/mo | 5,000 | 5 sites, Priority support, Advanced features |
| **Enterprise** | $299/mo | 20,000 | Unlimited sites, White label, API access |

## 🔒 Security

- All API keys stored server-side (never in WordPress)
- License validation on every request
- Rate limiting on API endpoints
- HTTPS required for all connections
- Row-level security in Supabase
- Stripe webhooks for subscription updates

## 📊 Credit Pricing

| Operation | Credits | API Cost | Markup |
|-----------|---------|----------|---------|
| Blog Post (Claude) | 25 | ~$0.50 | 500% |
| Image (Imagen 4) | 10 | ~$0.20 | 500% |
| Video Script | 20 | ~$0.40 | 500% |
| SEO Analysis | 5 | ~$0.10 | 500% |

## 🛠️ Development

### Code Style

- ESLint + Prettier
- TypeScript strict mode
- Conventional commits

### Testing

```bash
npm run test
```

### Build

```bash
npm run build
```

## 📝 License

Proprietary - All rights reserved

## 🤝 Support

For support, email support@kontentfire.com
