import { NextRequest, NextResponse } from 'next/server';
import { createClient } from '@supabase/supabase-js';

const supabase = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL!,
  process.env.SUPABASE_SERVICE_ROLE_KEY!
);

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

    // Get the OAuth connection
    const { data: connection } = await supabase
      .from('oauth_connections')
      .select('*')
      .eq('user_id', license.user_id)
      .eq('platform', platform)
      .single();

    if (!connection) {
      return NextResponse.json(
        { error: 'Connection not found' },
        { status: 404 }
      );
    }

    if (!connection.refresh_token) {
      return NextResponse.json(
        { error: 'No refresh token available' },
        { status: 400 }
      );
    }

    // Refresh the access token
    const newTokens = await refreshAccessToken(platform, connection.refresh_token);

    if (!newTokens) {
      // Refresh failed - mark connection as expired
      await supabase
        .from('oauth_connections')
        .update({ status: 'expired' })
        .eq('user_id', license.user_id)
        .eq('platform', platform);

      return NextResponse.json(
        { error: 'Failed to refresh token', requires_reauth: true },
        { status: 401 }
      );
    }

    // Update connection with new tokens
    await supabase
      .from('oauth_connections')
      .update({
        access_token: newTokens.access_token,
        refresh_token: newTokens.refresh_token || connection.refresh_token,
        token_expires_at: newTokens.expires_at,
        status: 'active',
      })
      .eq('user_id', license.user_id)
      .eq('platform', platform);

    return NextResponse.json({
      success: true,
      access_token: newTokens.access_token,
      expires_at: newTokens.expires_at,
    });
  } catch (error) {
    console.error('OAuth refresh error:', error);
    return NextResponse.json(
      { error: 'Failed to refresh OAuth token' },
      { status: 500 }
    );
  }
}

async function refreshAccessToken(
  platform: string,
  refreshToken: string
): Promise<{ access_token: string; refresh_token?: string; expires_at?: string } | null> {
  const clientIdKey = `${platform.toUpperCase()}_CLIENT_ID`;
  const clientSecretKey = `${platform.toUpperCase()}_CLIENT_SECRET`;

  const clientId = process.env[clientIdKey];
  const clientSecret = process.env[clientSecretKey];

  if (!clientId || !clientSecret) {
    console.error(`Missing OAuth credentials for ${platform}`);
    return null;
  }

  const tokenUrls: Record<string, string> = {
    facebook: 'https://graph.facebook.com/v18.0/oauth/access_token',
    instagram: 'https://graph.facebook.com/v18.0/oauth/access_token', // Instagram uses Facebook Graph API
    twitter: 'https://api.twitter.com/2/oauth2/token',
    linkedin: 'https://www.linkedin.com/oauth/v2/accessToken',
    youtube: 'https://oauth2.googleapis.com/token',
    tiktok: 'https://open.tiktokapis.com/v2/oauth/token/',
  };

  const tokenUrl = tokenUrls[platform];
  if (!tokenUrl) return null;

  try {
    const params = new URLSearchParams({
      client_id: clientId,
      client_secret: clientSecret,
      refresh_token: refreshToken,
      grant_type: 'refresh_token',
    });

    // Facebook requires different parameters
    if (platform === 'facebook' || platform === 'instagram') {
      params.delete('refresh_token');
      params.set('grant_type', 'fb_exchange_token');
      params.set('fb_exchange_token', refreshToken);
    }

    const response = await fetch(tokenUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: params.toString(),
    });

    const data = await response.json();

    if (!response.ok || !data.access_token) {
      console.error('Token refresh failed:', data);
      return null;
    }

    return {
      access_token: data.access_token,
      refresh_token: data.refresh_token,
      expires_at: data.expires_in
        ? new Date(Date.now() + data.expires_in * 1000).toISOString()
        : undefined,
    };
  } catch (error) {
    console.error('Token refresh error:', error);
    return null;
  }
}
