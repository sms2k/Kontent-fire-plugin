# Kontent Fire Portal - Quick Setup Guide

## ✅ What's Been Created

### 📁 Project Structure
- ✅ Next.js 14 project configuration
- ✅ Package.json with all dependencies
- ✅ Environment variables template
- ✅ Complete database schema (Supabase)

### 🔌 API Endpoints (for WordPress Plugin)
- ✅ `POST /api/license/validate` - License validation
- ✅ `POST /api/license/activate` - License activation
- ✅ `GET /api/credits/balance` - Check credit balance
- ✅ `POST /api/credits/deduct` - Deduct credits

### 🗄️ Database Schema
- ✅ Users/Profiles table
- ✅ Subscriptions table (Stripe integration)
- ✅ Licenses table
- ✅ Credits table (monthly allocation)
- ✅ Credit transactions (audit log)
- ✅ API usage logs
- ✅ OAuth connections
- ✅ Audit logs
- ✅ Operation costs table

## 🚀 Next Steps to Deploy

### 1. Create Supabase Project

1. Go to [supabase.com](https://supabase.com)
2. Create new project
3. Copy the project URL and anon key
4. Go to SQL Editor
5. Run the entire `supabase/schema.sql` file
6. Go to Authentication > Providers and enable Email

### 2. Create Stripe Account

1. Go to [stripe.com](https://stripe.com)
2. Create account
3. Go to Products and create 3 subscription products:
   - **Basic**: $29/month (recurring)
   - **Pro**: $99/month (recurring)
   - **Enterprise**: $299/month (recurring)
4. Copy the Price IDs for each
5. Get your API keys from Developers > API keys

### 3. Set Up Environment Variables

Create `.env.local`:

```bash
# Supabase
NEXT_PUBLIC_SUPABASE_URL=https://xxxxx.supabase.co
NEXT_PUBLIC_SUPABASE_ANON_KEY=eyJxxx...
SUPABASE_SERVICE_ROLE_KEY=eyJxxx...

# Stripe
NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY=pk_test_xxx
STRIPE_SECRET_KEY=sk_test_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx

# Stripe Price IDs
STRIPE_PRICE_ID_BASIC=price_xxx
STRIPE_PRICE_ID_PRO=price_xxx
STRIPE_PRICE_ID_ENTERPRISE=price_xxx

# AI API Keys (for proxy)
ANTHROPIC_API_KEY=sk-ant-xxx
OPENAI_API_KEY=sk-xxx
GOOGLE_AI_API_KEY=xxx

# App URL
NEXT_PUBLIC_APP_URL=https://app.kontentfire.com
```

### 4. Install Dependencies

```bash
cd kontent-fire-portal
npm install
```

### 5. Run Development Server

```bash
npm run dev
```

Open [http://localhost:3000](http://localhost:3000)

### 6. Deploy to Vercel

```bash
# Install Vercel CLI
npm i -g vercel

# Deploy
vercel

# Add environment variables in Vercel dashboard
```

### 7. Set Up Stripe Webhooks

1. Go to Stripe Dashboard > Developers > Webhooks
2. Add endpoint: `https://app.kontentfire.com/api/webhooks/stripe`
3. Select events:
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
4. Copy webhook signing secret to `.env.local`

## 📋 Still Need to Build

### Frontend UI Components
- Login/Register pages
- User dashboard
- License management page
- Subscription/billing page
- Usage analytics

### Additional API Endpoints
- OAuth proxy (social media)
- AI API proxy (Claude, OpenAI, Gemini)
- Stripe webhook handler
- License generation endpoint

### WordPress Plugin Updates
- Update license server URL
- Implement credit checking before operations
- Add API proxy integration

## 🎨 Branding Needed

Please provide:
1. **Logo** (SVG preferred, PNG acceptable)
2. **Brand Colors**:
   - Primary color (hex)
   - Secondary color (hex)
   - Accent color (hex)
3. **Domain name** for portal (e.g., app.kontentfire.com)

Once you provide branding, I'll create the complete UI with your colors and logo.

## 🔧 Testing the API

### Test License Validation

```bash
curl -X POST http://localhost:3000/api/license/validate \
  -H "Content-Type: application/json" \
  -d '{"license_key": "TEST-LICENSE-KEY", "site_url": "https://mysite.com"}'
```

### Test Credit Balance

```bash
curl -X GET http://localhost:3000/api/credits/balance \
  -H "X-License-Key: TEST-LICENSE-KEY"
```

## 💡 Tips

- Use Supabase Dashboard to manually create test licenses
- Set up email templates in Supabase for password resets
- Configure CORS in Supabase settings if needed
- Monitor API logs in Vercel dashboard
- Use Stripe test mode for development

## 📞 Questions?

Review the README.md for full documentation.
