<?php

if (!defined('ABSPATH')) {
    exit;
}

class CJP_Stats
{
    public const CACHE_KEY = 'cjp_donation_stats';
    public const CACHE_TTL = 60;

    public static function init(): void
    {
        add_filter('cjp_donation_targets', [self::class, 'default_targets']);
        add_filter('cjp_stripe_payment_links', [self::class, 'default_payment_links']);
        add_filter('cjp_currency_rates_to_eur', [self::class, 'default_currency_rates']);
    }

    public static function default_targets(array $targets): array
    {
        return [
            'funding' => [
                'foundation' => 20000,
                'structure' => 80000,
                'interior' => 100000,
                'total' => 200000,
            ],
            'materials' => [
                'beton_fundatie' => ['target' => 120, 'unit' => 'm³', 'label' => 'Beton fundație'],
                'beton_suprastructura' => ['target' => 86, 'unit' => 'm³', 'label' => 'Beton suprastructură'],
                'bca' => ['target' => 93, 'unit' => 'm³', 'label' => 'BCA ziduri'],
                'manopera' => ['target' => 45000, 'unit' => 'EUR', 'label' => 'Manoperă'],
                'lemn_acoperis' => ['target' => 32, 'unit' => 'm³', 'label' => 'Lemn acoperiș'],
            ],
        ];
    }

    public static function default_payment_links(array $links): array
    {
        return [
            'one_time' => [
                [
                    'id' => 'beton_fundatie',
                    'title' => '1 mc beton pentru fundație',
                    'description' => 'Contribuie direct la turnarea fundației.',
                    'price' => 450,
                    'currency' => 'lei',
                    'link' => '#',
                ],
                [
                    'id' => 'bca',
                    'title' => '1 mc BCA pentru ziduri',
                    'description' => 'Sprijină zidăria construcției.',
                    'price' => 650,
                    'currency' => 'lei',
                    'link' => '#',
                ],
                [
                    'id' => 'manopera',
                    'title' => '1 zi manoperă',
                    'description' => 'Acoperă costul unei zile de muncă.',
                    'price' => 1000,
                    'currency' => 'lei',
                    'link' => '#',
                ],
                [
                    'id' => 'fier',
                    'title' => '20 kg fier beton',
                    'description' => 'Donează pentru armarea structurii.',
                    'price' => 100,
                    'currency' => 'lei',
                    'link' => '#',
                ],
                [
                    'id' => 'custom_amount',
                    'title' => 'Susține "Casa Justin Pârvu"',
                    'description' => 'Donează cu suma pe care o alegi tu.',
                    'price' => 0,
                    'currency' => 'lei',
                    'link' => '#',
                ],
            ],
            'monthly' => [
                ['id' => 'monthly_10', 'title' => '10 lei / lună', 'price' => 10, 'currency' => 'lei', 'link' => '#'],
                ['id' => 'monthly_25', 'title' => '25 lei / lună', 'price' => 25, 'currency' => 'lei', 'link' => '#'],
                ['id' => 'monthly_50', 'title' => '50 lei / lună', 'price' => 50, 'currency' => 'lei', 'link' => '#'],
                ['id' => 'monthly_100', 'title' => '100 lei / lună', 'price' => 100, 'currency' => 'lei', 'link' => '#'],
            ],
        ];
    }

    public static function get_payment_links(): array
    {
        $defaults = self::default_payment_links([]);
        $links = apply_filters('cjp_stripe_payment_links', $defaults);

        if (!is_array($links)) {
            $links = $defaults;
        }

        $links['one_time'] = is_array($links['one_time'] ?? null) ? $links['one_time'] : $defaults['one_time'];
        $links['monthly'] = is_array($links['monthly'] ?? null) ? $links['monthly'] : $defaults['monthly'];

        if (class_exists('CJP_Admin')) {
            $override_map = [
                'one_time' => [
                    'beton_fundatie',
                    'bca',
                    'manopera',
                    'fier',
                    'custom_amount',
                ],
                'monthly' => [
                    'monthly_10',
                    'monthly_25',
                    'monthly_50',
                    'monthly_100',
                ],
            ];

            foreach ($override_map as $group => $ids) {
                foreach ($links[$group] as $index => $card) {
                    $card_id = (string) ($card['id'] ?? '');
                    if (!in_array($card_id, $ids, true)) {
                        continue;
                    }

                    $admin_link = CJP_Admin::get_payment_link($group, $card_id);
                    if ($admin_link !== '') {
                        $links[$group][$index]['link'] = $admin_link;
                    }
                }
            }
        }

        return $links;
    }

    public static function default_currency_rates(array $rates): array
    {
        return [
            'eur' => 1.0,
            'ron' => 5.0,
            'lei' => 5.0,
        ];
    }

    public static function invalidate_cache(): void
    {
        delete_transient(self::CACHE_KEY);
    }

    public static function get_stats(): array
    {
        $cached = get_transient(self::CACHE_KEY);
        if ($cached !== false) {
            return $cached;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'cjp_donations';

        $targets = apply_filters('cjp_donation_targets', []);
        $funding_targets = $targets['funding'] ?? [];
        $material_targets = $targets['materials'] ?? [];

        $rows = $wpdb->get_results("SELECT donation_type, amount, currency, quantity FROM {$table_name}", ARRAY_A);

        $currency_rates = apply_filters('cjp_currency_rates_to_eur', []);
        $total_eur = 0.0;

        $material_totals = [
            'beton_fundatie' => 0.0,
            'beton_suprastructura' => 0.0,
            'bca' => 0.0,
            'manopera' => 0.0,
            'lemn_acoperis' => 0.0,
        ];

        foreach ($rows as $row) {
            $donation_type = sanitize_key((string) ($row['donation_type'] ?? 'general_donation'));
            $amount = (float) ($row['amount'] ?? 0);
            $currency = strtolower(sanitize_text_field((string) ($row['currency'] ?? 'eur')));
            $quantity = (float) ($row['quantity'] ?? 0);

            $rate = (float) ($currency_rates[$currency] ?? 1);
            if ($rate <= 0) {
                $rate = 1;
            }
            $total_eur += ($amount / $rate);

            if (array_key_exists($donation_type, $material_totals)) {
                if ($donation_type === 'manopera') {
                    $material_totals[$donation_type] += ($amount / $rate);
                } else {
                    $material_totals[$donation_type] += $quantity;
                }
            }
        }

        $material_progress = [];
        foreach ($material_targets as $type => $target_config) {
            $db_current = (float) ($material_totals[$type] ?? 0);

            $manual_current = 0.0;
            if (class_exists('CJP_Admin')) {
                $manual_current = CJP_Admin::get_material_current($type);
            }

            $current = $manual_current > 0 ? $manual_current : $db_current;
            $target = (float) ($target_config['target'] ?? 0);
            $percent = $target > 0 ? min(100, ($current / $target) * 100) : 0;

            $material_progress[$type] = [
                'label' => (string) ($target_config['label'] ?? $type),
                'unit' => (string) ($target_config['unit'] ?? ''),
                'current' => round($current, 2),
                'target' => $target,
                'percent' => round($percent, 2),
            ];
        }

        $goal_total = (float) ($funding_targets['total'] ?? 200000);
        $funding_percent = $goal_total > 0 ? min(100, ($total_eur / $goal_total) * 100) : 0;

        $recent = $wpdb->get_results(
            "SELECT donor_name, amount, currency, donation_type, quantity, created_at
             FROM {$table_name}
             ORDER BY created_at DESC, id DESC
             LIMIT 20",
            ARRAY_A
        );

        $data = [
            'totals' => [
                'eur_raised' => round($total_eur, 2),
                'eur_goal' => $goal_total,
                'percent' => round($funding_percent, 2),
                'foundation_target' => (float) ($funding_targets['foundation'] ?? 20000),
                'structure_target' => (float) ($funding_targets['structure'] ?? 80000),
                'interior_target' => (float) ($funding_targets['interior'] ?? 100000),
            ],
            'materials' => $material_progress,
            'recent_donations' => array_map([self::class, 'normalize_recent_row'], $recent),
            'updated_at' => current_time('mysql'),
        ];

        set_transient(self::CACHE_KEY, $data, self::CACHE_TTL);
        return $data;
    }

    private static function normalize_recent_row(array $row): array
    {
        return [
            'donor_name' => sanitize_text_field((string) ($row['donor_name'] ?? 'Donator anonim')),
            'amount' => round((float) ($row['amount'] ?? 0), 2),
            'currency' => strtoupper(sanitize_text_field((string) ($row['currency'] ?? 'RON'))),
            'donation_type' => sanitize_key((string) ($row['donation_type'] ?? 'general_donation')),
            'quantity' => round((float) ($row['quantity'] ?? 0), 2),
            'created_at' => sanitize_text_field((string) ($row['created_at'] ?? '')),
        ];
    }
}
