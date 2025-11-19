# 🚀 Kontent Fire Portal - Deployment Guide

## Overview

This guide walks you through deploying the Kontent Fire SaaS portal and integrating it with the WordPress plugin.

## Prerequisites

- Supabase account
- Stripe account
- Vercel account (or alternative hosting)
- Domain name (e.g., app.kontentfire.com)
- AI API keys (Anthropic Claude, OpenAI, Google Gemini)
- OAuth credentials for social platforms

---

## Part 1: Supabase Setup

### 1.1 Create Supabase Project

1. Go to [https://supabase.com](https://supabase.com)
2. Click "New Project"
3. Name: `kontent-fire-portal`
4. Database password: Generate a strong password (save it securely)
5. Region: Choose closest to your users
6. Click "Create new project"

### 1.2 Run Database Schema

1. In Supabase dashboard, go to **SQL Editor**
2. Copy the contents of `/supabase/schema.sql`
3. Paste into SQL Editor
4. Click "Run" to execute
5. Verify all tables are created:
   - profiles
   - subscriptions
   - licenses
   - credits
   - credit_transactions
   - api_usage_logs
   - oauth_states
   - oauth_connections
   - audit_logs
   - operation_costs

### 1.3 Configure Authentication

1. Go to **Authentication** → **Settings**
2. Enable Email provider
3. Configure email templates:
   - Customize "Confirm signup" email
   - Customize "Magic Link" email
   - Customize "Reset Password" email
4. Set Site URL: `https://app.kontentfire.com`
5. Add Redirect URLs:
   - `https://app.kontentfire.com/api/oauth/callback`
   - `http://localhost:3000` (for development)

### 1.4 Get API Keys

1. Go to **Settings** → **API**
2. Copy these values:
   - Project URL (e.g., `https://xxx.supabase.co`)
   - `anon` public key
   - `service_role` secret key (⚠️ Never expose in frontend)

---

## Part 2: Stripe Setup

### 2.1 Create Stripe Account

1. Go to [https://stripe.com](https://stripe.com)
2. Sign up and complete account verification
3. Switch to **Test mode** for initial setup

### 2.2 Create Products and Prices

Create three products:

#### Basic Plan ($29/month)
```
Name: Kontent Fire Basic
Description: 1,000 credits per month
Price: $29.00 USD / month
```

#### Pro Plan ($99/month)
```
Name: Kontent Fire Pro
Description: 5,000 credits per month
Price: $99.00 USD / month
```

#### Enterprise Plan ($299/month)
```
Name: Kontent Fire Enterprise
Description: 20,000 credits per month
Price: $299.00 USD / month
```

### 2.3 Get Stripe Keys

1. Go to **Developers** → **API keys**
2. Copy:
   - Publishable key
   - Secret key
3. Copy the Price IDs for each plan

### 2.4 Configure Webhooks

1. Go to **Developers** → **Webhooks**
2. Click "+ Add endpoint"
3. Endpoint URL: `https://app.kontentfire.com/api/webhooks/stripe`
4. Events to send:
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
5. Copy the **Webhook signing secret**

---

## Part 3: OAuth Platform Setup

### 3.1 Facebook/Meta

1. Go to [Facebook Developers](https://developers.facebook.com)
2. Create new app → Type: Business
3. Add **Facebook Login** product
4. Settings → Basic:
   - Copy App ID and App Secret
5. Facebook Login → Settings:
   - Valid OAuth Redirect URIs: `https://app.kontentfire.com/api/oauth/callback`
6. Add permissions: `pages_manage_posts`, `pages_read_engagement`, `pages_show_list`

### 3.2 Twitter/X

1. Go to [Twitter Developer Portal](https://developer.twitter.com)
2. Create new project and app
3. App settings → OAuth 2.0:
   - Type of App: Web App
   - Callback URL: `https://app.kontentfire.com/api/oauth/callback`
4. Copy Client ID and Client Secret

### 3.3 LinkedIn

1. Go to [LinkedIn Developers](https://www.linkedin.com/developers/)
2. Create new app
3. Auth → OAuth 2.0 settings:
   - Redirect URLs: `https://app.kontentfire.com/api/oauth/callback`
4. Products → Request access to "Sign In with LinkedIn using OpenID Connect"
5. Copy Client ID and Client Secret

### 3.4 YouTube (Google)

1. Go to [Google Cloud Console](https://console.cloud.google.com)
2. Create new project: "Kontent Fire"
3. Enable APIs:
   - YouTube Data API v3
   - Google Gemini API
   - Vertex AI API (for Imagen 4 and Veo 3)
4. Create OAuth 2.0 credentials:
   - Authorized redirect URIs: `https://app.kontentfire.com/api/oauth/callback`
5. Copy Client ID and Client Secret

### 3.5 TikTok

1. Go to [TikTok Developers](https://developers.tiktok.com)
2. Create new app
3. Add "Login Kit" and "Content Posting API"
4. Set Redirect URI: `https://app.kontentfire.com/api/oauth/callback`
5. Copy Client Key and Client Secret

---

## Part 4: Environment Variables

### 4.1 Create .env.local File

In the portal directory (`/home/user/kontent-fire-portal/`), create `.env.local`:

```env
# App
NEXT_PUBLIC_APP_URL=https://app.kontentfire.com

# Supabase
NEXT_PUBLIC_SUPABASE_URL=https://xxx.supabase.co
NEXT_PUBLIC_SUPABASE_ANON_KEY=your-anon-key
SUPABASE_SERVICE_ROLE_KEY=your-service-role-key

# Stripe
NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY=pk_test_xxx
STRIPE_SECRET_KEY=sk_test_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx
STRIPE_PRICE_ID_BASIC=price_xxx
STRIPE_PRICE_ID_PRO=price_xxx
STRIPE_PRICE_ID_ENTERPRISE=price_xxx

# AI APIs
ANTHROPIC_API_KEY=sk-ant-xxx
OPENAI_API_KEY=sk-xxx
GOOGLE_AI_API_KEY=AIzaXxx
GOOGLE_CLOUD_PROJECT_ID=kontent-fire-xxx
GOOGLE_APPLICATION_CREDENTIALS=./google-credentials.json

# OAuth - Facebook
FACEBOOK_CLIENT_ID=xxx
FACEBOOK_CLIENT_SECRET=xxx

# OAuth - Instagram (uses Facebook)
INSTAGRAM_CLIENT_ID=xxx
INSTAGRAM_CLIENT_SECRET=xxx

# OAuth - Twitter
TWITTER_CLIENT_ID=xxx
TWITTER_CLIENT_SECRET=xxx

# OAuth - LinkedIn
LINKEDIN_CLIENT_ID=xxx
LINKEDIN_CLIENT_SECRET=xxx

# OAuth - YouTube (uses Google)
YOUTUBE_CLIENT_ID=xxx
YOUTUBE_CLIENT_SECRET=xxx

# OAuth - TikTok
TIKTOK_CLIENT_ID=xxx
TIKTOK_CLIENT_SECRET=xxx
```

---

## Part 5: Deploy to Vercel

### 5.1 Install Dependencies

```bash
cd /home/user/kontent-fire-portal
npm install
```

### 5.2 Test Locally

```bash
npm run dev
```

Visit `http://localhost:3000` and verify:
- Login/register pages load
- Dashboard shows (may need to create a test user in Supabase Auth)

### 5.3 Deploy to Vercel

```bash
# Install Vercel CLI
npm install -g vercel

# Login
vercel login

# Deploy
vercel

# When prompted:
# - Link to existing project? N
# - Project name: kontent-fire-portal
# - Directory: ./
# - Override settings? N

# After deployment, copy the production URL
```

### 5.4 Configure Vercel Environment Variables

1. Go to Vercel dashboard → Your project
2. Settings → Environment Variables
3. Add all variables from `.env.local`
4. Click "Redeploy" to apply

### 5.5 Set Custom Domain

1. Vercel dashboard → Domains
2. Add domain: `app.kontentfire.com`
3. Follow DNS configuration instructions
4. Wait for SSL certificate to be issued

---

## Part 6: Update Stripe Webhook URL

1. Go back to Stripe → Developers → Webhooks
2. Update endpoint URL to production: `https://app.kontentfire.com/api/webhooks/stripe`
3. Copy new webhook signing secret
4. Update `STRIPE_WEBHOOK_SECRET` in Vercel environment variables
5. Redeploy

---

## Part 7: WordPress Plugin Integration

### 7.1 Update Plugin URLs

The WordPress plugin has already been updated to use the portal URLs:
- License validation: `https://app.kontentfire.com/api/license/validate`
- License activation: `https://app.kontentfire.com/api/license/activate`
- Credits balance: `https://app.kontentfire.com/api/credits/balance`
- Credits deduction: `https://app.kontentfire.com/api/credits/deduct`
- OAuth list: `https://app.kontentfire.com/api/oauth/list`
- Claude proxy: `https://app.kontentfire.com/api/proxy/claude`
- OpenAI proxy: `https://app.kontentfire.com/api/proxy/openai`
- Gemini proxy: `https://app.kontentfire.com/api/proxy/gemini`

### 7.2 Test License Activation

1. Create a test user in portal (register at `https://app.kontentfire.com/register`)
2. In WordPress admin, go to Kontent Fire → Settings → License
3. Enter license key from portal dashboard
4. Click "Activate License"
5. Verify activation success and credit display

---

## Part 8: Create Test Data

### 8.1 Create Test User

1. Register at `https://app.kontentfire.com/register`
2. Verify email (check Supabase Auth if email not sent)

### 8.2 Manually Create Subscription (for testing)

Run this in Supabase SQL Editor:

```sql
-- Get user ID
SELECT id FROM auth.users WHERE email = 'your-test-email@example.com';

-- Create subscription
INSERT INTO public.subscriptions (user_id, plan_type, status, current_period_start, current_period_end)
VALUES (
  'user-id-from-above',
  'pro',
  'active',
  NOW(),
  NOW() + INTERVAL '1 month'
);

-- Create license
INSERT INTO public.licenses (user_id, license_key, plan_type, status, max_activations)
VALUES (
  'user-id-from-above',
  'KF-TEST-' || UPPER(SUBSTRING(MD5(RANDOM()::TEXT) FROM 1 FOR 16)),
  'pro',
  'active',
  1
);

-- Get the license key
SELECT license_key FROM public.licenses WHERE user_id = 'user-id-from-above';

-- Create credits
INSERT INTO public.credits (user_id, license_key, total_credits, used_credits, remaining_credits, plan_type, period_start, period_end)
VALUES (
  'user-id-from-above',
  'license-key-from-above',
  5000,
  0,
  5000,
  'pro',
  NOW(),
  NOW() + INTERVAL '1 month'
);
```

---

## Part 9: Testing Checklist

### Portal Tests

- [ ] User registration works
- [ ] Email verification works
- [ ] Login works
- [ ] Dashboard displays correctly
- [ ] License keys are visible
- [ ] License key can be copied
- [ ] Billing page shows plans
- [ ] Usage page shows analytics

### WordPress Plugin Tests

- [ ] Plugin activates without errors
- [ ] License activation works
- [ ] Credit balance displays correctly
- [ ] Content generation deducts credits
- [ ] Credit balance updates in dashboard
- [ ] Image generation works and deducts credits
- [ ] Social media posting works

### API Proxy Tests

- [ ] Claude API calls route through portal
- [ ] OpenAI API calls route through portal
- [ ] Gemini API calls route through portal
- [ ] Credits are deducted for API calls
- [ ] API usage is logged in database

### OAuth Tests

- [ ] Facebook OAuth flow works
- [ ] Instagram OAuth flow works
- [ ] Twitter OAuth flow works
- [ ] LinkedIn OAuth flow works
- [ ] YouTube OAuth flow works
- [ ] TikTok OAuth flow works
- [ ] Connections display in WordPress plugin

### Payment Tests (Use Stripe Test Mode)

- [ ] Subscription signup works (test card: 4242 4242 4242 4242)
- [ ] Webhook receives subscription.created event
- [ ] License is automatically created
- [ ] Credits are allocated
- [ ] Payment success shows in dashboard
- [ ] Subscription upgrade/downgrade works
- [ ] Subscription cancellation works

---

## Part 10: Go Live

### 10.1 Switch Stripe to Live Mode

1. Complete Stripe account verification
2. In Stripe dashboard, toggle to **Live mode**
3. Recreate products in live mode
4. Get live API keys
5. Update Vercel environment variables with live keys
6. Update webhook endpoint

### 10.2 Monitor

Set up monitoring for:
- Vercel deployment logs
- Supabase database usage
- Stripe dashboard for payments
- API error rates in Supabase logs

### 10.3 Backup

- Enable Supabase automated backups (Settings → Database → Backups)
- Export environment variables to secure location
- Document any custom configurations

---

## Troubleshooting

### License Validation Failing

- Check Supabase service role key is correct
- Verify license exists in database
- Check license status is 'active'
- Check CORS settings in `next.config.js`

### Credits Not Deducting

- Verify credit endpoint URL is correct
- Check license key is being sent in header
- Check Supabase permissions on credits table
- Check for errors in Vercel function logs

### API Proxy Not Working

- Verify AI API keys are set in environment variables
- Check proxy endpoint URLs in WordPress plugin
- Verify license key is valid
- Check function timeout settings (may need to increase)

### OAuth Not Connecting

- Verify redirect URLs match exactly in platform settings
- Check OAuth client IDs and secrets
- Verify scopes/permissions are granted
- Check oauth_states table for expired entries

### Stripe Webhooks Not Received

- Check webhook endpoint URL is correct
- Verify webhook signing secret matches
- Check Stripe webhook logs for delivery attempts
- Ensure endpoint is publicly accessible

---

## Support

For issues, check:
- Vercel deployment logs
- Supabase logs (Dashboard → Logs)
- Stripe webhook logs
- WordPress debug.log

## Next Steps

1. Set up email marketing for user onboarding
2. Create help documentation
3. Set up customer support system
4. Implement analytics tracking
5. Plan feature roadmap

---

**🔥 Kontent Fire is ready to launch!**
