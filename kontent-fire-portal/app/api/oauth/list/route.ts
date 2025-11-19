import { NextRequest, NextResponse } from 'next/server';
import { createClient } from '@supabase/supabase-js';

const supabase = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL!,
  process.env.SUPABASE_SERVICE_ROLE_KEY!
);

export async function GET(request: NextRequest) {
  try {
    const licenseKey = request.headers.get('X-License-Key');

    if (!licenseKey) {
      return NextResponse.json(
        { error: 'Missing license key' },
        { status: 401 }
      );
    }

    // Validate license
    const { data: license } = await supabase
      .from('licenses')
      .select('user_id, status')
      .eq('license_key', licenseKey)
      .single();

    if (!license || license.status !== 'active') {
      return NextResponse.json(
        { error: 'Invalid or inactive license' },
        { status: 403 }
      );
    }

    // Get all OAuth connections for this user
    const { data: connections, error } = await supabase
      .from('oauth_connections')
      .select('platform, platform_username, account_name, account_type, status, platform_data, created_at')
      .eq('user_id', license.user_id)
      .eq('status', 'active');

    if (error) {
      console.error('Error fetching OAuth connections:', error);
      return NextResponse.json(
        { error: 'Failed to fetch connections' },
        { status: 500 }
      );
    }

    // Format response for WordPress plugin
    const formattedConnections = connections?.map(conn => ({
      platform: conn.platform,
      username: conn.platform_username,
      account_name: conn.account_name,
      account_type: conn.account_type,
      connected_at: conn.created_at,
      // Include business pages for Facebook/Instagram
      pages: conn.platform_data?.accounts || conn.platform_data?.pages || [],
    })) || [];

    return NextResponse.json({
      success: true,
      connections: formattedConnections,
    });
  } catch (error) {
    console.error('OAuth list error:', error);
    return NextResponse.json(
      { error: 'Failed to list OAuth connections' },
      { status: 500 }
    );
  }
}
