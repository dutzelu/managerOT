<?php

if (!defined('ABSPATH')) {
    exit;
}

class CJP_Admin
{
    public const SETTINGS_GROUP = 'cjp_donations_settings_group';
    public const SETTINGS_OPTION = 'cjp_donations_settings';

    public static function init(): void
    {
        if (!is_admin()) {
            return;
        }

        add_action('admin_menu', [self::class, 'register_menu']);
        add_action('admin_init', [self::class, 'register_settings']);
    }

    public static function register_menu(): void
    {
        add_options_page(
            'Casa Justin Parvu Donations',
            'CJP Donations',
            'manage_options',
            'cjp-donations',
            [self::class, 'render_settings_page']
        );
    }

    public static function register_settings(): void
    {
        register_setting(
            self::SETTINGS_GROUP,
            self::SETTINGS_OPTION,
            [
                'type' => 'array',
                'sanitize_callback' => [self::class, 'sanitize_settings'],
                'default' => self::get_default_settings(),
            ]
        );

        add_settings_section(
            'cjp_section_stripe',
            'Stripe',
            '__return_false',
            'cjp-donations'
        );

        add_settings_field(
            'webhook_secret',
            'Stripe Webhook Secret',
            [self::class, 'render_webhook_secret_field'],
            'cjp-donations',
            'cjp_section_stripe'
        );

        add_settings_section(
            'cjp_section_one_time',
            'Linkuri donații unice',
            '__return_false',
            'cjp-donations'
        );

        $one_time_fields = [
            'beton_fundatie' => '1 mc beton pentru fundație',
            'bca' => '1 mc BCA pentru ziduri',
            'manopera' => '1 zi manoperă',
            'fier' => '20 kg fier beton',
            'custom_amount' => 'Susține "Casa Justin Pârvu" (sumă liberă)',
        ];

        foreach ($one_time_fields as $key => $label) {
            add_settings_field(
                'one_time_' . $key,
                $label,
                [self::class, 'render_link_field'],
                'cjp-donations',
                'cjp_section_one_time',
                [
                    'path' => 'payment_links.one_time.' . $key,
                    'placeholder' => 'https://buy.stripe.com/...',
                ]
            );
        }

        add_settings_section(
            'cjp_section_monthly',
            'Linkuri donații lunare',
            '__return_false',
            'cjp-donations'
        );

        $monthly_fields = [
            'monthly_10' => '10 lei / lună',
            'monthly_25' => '25 lei / lună',
            'monthly_50' => '50 lei / lună',
            'monthly_100' => '100 lei / lună',
        ];

        foreach ($monthly_fields as $key => $label) {
            add_settings_field(
                'monthly_' . $key,
                $label,
                [self::class, 'render_link_field'],
                'cjp-donations',
                'cjp_section_monthly',
                [
                    'path' => 'payment_links.monthly.' . $key,
                    'placeholder' => 'https://buy.stripe.com/...',
                ]
            );
        }

        add_settings_section(
            'cjp_section_materials',
            'Progres materiale (valori actuale)',
            [self::class, 'render_materials_section_intro'],
            'cjp-donations'
        );

        $materials_fields = [
            'beton_fundatie'       => 'Beton fundație (m³)',
            'beton_suprastructura' => 'Beton suprastructură (m³)',
            'bca'                  => 'BCA ziduri (m³)',
            'manopera'             => 'Manoperă (EUR)',
            'lemn_acoperis'        => 'Lemn acoperiș (m³)',
        ];

        foreach ($materials_fields as $key => $label) {
            add_settings_field(
                'material_' . $key,
                $label,
                [self::class, 'render_material_field'],
                'cjp-donations',
                'cjp_section_materials',
                ['key' => $key]
            );
        }
    }

    public static function render_materials_section_intro(): void
    {
        echo '<p class="description">Setează manual valorile actuale pentru fiecare material. Aceste valori înlocuiesc totalurile calculate automat din donațiile Stripe.</p>';
    }

    public static function sanitize_settings($input): array
    {
        $defaults = self::get_default_settings();
        $input = is_array($input) ? $input : [];

        $webhook_secret = sanitize_text_field((string) ($input['webhook_secret'] ?? ''));

        $one_time_defaults = $defaults['payment_links']['one_time'];
        $one_time = [];
        foreach ($one_time_defaults as $key => $value) {
            $raw = $input['payment_links']['one_time'][$key] ?? '';
            $one_time[$key] = esc_url_raw((string) $raw);
        }

        $monthly_defaults = $defaults['payment_links']['monthly'];
        $monthly = [];
        foreach ($monthly_defaults as $key => $value) {
            $raw = $input['payment_links']['monthly'][$key] ?? '';
            $monthly[$key] = esc_url_raw((string) $raw);
        }

        $material_keys = array_keys(self::get_default_settings()['materials_current']);
        $materials_current = [];
        foreach ($material_keys as $key) {
            $raw = $input['materials_current'][$key] ?? 0;
            $materials_current[$key] = max(0.0, (float) $raw);
        }

        return [
            'webhook_secret' => $webhook_secret,
            'payment_links' => [
                'one_time' => $one_time,
                'monthly' => $monthly,
            ],
            'materials_current' => $materials_current,
        ];
    }

    public static function get_settings(): array
    {
        $defaults = self::get_default_settings();
        $saved = get_option(self::SETTINGS_OPTION, []);

        if (!is_array($saved)) {
            $saved = [];
        }

        $settings = wp_parse_args($saved, $defaults);

        if (!is_array($settings['payment_links'] ?? null)) {
            $settings['payment_links'] = $defaults['payment_links'];
        }

        $settings['payment_links']['one_time'] = wp_parse_args(
            is_array($settings['payment_links']['one_time'] ?? null) ? $settings['payment_links']['one_time'] : [],
            $defaults['payment_links']['one_time']
        );

        $settings['payment_links']['monthly'] = wp_parse_args(
            is_array($settings['payment_links']['monthly'] ?? null) ? $settings['payment_links']['monthly'] : [],
            $defaults['payment_links']['monthly']
        );

        $settings['webhook_secret'] = sanitize_text_field((string) ($settings['webhook_secret'] ?? ''));

        $settings['materials_current'] = wp_parse_args(
            is_array($settings['materials_current'] ?? null) ? $settings['materials_current'] : [],
            $defaults['materials_current']
        );

        return $settings;
    }

    public static function get_webhook_secret(): string
    {
        $settings = self::get_settings();
        return (string) ($settings['webhook_secret'] ?? '');
    }

    public static function get_payment_link(string $group, string $key): string
    {
        $settings = self::get_settings();
        return (string) ($settings['payment_links'][$group][$key] ?? '#');
    }

    public static function get_material_current(string $key): float
    {
        $settings = self::get_settings();
        return (float) ($settings['materials_current'][$key] ?? 0.0);
    }

    public static function render_material_field(array $args): void
    {
        $key = (string) ($args['key'] ?? '');
        $value = self::get_material_current($key);

        printf(
            '<input type="number" step="0.01" min="0" class="small-text" name="%1$s[materials_current][%2$s]" value="%3$s" />',
            esc_attr(self::SETTINGS_OPTION),
            esc_attr($key),
            esc_attr((string) $value)
        );
    }

    public static function render_settings_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        ?>
        <div class="wrap">
            <h1>Casa Justin Parvu Donations</h1>
            <p>Configurează Stripe Webhook Secret și linkurile Stripe Payment Links utilizate în shortcode-ul <code>[donations_page]</code>.</p>

            <form action="options.php" method="post">
                <?php
                settings_fields(self::SETTINGS_GROUP);
                do_settings_sections('cjp-donations');
                submit_button('Salvează setările');
                ?>
            </form>
        </div>
        <?php
    }

    public static function render_webhook_secret_field(): void
    {
        $settings = self::get_settings();
        $value = (string) ($settings['webhook_secret'] ?? '');

        printf(
            '<input type="password" class="regular-text" name="%1$s[webhook_secret]" value="%2$s" autocomplete="off" />',
            esc_attr(self::SETTINGS_OPTION),
            esc_attr($value)
        );

        echo '<p class="description">Ex: whsec_... (folosit pentru verificarea semnăturii Stripe webhook).</p>';
    }

    public static function render_link_field(array $args): void
    {
        $path = (string) ($args['path'] ?? '');
        $placeholder = (string) ($args['placeholder'] ?? 'https://buy.stripe.com/...');

        $parts = explode('.', $path);
        if (count($parts) !== 3) {
            return;
        }

        [$root, $group, $key] = $parts;
        if ($root !== 'payment_links') {
            return;
        }

        $value = self::get_payment_link($group, $key);

        printf(
            '<input type="url" class="regular-text code" name="%1$s[payment_links][%2$s][%3$s]" value="%4$s" placeholder="%5$s" />',
            esc_attr(self::SETTINGS_OPTION),
            esc_attr($group),
            esc_attr($key),
            esc_attr($value),
            esc_attr($placeholder)
        );
    }

    private static function get_default_settings(): array
    {
        return [
            'webhook_secret' => '',
            'payment_links' => [
                'one_time' => [
                    'beton_fundatie' => '#',
                    'bca' => '#',
                    'manopera' => '#',
                    'fier' => '#',
                    'custom_amount' => '#',
                ],
                'monthly' => [
                    'monthly_10' => '#',
                    'monthly_25' => '#',
                    'monthly_50' => '#',
                    'monthly_100' => '#',
                ],
            ],
            'materials_current' => [
                'beton_fundatie'       => 0.0,
                'beton_suprastructura' => 0.0,
                'bca'                  => 0.0,
                'manopera'             => 0.0,
                'lemn_acoperis'        => 0.0,
            ],
        ];
    }
}
