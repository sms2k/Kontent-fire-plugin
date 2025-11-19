import { NextRequest, NextResponse } from 'next/server';
import { createClient } from '@supabase/supabase-js';

const supabase = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL!,
  process.env.SUPABASE_SERVICE_ROLE_KEY!
);

export async function POST(request: NextRequest) {
  try {
    const licenseKey = request.headers.get('X-License-Key');
    const body = await request.json();
    const { operation, quantity = 1, metadata = {} } = body;

    if (!licenseKey) {
      return NextResponse.json(
        { success: false, message: 'License key header is required.' },
        { status: 400 }
      );
    }

    if (!operation) {
      return NextResponse.json(
        { success: false, message: 'Operation type is required.' },
        { status: 400 }
      );
    }

    // Fetch license and user
    const { data: license, error: licenseError } = await supabase
      .from('licenses')
      .select(`
        *,
        profiles!inner(id)
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

    // Get operation cost
    const { data: operationCost } = await supabase
      .from('operation_costs')
      .select('credits')
      .eq('operation', operation)
      .single();

    const creditsToDeduct = operationCost ? operationCost.credits * quantity : 10; // Default to 10 if not found

    // Get current credit period
    const now = new Date();
    const { data: credits, error: creditsError } = await supabase
      .from('credits')
      .select('*')
      .eq('user_id', license.profiles.id)
      .lte('period_start', now.toISOString())
      .gte('period_end', now.toISOString())
      .single();

    if (!credits) {
      return NextResponse.json(
        {
          success: false,
          message: 'No active credit allocation found. Please renew your subscription.'
        },
        { status: 403 }
      );
    }

    // Check if enough credits
    if (credits.remaining_credits < creditsToDeduct) {
      return NextResponse.json(
        {
          success: false,
          message: `Insufficient credits. Required: ${creditsToDeduct}, Available: ${credits.remaining_credits}`,
          credits_required: creditsToDeduct,
          credits_available: credits.remaining_credits,
          upgrade_url: `${process.env.NEXT_PUBLIC_APP_URL}/billing`
        },
        { status: 403 }
      );
    }

    // Deduct credits
    const newUsedCredits = credits.used_credits + creditsToDeduct;
    const newRemainingCredits = credits.remaining_credits - creditsToDeduct;

    const { error: updateError } = await supabase
      .from('credits')
      .update({
        used_credits: newUsedCredits,
        remaining_credits: newRemainingCredits
      })
      .eq('id', credits.id);

    if (updateError) {
      throw updateError;
    }

    // Log transaction
    await supabase.from('credit_transactions').insert({
      user_id: license.profiles.id,
      license_key: licenseKey,
      operation,
      credits_used: creditsToDeduct,
      credits_remaining: newRemainingCredits,
      metadata: {
        quantity,
        ...metadata
      }
    });

    return NextResponse.json({
      success: true,
      credits_deducted: creditsToDeduct,
      remaining_credits: newRemainingCredits,
      total_credits: credits.total_credits,
      used_credits: newUsedCredits
    });

  } catch (error) {
    console.error('Credit deduction error:', error);
    return NextResponse.json(
      { success: false, message: 'Server error during credit deduction.' },
      { status: 500 }
    );
  }
}
