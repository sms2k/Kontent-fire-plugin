import { NextRequest, NextResponse } from 'next/server';
import { createClient } from '@supabase/supabase-js';

const supabase = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL!,
  process.env.SUPABASE_SERVICE_ROLE_KEY!
);

// Google Gemini API pricing
const MODEL_COSTS = {
  'gemini-2.0-flash-exp': { input: 0, output: 0 }, // Free during preview
  'gemini-1.5-pro': { input: 1.25, output: 5 }, // per million tokens
  'gemini-1.5-flash': { input: 0.075, output: 0.3 },
  'imagen-4.0': { image: 0.04 }, // per image
  'veo-3': { video: 0.50 }, // per video (rough estimate)
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
    const { model, endpoint, ...params } = body;

    if (!model || !endpoint) {
      return NextResponse.json(
        { error: 'Missing model or endpoint parameter' },
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

    // Make API call to Google Gemini
    const startTime = Date.now();
    let response;
    let apiCostUSD = 0;
    let creditsUsed = 0;

    try {
      const apiKey = process.env.GOOGLE_AI_API_KEY;
      let apiUrl = '';

      if (endpoint === 'generateContent') {
        // Gemini text generation
        apiUrl = `https://generativelanguage.googleapis.com/v1beta/models/${model}:generateContent?key=${apiKey}`;

        const apiResponse = await fetch(apiUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(params),
        });

        response = await apiResponse.json();

        if (!apiResponse.ok) {
          throw new Error(response.error?.message || 'Gemini API request failed');
        }

        // Calculate cost
        const inputTokens = response.usageMetadata?.promptTokenCount || 0;
        const outputTokens = response.usageMetadata?.candidatesTokenCount || 0;
        const modelCost = MODEL_COSTS[model as keyof typeof MODEL_COSTS] || MODEL_COSTS['gemini-1.5-flash'];

        if ('input' in modelCost) {
          apiCostUSD = (
            (inputTokens / 1000000) * modelCost.input +
            (outputTokens / 1000000) * modelCost.output
          );
        }
      } else if (endpoint === 'generateImage') {
        // Imagen 4 image generation
        apiUrl = `https://us-central1-aiplatform.googleapis.com/v1/projects/${process.env.GOOGLE_CLOUD_PROJECT_ID}/locations/us-central1/publishers/google/models/imagen-4.0:predict`;

        const apiResponse = await fetch(apiUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${await getGoogleAccessToken()}`,
          },
          body: JSON.stringify({
            instances: [{ prompt: params.prompt }],
            parameters: {
              sampleCount: params.n || 1,
              aspectRatio: params.aspectRatio || '1:1',
              ...params,
            },
          }),
        });

        response = await apiResponse.json();

        if (!apiResponse.ok) {
          throw new Error(response.error?.message || 'Imagen API request failed');
        }

        // Calculate image cost
        const numImages = params.n || 1;
        apiCostUSD = MODEL_COSTS['imagen-4.0'].image * numImages;
      } else if (endpoint === 'generateVideo') {
        // Veo 3 video generation
        apiUrl = `https://us-central1-aiplatform.googleapis.com/v1/projects/${process.env.GOOGLE_CLOUD_PROJECT_ID}/locations/us-central1/publishers/google/models/veo-3:predict`;

        const apiResponse = await fetch(apiUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${await getGoogleAccessToken()}`,
          },
          body: JSON.stringify({
            instances: [{ prompt: params.prompt }],
            parameters: params,
          }),
        });

        response = await apiResponse.json();

        if (!apiResponse.ok) {
          throw new Error(response.error?.message || 'Veo API request failed');
        }

        // Calculate video cost
        apiCostUSD = MODEL_COSTS['veo-3'].video;
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
        api_provider: 'gemini',
        endpoint,
        model,
        tokens_used: response.usageMetadata?.totalTokenCount || 0,
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
        operation: `gemini_${endpoint}`,
        credits_used: creditsUsed,
        credits_remaining: credits.remaining_credits - creditsUsed,
        metadata: {
          model,
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
      console.error('Gemini API error:', err);

      // Log failed API call
      await supabase.from('api_usage_logs').insert({
        user_id: license.user_id,
        license_key: licenseKey,
        api_provider: 'gemini',
        endpoint,
        model,
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
    console.error('Gemini proxy error:', error);
    return NextResponse.json(
      { error: 'Proxy request failed' },
      { status: 500 }
    );
  }
}

// Helper function to get Google Cloud access token
async function getGoogleAccessToken(): Promise<string> {
  // This is a simplified version - in production, use Google Auth Library
  // or service account credentials
  const credentials = JSON.parse(process.env.GOOGLE_APPLICATION_CREDENTIALS || '{}');

  // For now, return a placeholder - you'll need to implement proper OAuth2 flow
  // or use Application Default Credentials (ADC)
  return process.env.GOOGLE_ACCESS_TOKEN || '';
}
