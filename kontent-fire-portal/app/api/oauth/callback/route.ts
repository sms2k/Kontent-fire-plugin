import { NextRequest, NextResponse } from 'next/server';
import { createClient } from '@supabase/supabase-js';

const supabase = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL!,
  process.env.SUPABASE_SERVICE_ROLE_KEY!
);

export async function GET(request: NextRequest) {
  try {
    const searchParams = request.nextUrl.searchParams;
    const code = searchParams.get('code');
    const state = searchParams.get('state');
    const error = searchParams.get('error');

    if (error) {
      return NextResponse.redirect(
        `${process.env.NEXT_PUBLIC_APP_URL}/dashboard/settings?oauth_error=${error}`
      );
    }

    if (!code || !state) {
      return NextResponse.redirect(
        `${process.env.NEXT_PUBLIC_APP_URL}/dashboard/settings?oauth_error=missing_params`
      );
    }

    // Validate state token
    const { data: oauthState } = await supabase
      .from('oauth_states')
      .select('*')
      .eq('state', state)
      .single();

    if (!oauthState || new Date(oauthState.expires_at) < new Date()) {
      return NextResponse.redirect(
        `${process.env.NEXT_PUBLIC_APP_URL}/dashboard/settings?oauth_error=invalid_state`
      );
    }

    // Exchange code for access token
    const tokenData = await exchangeCodeForToken(
      oauthState.platform,
      code,
      `${process.env.NEXT_PUBLIC_APP_URL}/api/oauth/callback`
    );

    if (!tokenData) {
      return NextResponse.redirect(
        `${process.env.NEXT_PUBLIC_APP_URL}/dashboard/settings?oauth_error=token_exchange_failed`
      );
    }

    // Get platform-specific user info
    const platformInfo = await getPlatformInfo(oauthState.platform, tokenData.access_token);

    // Store OAuth connection
    await supabase.from('oauth_connections').upsert({
      user_id: oauthState.user_id,
      platform: oauthState.platform,
      platform_user_id: platformInfo.id,
      platform_username: platformInfo.username,
      access_token: tokenData.access_token,
      refresh_token: tokenData.refresh_token,
      token_expires_at: tokenData.expires_at,
      platform_data: platformInfo.data,
      status: 'active',
    });

    // Delete used state
    await supabase.from('oauth_states').delete().eq('state', state);

    // Log successful connection
    await supabase.from('audit_logs').insert({
      user_id: oauthState.user_id,
      action: 'oauth_connected',
      resource_type: 'oauth_connection',
      resource_id: oauthState.platform,
      details: {
        platform: oauthState.platform,
        platform_username: platformInfo.username,
      },
    });

    return NextResponse.redirect(
      `${process.env.NEXT_PUBLIC_APP_URL}/dashboard/settings?oauth_success=${oauthState.platform}`
    );
  } catch (error) {
    console.error('OAuth callback error:', error);
    return NextResponse.redirect(
      `${process.env.NEXT_PUBLIC_APP_URL}/dashboard/settings?oauth_error=unknown`
    );
  }
}

async function exchangeCodeForToken(
  platform: string,
  code: string,
  redirectUri: string
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
    instagram: 'https://api.instagram.com/oauth/access_token',
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
      code,
      redirect_uri: redirectUri,
      grant_type: 'authorization_code',
    });

    const response = await fetch(tokenUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: params.toString(),
    });

    const data = await response.json();

    if (!response.ok || !data.access_token) {
      console.error('Token exchange failed:', data);
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
    console.error('Token exchange error:', error);
    return null;
  }
}

async function getPlatformInfo(
  platform: string,
  accessToken: string
): Promise<{ id: string; username: string; data: any }> {
  const apiUrls: Record<string, string> = {
    facebook: 'https://graph.facebook.com/me?fields=id,name,accounts{id,name,category,access_token}',
    instagram: 'https://graph.instagram.com/me?fields=id,username',
    twitter: 'https://api.twitter.com/2/users/me',
    linkedin: 'https://api.linkedin.com/v2/userinfo',
    youtube: 'https://www.googleapis.com/youtube/v3/channels?part=snippet&mine=true',
    tiktok: 'https://open.tiktokapis.com/v2/user/info/',
  };

  try {
    const response = await fetch(apiUrls[platform], {
      headers: {
        Authorization: `Bearer ${accessToken}`,
      },
    });

    const data = await response.json();

    // Extract ID and username based on platform
    let id: string;
    let username: string;

    switch (platform) {
      case 'facebook':
        id = data.id;
        username = data.name;
        break;
      case 'instagram':
        id = data.id;
        username = data.username;
        break;
      case 'twitter':
        id = data.data.id;
        username = data.data.username;
        break;
      case 'linkedin':
        id = data.sub;
        username = data.name;
        break;
      case 'youtube':
        id = data.items[0].id;
        username = data.items[0].snippet.title;
        break;
      case 'tiktok':
        id = data.data.user.open_id;
        username = data.data.user.display_name;
        break;
      default:
        id = data.id || 'unknown';
        username = data.username || data.name || 'unknown';
    }

    return { id, username, data };
  } catch (error) {
    console.error('Failed to get platform info:', error);
    return { id: 'unknown', username: 'unknown', data: {} };
  }
}
