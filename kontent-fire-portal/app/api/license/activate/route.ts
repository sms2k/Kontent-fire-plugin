import { NextRequest, NextResponse } from 'next/server';
import { createClient } from '@supabase/supabase-js';

const supabase = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL!,
  process.env.SUPABASE_SERVICE_ROLE_KEY!
);

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { license_key, site_url, site_name } = body;

    if (!license_key || !site_url) {
      return NextResponse.json(
        { success: false, message: 'License key and site URL are required.' },
        { status: 400 }
      );
    }

    // Fetch license
    const { data: license, error } = await supabase
      .from('licenses')
      .select(`
        *,
        subscriptions!inner(status, plan_type)
      `)
      .eq('license_key', license_key)
      .single();

    if (error || !license) {
      return NextResponse.json(
        { success: false, message: 'Invalid license key.' },
        { status: 404 }
      );
    }

    // Check if subscription is active
    if (license.subscriptions.status !== 'active') {
      return NextResponse.json(
        {
          success: false,
          message: `Subscription is ${license.subscriptions.status}. Please update your billing.`
        },
        { status: 403 }
      );
    }

    // Check if already activated for this site
    if (license.site_url === site_url) {
      return NextResponse.json({
        success: true,
        message: 'License is already activated for this site.',
        data: {
          plan_type: license.plan_type,
          status: license.status,
          max_activations: license.max_activations
        }
      });
    }

    // Check activation limit
    if (license.site_url && license.site_url !== site_url) {
      // Check if max activations reached
      if (license.activations_used >= license.max_activations) {
        return NextResponse.json(
          {
            success: false,
            message: `License activation limit reached (${license.max_activations} sites). Please deactivate another site or upgrade your plan.`
          },
          { status: 403 }
        );
      }
    }

    // Activate license for this site
    const { error: updateError } = await supabase
      .from('licenses')
      .update({
        site_url,
        site_name: site_name || 'WordPress Site',
        status: 'active',
        activations_used: license.site_url ? license.activations_used + 1 : 1,
        last_validated_at: new Date().toISOString()
      })
      .eq('id', license.id);

    if (updateError) {
      throw updateError;
    }

    // Log activation in audit logs
    await supabase.from('audit_logs').insert({
      user_id: license.user_id,
      action: 'license_activated',
      resource_type: 'license',
      resource_id: license.id,
      details: {
        license_key,
        site_url,
        site_name
      }
    });

    return NextResponse.json({
      success: true,
      message: 'License activated successfully!',
      data: {
        plan_type: license.plan_type,
        status: 'active',
        max_activations: license.max_activations,
        activations_used: license.activations_used + 1
      }
    });

  } catch (error) {
    console.error('License activation error:', error);
    return NextResponse.json(
      { success: false, message: 'Server error during activation.' },
      { status: 500 }
    );
  }
}
