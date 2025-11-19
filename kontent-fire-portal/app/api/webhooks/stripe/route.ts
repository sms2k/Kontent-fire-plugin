import { NextRequest, NextResponse } from 'next/server';
import { createClient } from '@supabase/supabase-js';
import Stripe from 'stripe';

const stripe = new Stripe(process.env.STRIPE_SECRET_KEY!, {
  apiVersion: '2024-11-20.acacia',
});

const supabase = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL!,
  process.env.SUPABASE_SERVICE_ROLE_KEY!
);

const webhookSecret = process.env.STRIPE_WEBHOOK_SECRET!;

export async function POST(request: NextRequest) {
  try {
    const body = await request.text();
    const signature = request.headers.get('stripe-signature')!;

    let event: Stripe.Event;

    try {
      event = stripe.webhooks.constructEvent(body, signature, webhookSecret);
    } catch (err: any) {
      console.error('Webhook signature verification failed:', err.message);
      return NextResponse.json(
        { error: 'Webhook signature verification failed' },
        { status: 400 }
      );
    }

    // Handle different event types
    switch (event.type) {
      case 'customer.subscription.created':
      case 'customer.subscription.updated':
        await handleSubscriptionUpdate(event.data.object as Stripe.Subscription);
        break;

      case 'customer.subscription.deleted':
        await handleSubscriptionDeleted(event.data.object as Stripe.Subscription);
        break;

      case 'invoice.payment_succeeded':
        await handleInvoicePaymentSucceeded(event.data.object as Stripe.Invoice);
        break;

      case 'invoice.payment_failed':
        await handleInvoicePaymentFailed(event.data.object as Stripe.Invoice);
        break;

      default:
        console.log(`Unhandled event type: ${event.type}`);
    }

    return NextResponse.json({ received: true });
  } catch (error) {
    console.error('Webhook error:', error);
    return NextResponse.json(
      { error: 'Webhook processing failed' },
      { status: 500 }
    );
  }
}

async function handleSubscriptionUpdate(subscription: Stripe.Subscription) {
  const customerId = subscription.customer as string;
  const subscriptionId = subscription.id;

  // Get user by Stripe customer ID
  const { data: existingSubscription } = await supabase
    .from('subscriptions')
    .select('user_id')
    .eq('stripe_customer_id', customerId)
    .single();

  if (!existingSubscription) {
    console.error('No user found for customer:', customerId);
    return;
  }

  // Determine plan type from price ID
  const priceId = subscription.items.data[0]?.price.id;
  let planType = 'pro'; // default

  if (priceId === process.env.STRIPE_PRICE_ID_BASIC) {
    planType = 'basic';
  } else if (priceId === process.env.STRIPE_PRICE_ID_PRO) {
    planType = 'pro';
  } else if (priceId === process.env.STRIPE_PRICE_ID_ENTERPRISE) {
    planType = 'enterprise';
  }

  // Update subscription
  const { error } = await supabase
    .from('subscriptions')
    .upsert({
      user_id: existingSubscription.user_id,
      stripe_customer_id: customerId,
      stripe_subscription_id: subscriptionId,
      plan_type: planType,
      status: subscription.status,
      current_period_start: new Date(subscription.current_period_start * 1000).toISOString(),
      current_period_end: new Date(subscription.current_period_end * 1000).toISOString(),
      cancel_at_period_end: subscription.cancel_at_period_end,
      canceled_at: subscription.canceled_at
        ? new Date(subscription.canceled_at * 1000).toISOString()
        : null,
    });

  if (error) {
    console.error('Error updating subscription:', error);
    return;
  }

  // Create or update credit allocation
  const creditAmounts: Record<string, number> = {
    basic: 1000,
    pro: 5000,
    enterprise: 20000,
  };

  await supabase
    .from('credits')
    .upsert({
      user_id: existingSubscription.user_id,
      total_credits: creditAmounts[planType],
      used_credits: 0,
      remaining_credits: creditAmounts[planType],
      plan_type: planType,
      period_start: new Date(subscription.current_period_start * 1000).toISOString(),
      period_end: new Date(subscription.current_period_end * 1000).toISOString(),
    });

  console.log('Subscription updated:', subscriptionId, planType);
}

async function handleSubscriptionDeleted(subscription: Stripe.Subscription) {
  const customerId = subscription.customer as string;

  // Update subscription status
  const { error } = await supabase
    .from('subscriptions')
    .update({
      status: 'canceled',
      canceled_at: new Date().toISOString(),
    })
    .eq('stripe_customer_id', customerId);

  if (error) {
    console.error('Error canceling subscription:', error);
  }

  // Deactivate all licenses for this user
  const { data: subscription_data } = await supabase
    .from('subscriptions')
    .select('user_id')
    .eq('stripe_customer_id', customerId)
    .single();

  if (subscription_data) {
    await supabase
      .from('licenses')
      .update({ status: 'suspended' })
      .eq('user_id', subscription_data.user_id);
  }

  console.log('Subscription deleted:', subscription.id);
}

async function handleInvoicePaymentSucceeded(invoice: Stripe.Invoice) {
  const customerId = invoice.customer as string;
  const subscriptionId = invoice.subscription as string;

  console.log('Payment succeeded:', invoice.id, 'for subscription:', subscriptionId);

  // Log successful payment in audit logs
  const { data: subscription_data } = await supabase
    .from('subscriptions')
    .select('user_id')
    .eq('stripe_subscription_id', subscriptionId)
    .single();

  if (subscription_data) {
    await supabase.from('audit_logs').insert({
      user_id: subscription_data.user_id,
      action: 'payment_succeeded',
      resource_type: 'subscription',
      resource_id: subscriptionId,
      details: {
        invoice_id: invoice.id,
        amount: invoice.amount_paid / 100,
        currency: invoice.currency,
      },
    });
  }
}

async function handleInvoicePaymentFailed(invoice: Stripe.Invoice) {
  const customerId = invoice.customer as string;
  const subscriptionId = invoice.subscription as string;

  console.error('Payment failed:', invoice.id, 'for subscription:', subscriptionId);

  // Update subscription status to past_due
  await supabase
    .from('subscriptions')
    .update({ status: 'past_due' })
    .eq('stripe_subscription_id', subscriptionId);

  // Log failed payment
  const { data: subscription_data } = await supabase
    .from('subscriptions')
    .select('user_id')
    .eq('stripe_subscription_id', subscriptionId)
    .single();

  if (subscription_data) {
    await supabase.from('audit_logs').insert({
      user_id: subscription_data.user_id,
      action: 'payment_failed',
      resource_type: 'subscription',
      resource_id: subscriptionId,
      details: {
        invoice_id: invoice.id,
        amount_due: invoice.amount_due / 100,
        currency: invoice.currency,
        attempt_count: invoice.attempt_count,
      },
    });
  }

  // TODO: Send email notification to user about failed payment
}
