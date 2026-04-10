<?php

if (!defined('ABSPATH')) {
    exit;
}

class CJP_Shortcode
{
    public static function init(): void
    {
        add_shortcode('donations_page', [self::class, 'render_shortcode']);
        add_shortcode('cjp_donation_banner', [self::class, 'render_banner']);
        add_action('wp_enqueue_scripts', [self::class, 'register_assets']);
    }

    public static function register_assets(): void
    {
        wp_register_style(
            'cjp-donations',
            CJP_DONATIONS_URL . 'assets/css/cjp-donations.css',
            [],
            CJP_DONATIONS_VERSION
        );

        wp_register_script(
            'cjp-donations',
            CJP_DONATIONS_URL . 'assets/js/cjp-donations.js',
            [],
            CJP_DONATIONS_VERSION,
            true
        );
    }

    public static function render_shortcode(): string
    {
        wp_enqueue_style('cjp-donations');
        wp_enqueue_script('cjp-donations');
        wp_enqueue_script('formular230', 'https://formular230.ro/share/44a886179', [], null, true);
        wp_localize_script('cjp-donations', 'cjpDonationsData', [
            'statsEndpoint' => esc_url_raw(rest_url('cjp/v1/stats')),
            'pollInterval' => 30000,
        ]);

        $stats = CJP_Stats::get_stats();
        $links = CJP_Stats::get_payment_links();

        $one_time_cards = $links['one_time'] ?? [];
        $monthly_cards = $links['monthly'] ?? [];

        $totals = $stats['totals'] ?? [];
        $materials = $stats['materials'] ?? [];
        $recent = $stats['recent_donations'] ?? [];

        ob_start();
        ?>
        <div class="cjp-donations-page" id="cjp-donations-page">

            <!-- 1. Security + logos -->
            <section class="cjp-section cjp-hero">
                <img class="cjp-hero-img" src="https://casajustinparvu.ro/wp-content/uploads/2024/02/img1-1536x760.png" alt="Casa Justin Pârvu" loading="eager" decoding="async" />
                <h1>Construim <span>Casa Justin Pârvu</span></h1>

                <div class="cjp-security-banner">
                    <div class="cjp-security-icon" aria-hidden="true">🔒</div>
                    <div class="cjp-security-text">
                        <strong>Donații securizate 100% prin Stripe</strong>
                        <p>Plăți protejate și procesare sigură pentru carduri și portofele digitale.</p>
                    </div>
                    <?php
                    $stripe_abs = CJP_DONATIONS_PATH . 'assets/img/payments/stripe.png';
                    if (file_exists($stripe_abs)) :
                    ?>
                    <img class="cjp-stripe-badge" src="<?php echo esc_url(CJP_DONATIONS_URL . 'assets/img/payments/stripe.png'); ?>" alt="Stripe" />
                    <?php endif; ?>
                </div>

                <div class="cjp-payment-logos" aria-label="Metode de plată disponibile">
                    <?php foreach (self::payment_logo_items() as $logo) : ?>
                        <span class="cjp-logo<?php echo !empty($logo['is_image']) ? ' cjp-logo-img' : ''; ?>">
                            <?php if (!empty($logo['is_image'])) : ?>
                                <img src="<?php echo esc_url((string) $logo['src']); ?>" alt="<?php echo esc_attr((string) $logo['name']); ?>" loading="lazy" decoding="async" />
                            <?php else : ?>
                                <?php echo esc_html((string) $logo['name']); ?>
                            <?php endif; ?>
                        </span>
                    <?php endforeach; ?>
                </div>

                <div class="cjp-transparency-banner">
                    <div class="cjp-transparency-icon" aria-hidden="true">🤝</div>
                    <div class="cjp-transparency-text">
                        <strong>Transparență și donații în timp real</strong>
                        <p>Vezi toate donațiile pe care Asociația Ortodoxia Tinerilor le-a oferit oamenilor aflați în nevoie.</p>
                    </div>
                    <a class="cjp-btn cjp-btn-transparency" href="https://ortodoxiatinerilor.ro/managerot/donatii-publice.php" target="_blank" rel="noopener">
                        Donațiile noastre →
                    </a>
                </div>
            </section>

            <!-- 2. Donații unice -->
            <section class="cjp-section">
                <h2>Donații unice</h2>
                <div class="cjp-cards-grid">
                    <?php foreach ($one_time_cards as $card) : ?>
                        <article class="cjp-card">
                            <div class="cjp-card-head">
                                <span class="cjp-card-icon" aria-hidden="true"><?php echo esc_html(self::icon_for_type((string) ($card['id'] ?? ''))); ?></span>
                                <h3><?php echo esc_html((string) ($card['title'] ?? 'Donație')); ?></h3>
                            </div>
                            <p><?php echo esc_html((string) ($card['description'] ?? '')); ?></p>
                            <?php if ((float) ($card['price'] ?? 0) > 0) : ?>
                            <div class="cjp-price"><?php echo esc_html(number_format_i18n((float) ($card['price'] ?? 0), 0)); ?> <?php echo esc_html((string) ($card['currency'] ?? 'lei')); ?></div>
                            <?php endif; ?>
                            <a class="cjp-btn" href="<?php echo esc_url((string) ($card['link'] ?? '#')); ?>" target="_blank" rel="noopener">Donează</a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- 3. Donații lunare -->
            <section class="cjp-section">
                <h2>Donații lunare</h2>
                <div class="cjp-cards-grid cjp-cards-grid-monthly">

                    <?php foreach ($monthly_cards as $card) : ?>
                        <article class="cjp-card">
                            <div class="cjp-card-head">
                                <span class="cjp-card-icon" aria-hidden="true"><?php echo esc_html(self::icon_for_type('monthly_support')); ?></span>
                                <h3><?php echo esc_html((string) ($card['title'] ?? 'Donație lunară')); ?></h3>
                            </div>
                            <a class="cjp-btn" href="<?php echo esc_url((string) ($card['link'] ?? '#')); ?>" target="_blank" rel="noopener">Susține lunar</a>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="cjp-cancel-info">
                    <span class="cjp-cancel-icon" aria-hidden="true">ℹ️</span>
                    <p>Poți anula donația lunară oricând, fără penalizări. După efectuarea plății vei primi un email de confirmare de la Stripe — folosește linkul din email sau contactează-ne la <strong>asociatia@ortodoxiatinerilor.ro</strong> ori la <strong>0740 004 215</strong> (Claudiu Bălan) și anulăm noi în locul tău.</p>
                </div>
            </section>

            <!-- 4. Donații în conturi bancare -->
            <section class="cjp-section cjp-bank-section">
                <h2>Donații în conturile noastre bancare</h2>
                <div class="cjp-bank-grid">
                    <div class="cjp-bank-item">
                        <span class="cjp-bank-label">Cont lei</span>
                        <div class="cjp-bank-row">
                            <strong>RO56 UGBI 0000 7020 1286 1RON</strong>
                            <button class="cjp-copy-btn" data-copy="RO56 UGBI 0000 7020 1286 1RON" title="Copiază">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="cjp-bank-item">
                        <span class="cjp-bank-label">Cont euro</span>
                        <div class="cjp-bank-row">
                            <strong>RO81 UGBI 0000 7020 0133 0EUR</strong>
                            <button class="cjp-copy-btn" data-copy="RO81 UGBI 0000 7020 0133 0EUR" title="Copiază">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="cjp-bank-item">
                        <span class="cjp-bank-label">Titular</span>
                        <div class="cjp-bank-row">
                            <strong>Asociația Ortodoxia Tinerilor</strong>
                            <button class="cjp-copy-btn" data-copy="Asociația Ortodoxia Tinerilor" title="Copiază">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="cjp-bank-item">
                        <span class="cjp-bank-label">Banca</span>
                        <strong>GarantiBank România</strong>
                    </div>
                    <div class="cjp-bank-item">
                        <span class="cjp-bank-label">BIC</span>
                        <strong>UGBIROBU</strong>
                    </div>
                </div>
            </section>

            <!-- 5. PayPal -->
            <section class="cjp-section cjp-wallet-section cjp-paypal-section">
                <div class="cjp-wallet-header">
                    <div class="cjp-wallet-logo cjp-paypal-logo">
                        <img src="https://casajustinparvu.ro/wp-content/uploads/2026/04/paypal_logo_icon_147252.webp" alt="PayPal" loading="lazy" />
                    </div>
                    <div class="cjp-wallet-brand-text">
                        <h2>Donează prin PayPal</h2>
                        <p>Rapid, securizat, fără cont obligatoriu</p>
                    </div>
                </div>
                <p class="cjp-wallet-desc">Acceptăm carduri de credit/debit și conturi PayPal din toată lumea. Donația ajunge direct la Asociația Ortodoxia Tinerilor.</p>
                <a class="cjp-btn cjp-btn-paypal" href="https://www.paypal.me/ortodoxiatinerilor" target="_blank" rel="noopener">
                    <img src="https://casajustinparvu.ro/wp-content/uploads/2026/04/paypal_logo_icon_147252.webp" alt="" aria-hidden="true" class="cjp-btn-logo" />
                    Donează prin PayPal
                </a>
            </section>

            <!-- 6. Revolut -->
            <section class="cjp-section cjp-wallet-section cjp-revolut-section">
                <div class="cjp-wallet-header">
                    <div class="cjp-wallet-logo cjp-revolut-logo">
                        <img src="https://casajustinparvu.ro/wp-content/uploads/2026/04/unnamed.webp" alt="Revolut" loading="lazy" />
                    </div>
                    <div class="cjp-wallet-brand-text">
                        <h2>Donează prin Revolut</h2>
                        <p>Transfer instant, fără comisioane</p>
                    </div>
                </div>
                <p class="cjp-wallet-desc">Trimite donația direct din aplicația Revolut folosind tag-ul sau numărul de telefon de mai jos.</p>
                <div class="cjp-revolut-details">
                    <div class="cjp-bank-item">
                        <span class="cjp-bank-label">Tag Revolut</span>
                        <div class="cjp-bank-row">
                            <strong>@claudiu4ot</strong>
                            <button class="cjp-copy-btn" data-copy="claudiu4ot" title="Copiază">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="cjp-bank-item">
                        <span class="cjp-bank-label">Număr de telefon</span>
                        <div class="cjp-bank-row">
                            <strong>+40 740 004 215</strong>
                            <button class="cjp-copy-btn" data-copy="+40740004215" title="Copiază">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="cjp-bank-item">
                        <span class="cjp-bank-label">Beneficiar</span>
                        <strong>Claudiu Bălan</strong>
                    </div>
                </div>
            </section>

            <!-- 7. Redirecționare 3.5% persoane fizice -->
            <section class="cjp-section cjp-redirect-section">
                <h2>Pentru persoane fizice</h2>
                <p class="cjp-redirect-intro">Redirecționează online 3.5% din impozitul tău pe venit către Asociația Ortodoxia Tinerilor pentru a construi Casa Justin Pârvu.</p>
                <p>Completează, semnează și depune formularul 230 online, într-un minut, fără drumuri la ANAF. Declarația 230 PDF generată de platformă ajunge direct în contul asociației noastre.</p>
                <div class="cjp-redirect-btn-wrap">
                    <div class="f230ro-lansare f230ro-buton rotund verde">Redirecționează 3.5%</div>
                </div>
            </section>

            <!-- 6. Redirecționare 20% firme -->
            <section class="cjp-section cjp-redirect-section">
                <h2>Pentru firme</h2>
                <p class="cjp-redirect-intro">Redirecționează 20% din impozitul anual pe profit sau venit.</p>
                <p>20% este o facilitate fiscală, care permite persoanelor juridice să susțină o cauză care se identifică cu valorile și cultura organizației, prin redirecționarea unui procent din impozitul pe venit (trimestrial) sau profit (anual). Totul se face prin completarea unui contract de sponsorizare între beneficiar (în cazul de față Asociația Ortodoxia Tinerilor) și sponsor (persoana juridică).</p>
                <p>Persoanele juridice vor plăti impozitul integral, însă un procent de 20% din suma totală poate fi redirecționat către o entitate non-profit, cum e și Asociația Ortodoxia Tinerilor.</p>
                <div class="cjp-redirect-btn-wrap">
                    <a class="cjp-btn cjp-btn-blue" href="https://casajustinparvu.ro/redirectioneaza-20-din-impozitul-anual-pe-profit-sau-venit/" target="_blank" rel="noopener">Află cum poți redirecționa 20% din impozit</a>
                </div>
            </section>

            <!-- 7. Obiective + progres donații -->
            <section class="cjp-section">
                <h2>Obiective și progres</h2>

                <div class="cjp-goals-grid">
                    <div class="cjp-goal-item">
                        <span>Fundație</span>
                        <strong><?php echo esc_html(number_format_i18n((float) ($totals['foundation_target'] ?? 20000), 0)); ?> €</strong>
                    </div>
                    <div class="cjp-goal-item">
                        <span>Structură (parter + etaj + acoperiș)</span>
                        <strong><?php echo esc_html(number_format_i18n((float) ($totals['structure_target'] ?? 80000), 0)); ?> €</strong>
                    </div>
                    <div class="cjp-goal-item">
                        <span>Lucrări interioare</span>
                        <strong><?php echo esc_html(number_format_i18n((float) ($totals['interior_target'] ?? 100000), 0)); ?> €</strong>
                    </div>
                    <div class="cjp-goal-item cjp-goal-total">
                        <span>Obiectiv total</span>
                        <strong><?php echo esc_html(number_format_i18n((float) ($totals['eur_goal'] ?? 200000), 0)); ?> €</strong>
                    </div>
                </div>

                <div class="cjp-main-progress-wrap">
                    <div class="cjp-main-progress-meta">
                        <span>Progres donații</span>
                        <strong id="cjp-eur-progress-text"><?php echo esc_html(number_format_i18n((float) ($totals['eur_raised'] ?? 0), 0)); ?> € / <?php echo esc_html(number_format_i18n((float) ($totals['eur_goal'] ?? 200000), 0)); ?> €</strong>
                        <span class="cjp-progress-pill" id="cjp-eur-progress-pill"><?php echo esc_html(number_format_i18n((float) ($totals['percent'] ?? 0), 1)); ?>%</span>
                    </div>
                    <div class="cjp-progress">
                        <div id="cjp-eur-progress-bar" class="cjp-progress-bar" style="width: <?php echo esc_attr((string) ((float) ($totals['percent'] ?? 0))); ?>%;"></div>
                    </div>
                </div>
            </section>

            <!-- 9. Donații recente -->
            <section class="cjp-section">
                <h2>Donații recente</h2>
                <ul class="cjp-recent-list" id="cjp-recent-list">
                    <?php if (empty($recent)) : ?>
                        <li class="cjp-empty">Încă nu există donații înregistrate.</li>
                    <?php else : ?>
                        <?php foreach ($recent as $donation) : ?>
                            <?php
                                $currency_raw = strtoupper((string) ($donation['currency'] ?? 'RON'));
                                $currency_display = ($currency_raw === 'EUR') ? '€' : $currency_raw;
                            ?>
                            <li class="cjp-recent-item">
                                <div class="cjp-recent-donor">
                                    <strong><?php echo esc_html((string) ($donation['donor_name'] ?? 'Donator anonim')); ?></strong>
                                    <span class="cjp-recent-date"><?php echo esc_html(date_i18n('d.m.Y', strtotime((string) ($donation['created_at'] ?? 'now')))); ?></span>
                                </div>
                                <strong class="cjp-recent-amount"><?php echo esc_html(number_format_i18n((float) ($donation['amount'] ?? 0), 0)); ?> <?php echo esc_html($currency_display); ?></strong>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </section>

        </div>
        <?php

        return (string) ob_get_clean();
    }

    public static function render_banner(): string
    {
        wp_enqueue_style('cjp-donations');
        wp_enqueue_script('cjp-donations');
        wp_enqueue_script('formular230', 'https://formular230.ro/share/44a886179', [], null, true);
        wp_localize_script('cjp-donations', 'cjpDonationsData', [
            'statsEndpoint' => esc_url_raw(rest_url('cjp/v1/stats')),
            'pollInterval'  => 30000,
        ]);

        $stats = CJP_Stats::get_stats();
        $links = CJP_Stats::get_payment_links();

        $one_time_cards = $links['one_time'] ?? [];
        $monthly_cards  = $links['monthly']  ?? [];

        ob_start();
        ?>
        <div class="cjp-donations-page" id="cjp-donations-banner">

            <!-- 1. Security + logos -->
            <section class="cjp-section cjp-hero">
                <img class="cjp-hero-img" src="https://casajustinparvu.ro/wp-content/uploads/2024/02/img1-1536x760.png" alt="Casa Justin Pârvu" loading="eager" decoding="async" />
                <h1>Construim <span>Casa Justin Pârvu</span></h1>

                <div class="cjp-security-banner">
                    <div class="cjp-security-icon" aria-hidden="true">🔒</div>
                    <div class="cjp-security-text">
                        <strong>Donații securizate 100% prin Stripe</strong>
                        <p>Plăți protejate și procesare sigură pentru carduri și portofele digitale.</p>
                    </div>
                    <?php
                    $stripe_abs = CJP_DONATIONS_PATH . 'assets/img/payments/stripe.png';
                    if (file_exists($stripe_abs)) :
                    ?>
                    <img class="cjp-stripe-badge" src="<?php echo esc_url(CJP_DONATIONS_URL . 'assets/img/payments/stripe.png'); ?>" alt="Stripe" />
                    <?php endif; ?>
                </div>

                <div class="cjp-payment-logos" aria-label="Metode de plată disponibile">
                    <?php foreach (self::payment_logo_items() as $logo) : ?>
                        <span class="cjp-logo<?php echo !empty($logo['is_image']) ? ' cjp-logo-img' : ''; ?>">
                            <?php if (!empty($logo['is_image'])) : ?>
                                <img src="<?php echo esc_url((string) $logo['src']); ?>" alt="<?php echo esc_attr((string) $logo['name']); ?>" loading="lazy" decoding="async" />
                            <?php else : ?>
                                <?php echo esc_html((string) $logo['name']); ?>
                            <?php endif; ?>
                        </span>
                    <?php endforeach; ?>
                </div>

                <div class="cjp-transparency-banner">
                    <div class="cjp-transparency-icon" aria-hidden="true">🤝</div>
                    <div class="cjp-transparency-text">
                        <strong>Transparență și donații în timp real</strong>
                        <p>Vezi toate donațiile pe care Asociația Ortodoxia Tinerilor le-a oferit oamenilor aflați în nevoie.</p>
                    </div>
                    <a class="cjp-btn cjp-btn-transparency" href="https://ortodoxiatinerilor.ro/managerot/donatii-publice.php" target="_blank" rel="noopener">
                        Donațiile noastre →
                    </a>
                </div>
            </section>

            <!-- 2. Donații unice -->
            <section class="cjp-section">
                <h2>Donații unice</h2>
                <div class="cjp-cards-grid">
                    <?php foreach ($one_time_cards as $card) : ?>
                        <article class="cjp-card">
                            <div class="cjp-card-head">
                                <span class="cjp-card-icon" aria-hidden="true"><?php echo esc_html(self::icon_for_type((string) ($card['id'] ?? ''))); ?></span>
                                <h3><?php echo esc_html((string) ($card['title'] ?? 'Donație')); ?></h3>
                            </div>
                            <p><?php echo esc_html((string) ($card['description'] ?? '')); ?></p>
                            <?php if ((float) ($card['price'] ?? 0) > 0) : ?>
                            <div class="cjp-price"><?php echo esc_html(number_format_i18n((float) ($card['price'] ?? 0), 0)); ?> <?php echo esc_html((string) ($card['currency'] ?? 'lei')); ?></div>
                            <?php endif; ?>
                            <a class="cjp-btn" href="<?php echo esc_url((string) ($card['link'] ?? '#')); ?>" target="_blank" rel="noopener">Donează</a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- 3. Donații lunare -->
            <section class="cjp-section">
                <h2>Donații lunare</h2>
                <div class="cjp-cards-grid cjp-cards-grid-monthly">
                    <?php foreach ($monthly_cards as $card) : ?>
                        <article class="cjp-card">
                            <div class="cjp-card-head">
                                <span class="cjp-card-icon" aria-hidden="true"><?php echo esc_html(self::icon_for_type('monthly_support')); ?></span>
                                <h3><?php echo esc_html((string) ($card['title'] ?? 'Donație lunară')); ?></h3>
                            </div>
                            <a class="cjp-btn" href="<?php echo esc_url((string) ($card['link'] ?? '#')); ?>" target="_blank" rel="noopener">Susține lunar</a>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="cjp-cancel-info">
                    <span class="cjp-cancel-icon" aria-hidden="true">ℹ️</span>
                    <p>Poți anula donația lunară oricând, fără penalizări. După efectuarea plății vei primi un email de confirmare de la Stripe — folosește linkul din email sau contactează-ne la <strong>asociatia@ortodoxiatinerilor.ro</strong> ori la <strong>0740 004 215</strong> (Claudiu Bălan) și anulăm noi în locul tău.</p>
                </div>
            </section>

            <!-- 4. Donații în conturi bancare -->
            <section class="cjp-section cjp-bank-section">
                <h2>Donații în conturile noastre bancare</h2>
                <div class="cjp-bank-grid">
                    <div class="cjp-bank-item">
                        <span class="cjp-bank-label">Cont lei</span>
                        <div class="cjp-bank-row">
                            <strong>RO56 UGBI 0000 7020 1286 1RON</strong>
                            <button class="cjp-copy-btn" data-copy="RO56 UGBI 0000 7020 1286 1RON" title="Copiază">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="cjp-bank-item">
                        <span class="cjp-bank-label">Cont euro</span>
                        <div class="cjp-bank-row">
                            <strong>RO81 UGBI 0000 7020 0133 0EUR</strong>
                            <button class="cjp-copy-btn" data-copy="RO81 UGBI 0000 7020 0133 0EUR" title="Copiază">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="cjp-bank-item">
                        <span class="cjp-bank-label">Titular</span>
                        <div class="cjp-bank-row">
                            <strong>Asociația Ortodoxia Tinerilor</strong>
                            <button class="cjp-copy-btn" data-copy="Asociația Ortodoxia Tinerilor" title="Copiază">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="cjp-bank-item">
                        <span class="cjp-bank-label">Banca</span>
                        <strong>GarantiBank România</strong>
                    </div>
                    <div class="cjp-bank-item">
                        <span class="cjp-bank-label">BIC</span>
                        <strong>UGBIROBU</strong>
                    </div>
                </div>
            </section>

            <!-- 5. PayPal -->
            <section class="cjp-section cjp-wallet-section cjp-paypal-section">
                <div class="cjp-wallet-header">
                    <div class="cjp-wallet-logo cjp-paypal-logo">
                        <img src="https://casajustinparvu.ro/wp-content/uploads/2026/04/paypal_logo_icon_147252.webp" alt="PayPal" loading="lazy" />
                    </div>
                    <div class="cjp-wallet-brand-text">
                        <h2>Donează prin PayPal</h2>
                        <p>Rapid, securizat, fără cont obligatoriu</p>
                    </div>
                </div>
                <p class="cjp-wallet-desc">Acceptăm carduri de credit/debit și conturi PayPal din toată lumea. Donația ajunge direct la Asociația Ortodoxia Tinerilor.</p>
                <a class="cjp-btn cjp-btn-paypal" href="https://www.paypal.me/ortodoxiatinerilor" target="_blank" rel="noopener">
                    <img src="https://casajustinparvu.ro/wp-content/uploads/2026/04/paypal_logo_icon_147252.webp" alt="" aria-hidden="true" class="cjp-btn-logo" />
                    Donează prin PayPal
                </a>
            </section>

            <!-- 6. Revolut -->
            <section class="cjp-section cjp-wallet-section cjp-revolut-section">
                <div class="cjp-wallet-header">
                    <div class="cjp-wallet-logo cjp-revolut-logo">
                        <img src="https://casajustinparvu.ro/wp-content/uploads/2026/04/unnamed.webp" alt="Revolut" loading="lazy" />
                    </div>
                    <div class="cjp-wallet-brand-text">
                        <h2>Donează prin Revolut</h2>
                        <p>Transfer instant, fără comisioane</p>
                    </div>
                </div>
                <p class="cjp-wallet-desc">Trimite donația direct din aplicația Revolut folosind tag-ul sau numărul de telefon de mai jos.</p>
                <div class="cjp-revolut-details">
                    <div class="cjp-bank-item">
                        <span class="cjp-bank-label">Tag Revolut</span>
                        <div class="cjp-bank-row">
                            <strong>@claudiu4ot</strong>
                            <button class="cjp-copy-btn" data-copy="claudiu4ot" title="Copiază">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="cjp-bank-item">
                        <span class="cjp-bank-label">Număr de telefon</span>
                        <div class="cjp-bank-row">
                            <strong>+40 740 004 215</strong>
                            <button class="cjp-copy-btn" data-copy="+40740004215" title="Copiază">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="cjp-bank-item">
                        <span class="cjp-bank-label">Beneficiar</span>
                        <strong>Claudiu Bălan</strong>
                    </div>
                </div>
            </section>

            <!-- 7. Redirecționare 3.5% persoane fizice -->
            <section class="cjp-section cjp-redirect-section">
                <h2>Pentru persoane fizice</h2>
                <p class="cjp-redirect-intro">Redirecționează online 3.5% din impozitul tău pe venit către Asociația Ortodoxia Tinerilor pentru a construi Casa Justin Pârvu.</p>
                <p>Completează, semnează și depune formularul 230 online, într-un minut, fără drumuri la ANAF. Declarația 230 PDF generată de platformă ajunge direct în contul asociației noastre.</p>
                <div class="cjp-redirect-btn-wrap">
                    <div class="f230ro-lansare f230ro-buton rotund verde">Redirecționează 3.5%</div>
                </div>
            </section>

            <!-- 8. Redirecționare 20% firme -->
            <section class="cjp-section cjp-redirect-section">
                <h2>Pentru firme</h2>
                <p class="cjp-redirect-intro">Redirecționează 20% din impozitul anual pe profit sau venit.</p>
                <p>20% este o facilitate fiscală, care permite persoanelor juridice să susțină o cauză care se identifică cu valorile și cultura organizației, prin redirecționarea unui procent din impozitul pe venit (trimestrial) sau profit (anual). Totul se face prin completarea unui contract de sponsorizare între beneficiar (în cazul de față Asociația Ortodoxia Tinerilor) și sponsor (persoana juridică).</p>
                <p>Persoanele juridice vor plăti impozitul integral, însă un procent de 20% din suma totală poate fi redirecționat către o entitate non-profit, cum e și Asociația Ortodoxia Tinerilor.</p>
                <div class="cjp-redirect-btn-wrap">
                    <a class="cjp-btn cjp-btn-blue" href="https://casajustinparvu.ro/redirectioneaza-20-din-impozitul-anual-pe-profit-sau-venit/" target="_blank" rel="noopener">Află cum poți redirecționa 20% din impozit</a>
                </div>
            </section>

        </div>
        <?php

        return (string) ob_get_clean();
    }

    private static function icon_for_type(string $type): string
    {
        $map = [
            'beton_fundatie' => '🧱',
            'bca' => '🏗️',
            'manopera' => '👷',
            'fier' => '🔩',
            'custom_amount' => '🤍',
            'monthly_support' => '💙',
        ];

        return (string) ($map[$type] ?? '🙏');
    }

    private static function payment_logo_items(): array
    {
        $files = [
            'Apple Pay'  => 'apple-pay.png',
            'Google Pay' => 'google-pay.png',
            'Revolut'    => 'revolut.png',
            'PayPal'     => 'paypal.png',
            'Mastercard' => 'mastercard.png',
            'Visa'       => 'visa.png',
        ];

        $items = [];
        foreach ($files as $name => $file) {
            $abs = CJP_DONATIONS_PATH . 'assets/img/payments/' . $file;
            $exists = file_exists($abs);

            $items[] = [
                'name' => $name,
                'is_image' => $exists,
                'src' => $exists ? CJP_DONATIONS_URL . 'assets/img/payments/' . $file : '',
            ];
        }

        return $items;
    }
}
