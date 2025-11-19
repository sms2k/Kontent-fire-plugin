import { NextRequest, NextResponse } from 'next/server';
import { createClient } from '@supabase/supabase-js';
import Anthropic from '@anthropic-ai/sdk';

const supabase = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL!,
  process.env.SUPABASE_SERVICE_ROLE_KEY!
);

const anthropic = new Anthropic({
  apiKey: process.env.ANTHROPIC_API_KEY!,
});

// Credit costs based on model and usage
const MODEL_COSTS = {
  'claude-3-5-sonnet-20241022': { input: 3, output: 15 }, // per million tokens
  'claude-3-5-haiku-20241022': { input: 0.8, output: 4 },
  'claude-3-opus-20240229': { input: 15, output: 75 },
};

export async function POST(request: NextRequest) {
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

    // Get request body
    const body = await request.json();
    const { model, messages, max_tokens, temperature, system } = body;

    if (!model || !messages) {
      return NextResponse.json(
        { error: 'Missing required parameters' },
        { status: 400 }
      );
    }

    // Check credit balance
    const { data: credits } = await supabase
      .from('credits')
      .select('*')
      .eq('user_id', license.user_id)
      .eq('license_key', licenseKey)
      .single();

    if (!credits || credits.remaining_credits <= 0) {
      return NextResponse.json(
        {
          error: 'Insufficient credits',
          upgrade_url: `${process.env.NEXT_PUBLIC_APP_URL}/dashboard/billing`,
        },
        { status: 403 }
      );
    }

    // Estimate credits needed (rough estimate before API call)
    const estimatedInputTokens = JSON.stringify(messages).length / 4; // Rough estimation
    const estimatedCredits = Math.ceil((estimatedInputTokens / 1000000) * 100); // Convert to credits

    if (credits.remaining_credits < estimatedCredits) {
      return NextResponse.json(
        {
          error: 'Insufficient credits for this operation',
          required: estimatedCredits,
          available: credits.remaining_credits,
          upgrade_url: `${process.env.NEXT_PUBLIC_APP_URL}/dashboard/billing`,
        },
        { status: 403 }
      );
    }

    // Make API call to Claude
    const startTime = Date.now();
    let response;
    let error = null;

    try {
      response = await anthropic.messages.create({
        model,
        messages,
        max_tokens: max_tokens || 4096,
        temperature: temperature || 1,
        ...(system && { system }),
      });
    } catch (err: any) {
      error = err.message;
      console.error('Claude API error:', err);

      // Log failed API call
      await supabase.from('api_usage_logs').insert({
        user_id: license.user_id,
        license_key: licenseKey,
        api_provider: 'claude',
        endpoint: '/messages',
        model,
        status: 'error',
        error_message: error,
        request_metadata: { messages, max_tokens, temperature },
      });

      return NextResponse.json(
        { error: 'API request failed', details: error },
        { status: 500 }
      );
    }

    // Calculate actual cost and credits
    const inputTokens = response.usage.input_tokens;
    const outputTokens = response.usage.output_tokens;

    const modelCost = MODEL_COSTS[model as keyof typeof MODEL_COSTS] || MODEL_COSTS['claude-3-5-sonnet-20241022'];
    const apiCostUSD = (
      (inputTokens / 1000000) * modelCost.input +
      (outputTokens / 1000000) * modelCost.output
    );

    // Apply 500% markup: $10 API cost = 1000 credits at $29/month
    const creditsUsed = Math.ceil((apiCostUSD / 10) * 1000);

    // Deduct credits
    const { error: deductError } = await supabase
      .from('credits')
      .update({
        used_credits: credits.used_credits + creditsUsed,
        remaining_credits: credits.remaining_credits - creditsUsed,
      })
      .eq('user_id', license.user_id)
      .eq('license_key', licenseKey);

    if (deductError) {
      console.error('Error deducting credits:', deductError);
    }

    // Log successful API call
    await supabase.from('api_usage_logs').insert({
      user_id: license.user_id,
      license_key: licenseKey,
      api_provider: 'claude',
      endpoint: '/messages',
      model,
      tokens_used: inputTokens + outputTokens,
      api_cost: apiCostUSD,
      credits_charged: creditsUsed,
      request_metadata: { messages, max_tokens, temperature },
      response_metadata: { usage: response.usage, latency_ms: Date.now() - startTime },
      status: 'success',
    });

    // Log credit transaction
    await supabase.from('credit_transactions').insert({
      user_id: license.user_id,
      license_key: licenseKey,
      operation: 'claude_api_call',
      credits_used: creditsUsed,
      credits_remaining: credits.remaining_credits - creditsUsed,
      metadata: {
        model,
        input_tokens: inputTokens,
        output_tokens: outputTokens,
        api_cost: apiCostUSD,
      },
    });

    return NextResponse.json({
      success: true,
      data: response,
      usage: {
        credits_used: creditsUsed,
        credits_remaining: credits.remaining_credits - creditsUsed,
        tokens: {
          input: inputTokens,
          output: outputTokens,
          total: inputTokens + outputTokens,
        },
        cost_usd: apiCostUSD,
      },
    });
  } catch (error) {
    console.error('Claude proxy error:', error);
    return NextResponse.json(
      { error: 'Proxy request failed' },
      { status: 500 }
    );
  }
}
