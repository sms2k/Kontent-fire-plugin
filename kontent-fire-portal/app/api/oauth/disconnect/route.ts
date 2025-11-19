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

    // Attempt to revoke token with platform
    await revokeToken(platform, connection.access_token);

    // Update connection status to disconnected
    await supabase
      .from('oauth_connections')
      .update({
        status: 'disconnected',
        access_token: null,
        refresh_token: null,
      })
      .eq('user_id', license.user_id)
      .eq('platform', platform);

    // Log disconnection
    await supabase.from('audit_logs').insert({
      user_id: license.user_id,
      action: 'oauth_disconnected',
      resource_type: 'oauth_connection',
      resource_id: platform,
      details: {
        platform,
        platform_username: connection.platform_username,
      },
    });

    return NextResponse.json({
      success: true,
      message: `Successfully disconnected from ${platform}`,
    });
  } catch (error) {
    console.error('OAuth disconnect error:', error);
    return NextResponse.json(
      { error: 'Failed to disconnect OAuth' },
      { status: 500 }
    );
  }
}

async function revokeToken(platform: string, accessToken: string): Promise<void> {
  const revokeUrls: Record<string, string> = {
    facebook: `https://graph.facebook.com/me/permissions?access_token=${accessToken}`,
    google: 'https://oauth2.googleapis.com/revoke',
    linkedin: 'https://www.linkedin.com/oauth/v2/revoke',
  };

  try {
    const revokeUrl = revokeUrls[platform === 'youtube' ? 'google' : platform];

    if (!revokeUrl) {
      // Platform doesn't support token revocation or not configured
      return;
    }

    if (platform === 'facebook') {
      // Facebook uses DELETE method
      await fetch(revokeUrl, { method: 'DELETE' });
    } else {
      // Most platforms use POST
      await fetch(revokeUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
          token: accessToken,
        }),
      });
    }
  } catch (error) {
    console.error(`Failed to revoke token for ${platform}:`, error);
    // Don't throw - we still want to disconnect in our database
  }
}
