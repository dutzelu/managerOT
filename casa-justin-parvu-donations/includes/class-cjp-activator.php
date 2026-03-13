<?php

if (!defined('ABSPATH')) {
    exit;
}

class CJP_Activator
{
    public static function activate(): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'cjp_donations';
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            stripe_payment_id VARCHAR(191) NOT NULL,
            donor_name VARCHAR(191) NOT NULL DEFAULT '',
            amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            currency VARCHAR(10) NOT NULL DEFAULT 'RON',
            donation_type VARCHAR(100) NOT NULL DEFAULT 'general_donation',
            quantity DECIMAL(12,2) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY stripe_payment_id (stripe_payment_id),
            KEY donation_type (donation_type),
            KEY created_at (created_at)
        ) {$charset_collate};";

        dbDelta($sql);
    }
}
