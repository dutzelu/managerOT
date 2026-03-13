<?php

if (!defined('ABSPATH')) {
    exit;
}

class CJP_REST
{
    public static function init(): void
    {
        add_action('rest_api_init', [self::class, 'register_routes']);
    }

    public static function register_routes(): void
    {
        register_rest_route('cjp/v1', '/stripe-webhook', [
            'methods' => 'POST',
            'callback' => [self::class, 'handle_stripe_webhook'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('cjp/v1', '/stats', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_stats'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function get_stats(WP_REST_Request $request): WP_REST_Response
    {
        $data = CJP_Stats::get_stats();
        return new WP_REST_Response($data, 200);
    }

    public static function handle_stripe_webhook(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_body();
        $signature = (string) $request->get_header('stripe-signature');

        if (!self::verify_signature($payload, $signature)) {
            return new WP_REST_Response(['error' => 'Invalid Stripe signature'], 400);
        }

        $event = json_decode($payload, true);
        if (!is_array($event) || empty($event['type'])) {
            return new WP_REST_Response(['error' => 'Invalid payload'], 400);
        }

        if ($event['type'] !== 'checkout.session.completed') {
            return new WP_REST_Response(['status' => 'ignored'], 200);
        }

        $session = $event['data']['object'] ?? [];
        if (!is_array($session)) {
            return new WP_REST_Response(['error' => 'Invalid session object'], 400);
        }

        $stripe_payment_id = sanitize_text_field((string) ($session['id'] ?? ''));
        if ($stripe_payment_id === '') {
            return new WP_REST_Response(['error' => 'Missing payment id'], 400);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'cjp_donations';

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE stripe_payment_id = %s LIMIT 1",
            $stripe_payment_id
        ));

        if ($existing) {
            return new WP_REST_Response(['status' => 'already_processed'], 200);
        }

        $donor_name = self::extract_donor_name($session);
        $currency = strtoupper(sanitize_text_field((string) ($session['currency'] ?? 'RON')));
        $amount = ((float) ($session['amount_total'] ?? 0)) / 100;

        [$donation_type, $quantity] = self::extract_donation_type_and_quantity($session);

        $inserted = $wpdb->insert(
            $table_name,
            [
                'stripe_payment_id' => $stripe_payment_id,
                'donor_name' => $donor_name,
                'amount' => round($amount, 2),
                'currency' => $currency,
                'donation_type' => $donation_type,
                'quantity' => $quantity,
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%f', '%s', '%s', '%f', '%s']
        );

        if ($inserted === false) {
            return new WP_REST_Response(['error' => 'Insert failed'], 500);
        }

        CJP_Stats::invalidate_cache();

        return new WP_REST_Response(['status' => 'ok'], 200);
    }

    private static function extract_donor_name(array $session): string
    {
        $name = '';
        if (!empty($session['customer_details']['name'])) {
            $name = (string) $session['customer_details']['name'];
        }

        if ($name === '' && !empty($session['metadata']['donor_name'])) {
            $name = (string) $session['metadata']['donor_name'];
        }

        $name = sanitize_text_field($name);
        return $name !== '' ? $name : 'Donator anonim';
    }

    private static function extract_donation_type_and_quantity(array $session): array
    {
        $line_item_name = '';
        $quantity = 1.0;

        if (!empty($session['line_items']['data'][0]) && is_array($session['line_items']['data'][0])) {
            $line_item = $session['line_items']['data'][0];
            $quantity = (float) ($line_item['quantity'] ?? 1);

            if (!empty($line_item['description'])) {
                $line_item_name = (string) $line_item['description'];
            } elseif (!empty($line_item['price']['nickname'])) {
                $line_item_name = (string) $line_item['price']['nickname'];
            } elseif (!empty($line_item['price']['product']['name'])) {
                $line_item_name = (string) $line_item['price']['product']['name'];
            }
        }

        if (!empty($session['metadata']['quantity'])) {
            $quantity = (float) $session['metadata']['quantity'];
        }

        $raw_type = '';
        if (!empty($session['metadata']['donation_type'])) {
            $raw_type = (string) $session['metadata']['donation_type'];
        }

        $comparable = strtolower(trim($raw_type !== '' ? $raw_type : $line_item_name));

        $map = [
            'beton_fundatie' => 'beton_fundatie',
            '1 mc beton pentru fundatie' => 'beton_fundatie',
            '1 mc beton pentru fundație' => 'beton_fundatie',
            'bca' => 'bca',
            '1 mc bca pentru ziduri' => 'bca',
            'manopera' => 'manopera',
            '1 zi manopera' => 'manopera',
            '1 zi manoperă' => 'manopera',
            'fier' => 'fier',
            '20 kg fier beton' => 'fier',
            'monthly_support' => 'monthly_support',
            'abonament lunar' => 'monthly_support',
            'monthly' => 'monthly_support',
            'general_donation' => 'general_donation',
            'beton_suprastructura' => 'beton_suprastructura',
            'lemn_acoperis' => 'lemn_acoperis',
        ];

        $donation_type = $map[$comparable] ?? 'general_donation';

        if ($quantity <= 0) {
            $quantity = 1.0;
        }

        return [sanitize_key($donation_type), round($quantity, 2)];
    }

    private static function verify_signature(string $payload, string $signature_header): bool
    {
        $secret = '';
        if (class_exists('CJP_Admin')) {
            $secret = CJP_Admin::get_webhook_secret();
        }

        if ($secret === '' && defined('CJP_STRIPE_WEBHOOK_SECRET')) {
            $secret = (string) CJP_STRIPE_WEBHOOK_SECRET;
        }

        $secret = (string) apply_filters('cjp_stripe_webhook_secret', $secret);
        if ($secret === '' || $signature_header === '') {
            return false;
        }

        $timestamp = '';
        $signatures = [];

        foreach (explode(',', $signature_header) as $part) {
            $segments = explode('=', trim($part), 2);
            if (count($segments) !== 2) {
                continue;
            }

            [$key, $value] = $segments;
            if ($key === 't') {
                $timestamp = $value;
            }
            if ($key === 'v1') {
                $signatures[] = $value;
            }
        }

        if ($timestamp === '' || empty($signatures)) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $signed_payload = $timestamp . '.' . $payload;
        $expected = hash_hmac('sha256', $signed_payload, $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }
}
