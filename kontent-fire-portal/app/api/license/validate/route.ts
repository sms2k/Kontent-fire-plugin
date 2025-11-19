import { NextRequest, NextResponse } from 'next/server';
import { createClient } from '@supabase/supabase-js';

const supabase = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL!,
  process.env.SUPABASE_SERVICE_ROLE_KEY!
);

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { license_key, site_url } = body;

    if (!license_key) {
      return NextResponse.json(
        { valid: false, message: 'License key is required.' },
        { status: 400 }
      );
    }

    // Fetch license from database
    const { data: license, error } = await supabase
      .from('licenses')
      .select(`
        *,
        profiles!inner(id, email),
        subscriptions!inner(status, plan_type, current_period_end)
      `)
      .eq('license_key', license_key)
      .single();

    if (error || !license) {
      return NextResponse.json(
        { valid: false, message: 'Invalid license key.' },
        { status: 404 }
      );
    }

    // Check if license is active
    if (license.status !== 'active') {
      return NextResponse.json(
        { valid: false, message: `License is ${license.status}.` },
        { status: 403 }
      );
    }

    // Check if subscription is active
    if (license.subscriptions.status !== 'active') {
      return NextResponse.json(
        {
          valid: false,
          message: `Subscription is ${license.subscriptions.status}. Please update your billing.`
        },
        { status: 403 }
      );
    }

    // Check if license has expired
    if (license.expires_at && new Date(license.expires_at) < new Date()) {
      return NextResponse.json(
        { valid: false, message: 'License has expired.' },
        { status: 403 }
      );
    }

    // Update last validated timestamp
    await supabase
      .from('licenses')
      .update({
        last_validated_at: new Date().toISOString(),
        site_url: site_url || license.site_url
      })
      .eq('id', license.id);

    // Return license data
    return NextResponse.json({
      valid: true,
      data: {
        license_key: license.license_key,
        plan_type: license.plan_type,
        status: license.status,
        max_activations: license.max_activations,
        activations_used: license.activations_used,
        expires_at: license.expires_at,
        subscription_status: license.subscriptions.status,
        subscription_period_end: license.subscriptions.current_period_end,
      }
    });

  } catch (error) {
    console.error('License validation error:', error);
    return NextResponse.json(
      { valid: false, message: 'Server error during validation.' },
      { status: 500 }
    );
  }
}
