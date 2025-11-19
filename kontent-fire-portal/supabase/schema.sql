-- Kontent Fire SaaS Portal Database Schema
-- Run this in Supabase SQL Editor

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- ============================================================================
-- PROFILES TABLE (extends Supabase auth.users)
-- ============================================================================
CREATE TABLE public.profiles (
    id UUID REFERENCES auth.users(id) ON DELETE CASCADE PRIMARY KEY,
    email TEXT UNIQUE NOT NULL,
    full_name TEXT,
    company_name TEXT,
    avatar_url TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc'::text, NOW()) NOT NULL,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc'::text, NOW()) NOT NULL
);

-- Enable Row Level Security
ALTER TABLE public.profiles ENABLE ROW LEVEL SECURITY;

-- Policies for profiles
CREATE POLICY "Users can view own profile" ON public.profiles
    FOR SELECT USING (auth.uid() = id);

CREATE POLICY "Users can update own profile" ON public.profiles
    FOR UPDATE USING (auth.uid() = id);

-- ============================================================================
-- SUBSCRIPTIONS TABLE
-- ============================================================================
CREATE TABLE public.subscriptions (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    user_id UUID REFERENCES public.profiles(id) ON DELETE CASCADE NOT NULL,
    stripe_customer_id TEXT UNIQUE,
    stripe_subscription_id TEXT UNIQUE,
    plan_type TEXT NOT NULL CHECK (plan_type IN ('basic', 'pro', 'enterprise')),
    status TEXT NOT NULL CHECK (status IN ('active', 'canceled', 'past_due', 'trialing', 'incomplete')),
    current_period_start TIMESTAMP WITH TIME ZONE,
    current_period_end TIMESTAMP WITH TIME ZONE,
    cancel_at_period_end BOOLEAN DEFAULT FALSE,
    canceled_at TIMESTAMP WITH TIME ZONE,
    trial_end TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc'::text, NOW()) NOT NULL,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc'::text, NOW()) NOT NULL
);

ALTER TABLE public.subscriptions ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Users can view own subscription" ON public.subscriptions
    FOR SELECT USING (auth.uid() = user_id);

-- Index for faster lookups
CREATE INDEX idx_subscriptions_user_id ON public.subscriptions(user_id);
CREATE INDEX idx_subscriptions_stripe_customer ON public.subscriptions(stripe_customer_id);
CREATE INDEX idx_subscriptions_status ON public.subscriptions(status);

-- ============================================================================
-- LICENSES TABLE
-- ============================================================================
CREATE TABLE public.licenses (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    user_id UUID REFERENCES public.profiles(id) ON DELETE CASCADE NOT NULL,
    license_key TEXT UNIQUE NOT NULL,
    plan_type TEXT NOT NULL CHECK (plan_type IN ('basic', 'pro', 'enterprise')),
    status TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'expired', 'suspended')),
    site_url TEXT,
    site_name TEXT,
    activations_used INTEGER DEFAULT 0,
    max_activations INTEGER NOT NULL DEFAULT 1,
    last_validated_at TIMESTAMP WITH TIME ZONE,
    expires_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc'::text, NOW()) NOT NULL,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc'::text, NOW()) NOT NULL
);

ALTER TABLE public.licenses ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Users can view own licenses" ON public.licenses
    FOR SELECT USING (auth.uid() = user_id);

-- Indexes
CREATE INDEX idx_licenses_user_id ON public.licenses(user_id);
CREATE INDEX idx_licenses_key ON public.licenses(license_key);
CREATE INDEX idx_licenses_status ON public.licenses(status);
CREATE UNIQUE INDEX idx_licenses_key_unique ON public.licenses(license_key);

-- ============================================================================
-- CREDITS TABLE (monthly credit allocation)
-- ============================================================================
CREATE TABLE public.credits (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    user_id UUID REFERENCES public.profiles(id) ON DELETE CASCADE NOT NULL,
    license_key TEXT REFERENCES public.licenses(license_key) ON DELETE CASCADE,
    total_credits INTEGER NOT NULL DEFAULT 0,
    used_credits INTEGER NOT NULL DEFAULT 0,
    remaining_credits INTEGER NOT NULL DEFAULT 0,
    plan_type TEXT NOT NULL CHECK (plan_type IN ('basic', 'pro', 'enterprise')),
    period_start TIMESTAMP WITH TIME ZONE NOT NULL,
    period_end TIMESTAMP WITH TIME ZONE NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc'::text, NOW()) NOT NULL,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc'::text, NOW()) NOT NULL
);

ALTER TABLE public.credits ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Users can view own credits" ON public.credits
    FOR SELECT USING (auth.uid() = user_id);

-- Indexes
CREATE INDEX idx_credits_user_id ON public.credits(user_id);
CREATE INDEX idx_credits_license_key ON public.credits(license_key);
CREATE INDEX idx_credits_period ON public.credits(period_start, period_end);

-- ============================================================================
-- CREDIT TRANSACTIONS TABLE (audit log)
-- ============================================================================
CREATE TABLE public.credit_transactions (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    user_id UUID REFERENCES public.profiles(id) ON DELETE CASCADE NOT NULL,
    license_key TEXT REFERENCES public.licenses(license_key) ON DELETE SET NULL,
    operation TEXT NOT NULL, -- e.g., 'blog_post', 'image_generation', 'video_script'
    credits_used INTEGER NOT NULL,
    credits_remaining INTEGER NOT NULL,
    metadata JSONB, -- Additional data about the operation
    created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc'::text, NOW()) NOT NULL
);

ALTER TABLE public.credit_transactions ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Users can view own transactions" ON public.credit_transactions
    FOR SELECT USING (auth.uid() = user_id);

-- Indexes
CREATE INDEX idx_credit_transactions_user_id ON public.credit_transactions(user_id);
CREATE INDEX idx_credit_transactions_license_key ON public.credit_transactions(license_key);
CREATE INDEX idx_credit_transactions_created_at ON public.credit_transactions(created_at DESC);

-- ============================================================================
-- API USAGE LOGS TABLE
-- ============================================================================
CREATE TABLE public.api_usage_logs (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    user_id UUID REFERENCES public.profiles(id) ON DELETE CASCADE NOT NULL,
    license_key TEXT REFERENCES public.licenses(license_key) ON DELETE SET NULL,
    api_provider TEXT NOT NULL, -- 'claude', 'openai', 'gemini', 'imagen', 'veo'
    endpoint TEXT NOT NULL,
    model TEXT,
    tokens_used INTEGER,
    api_cost DECIMAL(10, 6), -- Actual API cost in dollars
    credits_charged INTEGER NOT NULL,
    request_metadata JSONB, -- Request details
    response_metadata JSONB, -- Response details
    status TEXT NOT NULL CHECK (status IN ('success', 'error')),
    error_message TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc'::text, NOW()) NOT NULL
);

ALTER TABLE public.api_usage_logs ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Users can view own API usage" ON public.api_usage_logs
    FOR SELECT USING (auth.uid() = user_id);

-- Indexes
CREATE INDEX idx_api_usage_user_id ON public.api_usage_logs(user_id);
CREATE INDEX idx_api_usage_license_key ON public.api_usage_logs(license_key);
CREATE INDEX idx_api_usage_created_at ON public.api_usage_logs(created_at DESC);
CREATE INDEX idx_api_usage_provider ON public.api_usage_logs(api_provider);

-- ============================================================================
-- OAUTH STATES TABLE (for CSRF protection during OAuth flow)
-- ============================================================================
CREATE TABLE public.oauth_states (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    state TEXT UNIQUE NOT NULL,
    user_id UUID REFERENCES public.profiles(id) ON DELETE CASCADE NOT NULL,
    platform TEXT NOT NULL,
    license_key TEXT,
    expires_at TIMESTAMP WITH TIME ZONE NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc'::text, NOW()) NOT NULL
);

-- Index for fast state lookup
CREATE INDEX idx_oauth_states_state ON public.oauth_states(state);
CREATE INDEX idx_oauth_states_expires_at ON public.oauth_states(expires_at);

-- Auto-cleanup expired states (run periodically)
CREATE OR REPLACE FUNCTION cleanup_expired_oauth_states()
RETURNS void AS $$
BEGIN
    DELETE FROM public.oauth_states WHERE expires_at < NOW();
END;
$$ LANGUAGE plpgsql;

-- ============================================================================
-- OAUTH CONNECTIONS TABLE
-- ============================================================================
CREATE TABLE public.oauth_connections (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    user_id UUID REFERENCES public.profiles(id) ON DELETE CASCADE NOT NULL,
    license_key TEXT REFERENCES public.licenses(license_key) ON DELETE CASCADE,
    platform TEXT NOT NULL CHECK (platform IN ('facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'youtube')),
    platform_user_id TEXT NOT NULL,
    platform_username TEXT,
    account_name TEXT,
    account_type TEXT, -- 'business', 'personal', 'creator'
    access_token TEXT NOT NULL,
    refresh_token TEXT,
    token_expires_at TIMESTAMP WITH TIME ZONE,
    scopes TEXT[], -- Array of granted permissions
    platform_data JSONB, -- Platform-specific data (business pages, etc.)
    status TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'expired', 'disconnected')),
    last_synced_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc'::text, NOW()) NOT NULL,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc'::text, NOW()) NOT NULL,
    UNIQUE(user_id, platform, platform_user_id)
);

ALTER TABLE public.oauth_connections ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Users can view own OAuth connections" ON public.oauth_connections
    FOR SELECT USING (auth.uid() = user_id);

-- Indexes
CREATE INDEX idx_oauth_user_id ON public.oauth_connections(user_id);
CREATE INDEX idx_oauth_license_key ON public.oauth_connections(license_key);
CREATE INDEX idx_oauth_platform ON public.oauth_connections(platform);
CREATE INDEX idx_oauth_status ON public.oauth_connections(status);

-- ============================================================================
-- AUDIT LOGS TABLE (security & compliance)
-- ============================================================================
CREATE TABLE public.audit_logs (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    user_id UUID REFERENCES public.profiles(id) ON DELETE SET NULL,
    action TEXT NOT NULL,
    resource_type TEXT, -- 'license', 'subscription', 'oauth', 'credit'
    resource_id TEXT,
    details JSONB,
    ip_address INET,
    user_agent TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc'::text, NOW()) NOT NULL
);

-- Index
CREATE INDEX idx_audit_logs_user_id ON public.audit_logs(user_id);
CREATE INDEX idx_audit_logs_created_at ON public.audit_logs(created_at DESC);
CREATE INDEX idx_audit_logs_action ON public.audit_logs(action);

-- ============================================================================
-- FUNCTIONS
-- ============================================================================

-- Function to update updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = TIMEZONE('utc'::text, NOW());
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Triggers for updated_at
CREATE TRIGGER update_profiles_updated_at BEFORE UPDATE ON public.profiles
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_subscriptions_updated_at BEFORE UPDATE ON public.subscriptions
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_licenses_updated_at BEFORE UPDATE ON public.licenses
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_credits_updated_at BEFORE UPDATE ON public.credits
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_oauth_connections_updated_at BEFORE UPDATE ON public.oauth_connections
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Function to calculate remaining credits
CREATE OR REPLACE FUNCTION update_remaining_credits()
RETURNS TRIGGER AS $$
BEGIN
    NEW.remaining_credits = NEW.total_credits - NEW.used_credits;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER calculate_remaining_credits BEFORE INSERT OR UPDATE ON public.credits
    FOR EACH ROW EXECUTE FUNCTION update_remaining_credits();

-- Function to generate license key
CREATE OR REPLACE FUNCTION generate_license_key()
RETURNS TEXT AS $$
DECLARE
    key TEXT;
BEGIN
    key := 'KF-' ||
           UPPER(SUBSTRING(MD5(RANDOM()::TEXT) FROM 1 FOR 8)) || '-' ||
           UPPER(SUBSTRING(MD5(RANDOM()::TEXT) FROM 1 FOR 8)) || '-' ||
           UPPER(SUBSTRING(MD5(RANDOM()::TEXT) FROM 1 FOR 8));
    RETURN key;
END;
$$ LANGUAGE plpgsql;

-- ============================================================================
-- INITIAL DATA / SEED
-- ============================================================================

-- Credit costs for operations (can be adjusted)
CREATE TABLE public.operation_costs (
    operation TEXT PRIMARY KEY,
    credits INTEGER NOT NULL,
    description TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc'::text, NOW()) NOT NULL
);

INSERT INTO public.operation_costs (operation, credits, description) VALUES
    ('blog_post_short', 25, 'Short form blog post (800-1500 words)'),
    ('blog_post_long', 50, 'Long form blog post (1500-2500 words)'),
    ('auto_blog', 75, 'Full auto-blog with research and images'),
    ('image_generation', 10, 'Single image generation (Imagen 4)'),
    ('video_script', 20, 'Video script generation'),
    ('seo_analysis', 5, 'SEO content analysis'),
    ('social_post', 3, 'Single social media post'),
    ('keyword_research', 10, 'Keyword research with LSI');

-- ============================================================================
-- VIEWS (for analytics and reporting)
-- ============================================================================

-- User subscription status view
CREATE OR REPLACE VIEW user_subscription_status AS
SELECT
    p.id AS user_id,
    p.email,
    p.full_name,
    s.plan_type,
    s.status AS subscription_status,
    s.current_period_end,
    c.total_credits,
    c.used_credits,
    c.remaining_credits,
    COUNT(l.id) AS total_licenses
FROM public.profiles p
LEFT JOIN public.subscriptions s ON p.id = s.user_id
LEFT JOIN public.credits c ON p.id = c.user_id AND c.period_end > NOW()
LEFT JOIN public.licenses l ON p.id = l.user_id AND l.status = 'active'
GROUP BY p.id, p.email, p.full_name, s.plan_type, s.status, s.current_period_end, c.total_credits, c.used_credits, c.remaining_credits;

-- API usage summary view
CREATE OR REPLACE VIEW api_usage_summary AS
SELECT
    user_id,
    api_provider,
    DATE_TRUNC('day', created_at) AS usage_date,
    COUNT(*) AS request_count,
    SUM(tokens_used) AS total_tokens,
    SUM(credits_charged) AS total_credits,
    SUM(api_cost) AS total_cost
FROM public.api_usage_logs
WHERE status = 'success'
GROUP BY user_id, api_provider, DATE_TRUNC('day', created_at);

-- ============================================================================
-- COMMENTS
-- ============================================================================

COMMENT ON TABLE public.profiles IS 'User profile information extending Supabase auth.users';
COMMENT ON TABLE public.subscriptions IS 'Stripe subscription data synced via webhooks';
COMMENT ON TABLE public.licenses IS 'License keys for WordPress plugin activation';
COMMENT ON TABLE public.credits IS 'Monthly credit allocation per user/plan';
COMMENT ON TABLE public.credit_transactions IS 'Audit log of all credit usage';
COMMENT ON TABLE public.api_usage_logs IS 'Detailed logging of all API proxy requests';
COMMENT ON TABLE public.oauth_connections IS 'Social media OAuth connections';
COMMENT ON TABLE public.operation_costs IS 'Credit costs for different operations';
