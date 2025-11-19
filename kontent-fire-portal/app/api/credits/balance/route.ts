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
        { success: false, message: 'License key header is required.' },
        { status: 400 }
      );
    }

    // Fetch license and user
    const { data: license, error: licenseError } = await supabase
      .from('licenses')
      .select(`
        *,
        profiles!inner(id),
        subscriptions!inner(plan_type, current_period_end)
      `)
      .eq('license_key', licenseKey)
      .eq('status', 'active')
      .single();

    if (licenseError || !license) {
      return NextResponse.json(
        { success: false, message: 'Invalid or inactive license key.' },
        { status: 404 }
      );
    }

    // Get current credit period
    const now = new Date();
    const { data: credits, error: creditsError } = await supabase
      .from('credits')
      .select('*')
      .eq('user_id', license.profiles.id)
      .lte('period_start', now.toISOString())
      .gte('period_end', now.toISOString())
      .single();

    if (creditsError || !credits) {
      // No credits found for current period - create new allocation
      const planCredits = {
        basic: 1000,
        pro: 5000,
        enterprise: 20000
      };

      const totalCredits = planCredits[license.subscriptions.plan_type as keyof typeof planCredits] || 1000;

      const { data: newCredits, error: createError } = await supabase
        .from('credits')
        .insert({
          user_id: license.profiles.id,
          license_key: licenseKey,
          total_credits: totalCredits,
          used_credits: 0,
          remaining_credits: totalCredits,
          plan_type: license.subscriptions.plan_type,
          period_start: now.toISOString(),
          period_end: license.subscriptions.current_period_end
        })
        .select()
        .single();

      if (createError || !newCredits) {
        throw new Error('Failed to create credit allocation');
      }

      return NextResponse.json({
        success: true,
        credits: newCredits.remaining_credits,
        total_credits: newCredits.total_credits,
        used_credits: newCredits.used_credits,
        plan: license.subscriptions.plan_type,
        renewal_date: license.subscriptions.current_period_end
      });
    }

    return NextResponse.json({
      success: true,
      credits: credits.remaining_credits,
      total_credits: credits.total_credits,
      used_credits: credits.used_credits,
      plan: license.subscriptions.plan_type,
      renewal_date: license.subscriptions.current_period_end
    });

  } catch (error) {
    console.error('Credit balance error:', error);
    return NextResponse.json(
      { success: false, message: 'Server error fetching credit balance.' },
      { status: 500 }
    );
  }
}
