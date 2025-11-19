import { NextRequest, NextResponse } from 'next/server';
import { createClient } from '@supabase/supabase-js';
import crypto from 'crypto';

const supabase = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL!,
  process.env.SUPABASE_SERVICE_ROLE_KEY!
);

// OAuth configuration for each platform
const OAUTH_CONFIGS = {
  facebook: {
    authUrl: 'https://www.facebook.com/v18.0/dialog/oauth',
    scope: 'pages_manage_posts,pages_read_engagement,pages_show_list,instagram_basic,instagram_content_publish',
    responseType: 'code',
  },
  instagram: {
    authUrl: 'https://api.instagram.com/oauth/authorize',
    scope: 'user_profile,user_media,instagram_business_basic,instagram_content_publish',
    responseType: 'code',
  },
  twitter: {
    authUrl: 'https://twitter.com/i/oauth2/authorize',
    scope: 'tweet.read tweet.write users.read offline.access',
    responseType: 'code',
  },
  linkedin: {
    authUrl: 'https://www.linkedin.com/oauth/v2/authorization',
    scope: 'w_member_social r_organization_social rw_organization_admin',
    responseType: 'code',
  },
  youtube: {
    authUrl: 'https://accounts.google.com/o/oauth2/v2/auth',
    scope: 'https://www.googleapis.com/auth/youtube.upload https://www.googleapis.com/auth/youtube.readonly',
    responseType: 'code',
  },
  tiktok: {
    authUrl: 'https://www.tiktok.com/v2/auth/authorize',
    scope: 'user.info.basic,video.publish',
    responseType: 'code',
  },
};

export async function POST(request: NextRequest) {
  try {
    const { platform, license_key } = await request.json();

    if (!platform || !license_key) {
      return NextResponse.json(
        { error: 'Missing platform or license_key' },
        { status: 400 }
      );
    }

    // Validate license
    const { data: license } = await supabase
      .from('licenses')
      .select('user_id, status')
      .eq('license_key', license_key)
      .single();

    if (!license || license.status !== 'active') {
      return NextResponse.json(
        { error: 'Invalid or inactive license' },
        { status: 403 }
      );
    }

    const config = OAUTH_CONFIGS[platform as keyof typeof OAUTH_CONFIGS];
    if (!config) {
      return NextResponse.json(
        { error: 'Unsupported platform' },
        { status: 400 }
      );
    }

    // Generate state token for CSRF protection
    const state = crypto.randomBytes(32).toString('hex');

    // Store state in database for validation
    await supabase.from('oauth_states').insert({
      state,
      user_id: license.user_id,
      platform,
      license_key,
      expires_at: new Date(Date.now() + 10 * 60 * 1000).toISOString(), // 10 minutes
    });

    // Get client ID from environment
    const clientIdKey = `${platform.toUpperCase()}_CLIENT_ID`;
    const clientId = process.env[clientIdKey];

    if (!clientId) {
      return NextResponse.json(
        { error: 'OAuth not configured for this platform' },
        { status: 500 }
      );
    }

    // Build OAuth URL
    const redirectUri = `${process.env.NEXT_PUBLIC_APP_URL}/api/oauth/callback`;
    const params = new URLSearchParams({
      client_id: clientId,
      redirect_uri: redirectUri,
      response_type: config.responseType,
      scope: config.scope,
      state,
    });

    // Add platform-specific parameters
    if (platform === 'twitter') {
      params.append('code_challenge', 'challenge');
      params.append('code_challenge_method', 'plain');
    }

    const authUrl = `${config.authUrl}?${params.toString()}`;

    return NextResponse.json({
      success: true,
      auth_url: authUrl,
      state,
    });
  } catch (error) {
    console.error('OAuth initiate error:', error);
    return NextResponse.json(
      { error: 'Failed to initiate OAuth flow' },
      { status: 500 }
    );
  }
}
