import { NextRequest, NextResponse } from 'next/server';
import { createClient } from '@supabase/supabase-js';
import OpenAI from 'openai';

const supabase = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL!,
  process.env.SUPABASE_SERVICE_ROLE_KEY!
);

const openai = new OpenAI({
  apiKey: process.env.OPENAI_API_KEY!,
});

// Credit costs based on model
const MODEL_COSTS = {
  'gpt-4-turbo-preview': { input: 10, output: 30 }, // per million tokens
  'gpt-4': { input: 30, output: 60 },
  'gpt-3.5-turbo': { input: 0.5, output: 1.5 },
  'dall-e-3': { '1024x1024': 0.04, '1024x1792': 0.08, '1792x1024': 0.08 }, // per image
  'dall-e-2': { '1024x1024': 0.02, '512x512': 0.018, '256x256': 0.016 },
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
    const { endpoint, ...params } = body;

    if (!endpoint) {
      return NextResponse.json(
        { error: 'Missing endpoint parameter' },
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

    // Make API call based on endpoint
    const startTime = Date.now();
    let response;
    let apiCostUSD = 0;
    let creditsUsed = 0;

    try {
      if (endpoint === 'chat.completions') {
        response = await openai.chat.completions.create(params);

        // Calculate cost
        const inputTokens = response.usage?.prompt_tokens || 0;
        const outputTokens = response.usage?.completion_tokens || 0;
        const modelCost = MODEL_COSTS[params.model as keyof typeof MODEL_COSTS] || MODEL_COSTS['gpt-3.5-turbo'];

        if ('input' in modelCost) {
          apiCostUSD = (
            (inputTokens / 1000000) * modelCost.input +
            (outputTokens / 1000000) * modelCost.output
          );
        }
      } else if (endpoint === 'images.generate') {
        response = await openai.images.generate(params);

        // Calculate image generation cost
        const model = params.model || 'dall-e-2';
        const size = params.size || '1024x1024';
        const n = params.n || 1;

        const modelCost = MODEL_COSTS[model as keyof typeof MODEL_COSTS];
        if (modelCost && typeof modelCost === 'object' && size in modelCost) {
          apiCostUSD = (modelCost[size as keyof typeof modelCost] as number) * n;
        }
      } else {
        return NextResponse.json(
          { error: `Unsupported endpoint: ${endpoint}` },
          { status: 400 }
        );
      }

      // Apply 500% markup
      creditsUsed = Math.ceil((apiCostUSD / 10) * 1000);

      // Check if user has enough credits
      if (credits.remaining_credits < creditsUsed) {
        return NextResponse.json(
          {
            error: 'Insufficient credits for this operation',
            required: creditsUsed,
            available: credits.remaining_credits,
            upgrade_url: `${process.env.NEXT_PUBLIC_APP_URL}/dashboard/billing`,
          },
          { status: 403 }
        );
      }

      // Deduct credits
      await supabase
        .from('credits')
        .update({
          used_credits: credits.used_credits + creditsUsed,
          remaining_credits: credits.remaining_credits - creditsUsed,
        })
        .eq('user_id', license.user_id)
        .eq('license_key', licenseKey);

      // Log successful API call
      await supabase.from('api_usage_logs').insert({
        user_id: license.user_id,
        license_key: licenseKey,
        api_provider: 'openai',
        endpoint,
        model: params.model,
        tokens_used: response.usage ? response.usage.total_tokens : 0,
        api_cost: apiCostUSD,
        credits_charged: creditsUsed,
        request_metadata: params,
        response_metadata: { latency_ms: Date.now() - startTime },
        status: 'success',
      });

      // Log credit transaction
      await supabase.from('credit_transactions').insert({
        user_id: license.user_id,
        license_key: licenseKey,
        operation: `openai_${endpoint}`,
        credits_used: creditsUsed,
        credits_remaining: credits.remaining_credits - creditsUsed,
        metadata: {
          model: params.model,
          endpoint,
          api_cost: apiCostUSD,
        },
      });

      return NextResponse.json({
        success: true,
        data: response,
        usage: {
          credits_used: creditsUsed,
          credits_remaining: credits.remaining_credits - creditsUsed,
          cost_usd: apiCostUSD,
        },
      });
    } catch (err: any) {
      console.error('OpenAI API error:', err);

      // Log failed API call
      await supabase.from('api_usage_logs').insert({
        user_id: license.user_id,
        license_key: licenseKey,
        api_provider: 'openai',
        endpoint,
        model: params.model,
        status: 'error',
        error_message: err.message,
        request_metadata: params,
      });

      return NextResponse.json(
        { error: 'API request failed', details: err.message },
        { status: 500 }
      );
    }
  } catch (error) {
    console.error('OpenAI proxy error:', error);
    return NextResponse.json(
      { error: 'Proxy request failed' },
      { status: 500 }
    );
  }
}
