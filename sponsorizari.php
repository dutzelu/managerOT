<?php
include "includes/conexiune.php";
include "includes/functii.php";
include "includes/date-asociatie.php";

$titlu_pg = "Sponsorizări pentru firme - " . ASOC_DENUMIRE;
$descriere_pg = "Direcționează gratuit o parte din impozitul firmei tale către " . ASOC_DENUMIRE . ". Generează online contractul de sponsorizare în mai puțin de un minut.";
$url_pagina = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$imagine_share = "https://" . $_SERVER['HTTP_HOST'] . "/managerot/includes/logo-OT.png";

setlocale(LC_ALL, 'ro_RO.UTF-8');

// --- Mesaje succes / eroare (pattern PRG, după generarea contractului) ---
$contract_succes = $_GET['succes'] ?? '';
$eroare = $_GET['eroare'] ?? '';
$pdf_generat = $_GET['pdf'] ?? '';

// tipul sponsorizării, setat de butonul „Generează" de pe carduri (sau păstrat după o eroare de validare)
$tip_preselectat = $_GET['tip'] ?? 'sponsorizare20';
if (!in_array($tip_preselectat, ['directionare177', 'sponsorizare20'], true)) {
    $tip_preselectat = 'sponsorizare20';
}
$eticheta_tip = ($tip_preselectat === 'directionare177') ? 'Direcționare 177' : 'Sponsorizare directă 20%';

// formularul e ascuns până când vizitatorul apasă „Generează contractul";
// rămâne vizibil când revine cu mesaj de succes sau de eroare
$formular_vizibil = (!empty($contract_succes) || !empty($eroare));

// acceptăm doar fișiere din directorul nostru de contracte, existente pe disc
if (!empty($pdf_generat)) {
    $pdf_generat = str_replace('\\', '/', $pdf_generat);
    if (strpos($pdf_generat, 'contracte-sponsorizare/') !== 0 || strpos($pdf_generat, '..') !== false || !file_exists($pdf_generat)) {
        $pdf_generat = '';
    }
}

// --- Documente statice disponibile pentru descărcare ---
// modelele de contract se afișează doar dacă fișierele există;
// Declarația 177 e încărcată direct pe server, la rădăcina aplicației
$doc_model_pdf  = 'documente/model-contract-sponsorizare.pdf';
$doc_model_docx = 'documente/model-contract-sponsorizare.docx';
$doc_declaratia_177 = 'Declaratia-177.pdf';
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titlu_pg; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">

    <meta name="title" content="<?php echo $titlu_pg; ?>">
    <meta name="description" content="<?php echo $descriere_pg; ?>">

    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $url_pagina; ?>">
    <meta property="og:title" content="<?php echo $titlu_pg; ?>">
    <meta property="og:description" content="<?php echo $descriere_pg; ?>">
    <meta property="og:image" content="<?php echo $imagine_share; ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo $url_pagina; ?>">
    <meta property="twitter:title" content="<?php echo $titlu_pg; ?>">
    <meta property="twitter:description" content="<?php echo $descriere_pg; ?>">
    <meta property="twitter:image" content="<?php echo $imagine_share; ?>">

    <style>
        :root { --ot-blue: #0d47a1; --ot-blue-light: #1565c0; --ot-gold: #fbc02d; --bg-light: #f4f7f9; }
        body { background-color: var(--bg-light); font-family: 'Inter', sans-serif; color: #333; }

        /* Header */
        .header-centrat { background: #fff; padding: 50px 0 60px; border-bottom: 1px solid #e0e0e0; }
        .logo-img-large { max-height: 150px; width: auto; transition: transform 0.3s; }
        .logo-img-large:hover { transform: scale(1.05); }
        .hero-box { max-width: 820px; margin: 0 auto; text-align: center; }
        .badge-firme { background: #e8f0fe; color: var(--ot-blue); font-weight: 800; letter-spacing: 2px; font-size: 0.75rem; padding: 8px 18px; border-radius: 50px; text-transform: uppercase; }
        .divider-custom { height: 4px; width: 60px; background: var(--ot-blue); margin: 20px auto; border-radius: 2px; }

        /* Feature boxes */
        .feature-box { background: #fff; padding: 30px; border-radius: 20px; border: 1px solid #edf2f7; height: 100%; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
        .icon-circle { width: 60px; height: 60px; border-radius: 18px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; font-size: 1.5rem; }

        /* Carduri sponsorizare */
        .sponsor-card { background: #fff; border-radius: 22px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.08); height: 100%; display: flex; flex-direction: column; }
        .sponsor-card-header { padding: 28px 30px; color: #fff; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .header-blue { background: linear-gradient(135deg, #0d47a1 0%, #1976d2 100%); }
        .header-gold { background: linear-gradient(135deg, #b45309 0%, #f59e0b 100%); }
        .deadline-pill { background: rgba(255,255,255,0.18); border: 1px solid rgba(255,255,255,0.35); border-radius: 14px; padding: 8px 16px; text-align: center; line-height: 1.2; }
        .deadline-pill small { display: block; font-size: 0.68rem; opacity: 0.9; }
        .deadline-pill strong { font-size: 1.05rem; }
        .sponsor-card-body { padding: 30px; flex-grow: 1; display: flex; flex-direction: column; }

        /* Pași */
        .step-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; margin: 25px 0; }
        @media (max-width: 480px) { .step-grid { grid-template-columns: 1fr; } }
        .step-item { text-align: center; }
        .step-icon { width: 64px; height: 64px; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; font-size: 1.6rem; position: relative; }
        .step-icon-blue { background: #e8f0fe; color: var(--ot-blue); }
        .step-icon-gold { background: #fef3c7; color: #b45309; }
        .step-nr { position: absolute; top: -8px; right: -8px; width: 24px; height: 24px; border-radius: 50%; background: var(--ot-blue); color: #fff; font-size: 0.7rem; font-weight: 800; display: flex; align-items: center; justify-content: center; }
        .step-nr-gold { background: #b45309; }
        .step-item p { font-size: 0.86rem; color: #475569; margin: 0; font-weight: 600; }

        .btn-ot { background: var(--ot-blue); color: #fff; font-weight: 700; border-radius: 12px; padding: 14px 20px; border: 2px solid var(--ot-blue); }
        .btn-ot:hover { background: var(--ot-blue-light); border-color: var(--ot-blue-light); color: #fff; }
        .btn-ot-outline { background: #fff; color: var(--ot-blue); font-weight: 700; border-radius: 12px; padding: 13px 20px; border: 2px solid var(--ot-blue); }
        .btn-ot-outline:hover { background: #e8f0fe; color: var(--ot-blue); }
        .btn-gold { background: #b45309; color: #fff; font-weight: 700; border-radius: 12px; padding: 14px 20px; border: 2px solid #b45309; }
        .btn-gold:hover { background: #92400e; border-color: #92400e; color: #fff; }
        .btn-gold-outline { background: #fff; color: #b45309; font-weight: 700; border-radius: 12px; padding: 13px 20px; border: 2px solid #b45309; }
        .btn-gold-outline:hover { background: #fef3c7; color: #92400e; }

        /* Formular */
        .form-card { background: #fff; border-radius: 22px; border: 1px solid #e2e8f0; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.08); overflow: hidden; }
        .form-card-header { background: linear-gradient(135deg, #0d47a1 0%, #1976d2 100%); color: #fff; padding: 30px; }
        .form-label { font-weight: 700; font-size: 0.85rem; color: #334155; }
        .form-control, .form-select { border-radius: 10px; padding: 11px 14px; border-color: #cbd5e1; }
        .form-control:focus { border-color: var(--ot-blue); box-shadow: 0 0 0 0.2rem rgba(13,71,161,0.12); }
        .honey { position: absolute; left: -9999px; opacity: 0; height: 0; overflow: hidden; }

        /* Badge tip sponsorizare în antetul formularului */
        .tip-badge { background: rgba(255,255,255,0.18); border: 1px solid rgba(255,255,255,0.4); border-radius: 50px; padding: 6px 16px; font-weight: 700; font-size: 0.85rem; display: inline-block; }

        /* Bancar / Copy */
        .copy-group { background: #f1f5f9; border-radius: 12px; padding: 15px; margin-bottom: 10px; border: 1px solid #e2e8f0; }
        .copy-group label { display: block; font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 5px; }
        .copy-val { font-family: 'Monaco', 'Consolas', monospace; font-weight: 700; color: #0f172a; font-size: 1.0rem; }

        .section-title { font-weight: 800; }
        .legal-note { background: #fffbeb; border: 1px solid #fde68a; border-radius: 14px; padding: 18px 22px; font-size: 0.88rem; color: #78350f; }
    </style>
</head>
<body>

<header class="header-centrat">
    <div class="container text-center">
        <img src="includes/logo-OT.png" alt="Logo <?php echo ASOC_DENUMIRE; ?>" class="logo-img-large mb-4">
        <div class="hero-box">
            <span class="badge-firme"><i class="bi bi-buildings me-1"></i> Pentru companii</span>
            <h1 class="fw-bold text-dark mt-4" style="font-weight:800;">Sponsorizează din impozit, <span style="color:var(--ot-blue)">nu din buzunar</span></h1>
            <p class="lead text-secondary mt-3">
                Firma ta poate direcționa, <strong>fără niciun cost suplimentar</strong>, o parte din impozitul datorat statului
                către proiectele social-filantropice ale Asociației Ortodoxia Tinerilor.
                Generezi contractul de sponsorizare online, în mai puțin de un minut.
            </p>
            <div class="divider-custom"></div>
            <a href="#alege-varianta" class="btn btn-ot btn-lg px-5 shadow-sm mt-2">
                <i class="bi bi-file-earmark-text me-2"></i>Generează contractul de sponsorizare
            </a>
        </div>
    </div>
</header>

<div class="container py-5">

    <!-- Beneficii -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="feature-box">
                <div class="icon-circle" style="background: #ebf8ff; color: #2b6cb0;"><i class="bi bi-piggy-bank-fill"></i></div>
                <h5 class="fw-bold">Zero costuri pentru firmă</h5>
                <p class="text-muted small mb-0">Suma sponsorizată se scade direct din impozitul pe profit datorat statului. Banii ajung la cei aflați în nevoie, nu în plus față de taxele pe care oricum le plătești.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-box">
                <div class="icon-circle" style="background: #f0fff4; color: #2f855a;"><i class="bi bi-shield-check"></i></div>
                <h5 class="fw-bold">100% legal și transparent</h5>
                <p class="text-muted small mb-0">Mecanism reglementat prin Legea nr. 32/1994 privind sponsorizarea și art. 25 alin. (4) lit. i) din Codul fiscal. Vezi oricând cum folosim fondurile pe <a href="donatii-publice.php" class="fw-bold">pagina de transparență</a>.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-box">
                <div class="icon-circle" style="background: #fff5f5; color: #c53030;"><i class="bi bi-heart-fill"></i></div>
                <h5 class="fw-bold">Impact direct</h5>
                <p class="text-muted small mb-0">Sprijinim familii nevoiașe, bolnavi și bătrâni, proiecte educaționale și culturale pentru tineri. Fiecare leu direcționat ajunge la un caz real, documentat.</p>
            </div>
        </div>
    </div>

    <!-- Cele două mecanisme -->
    <h4 id="alege-varianta" class="section-title mb-4"><i class="bi bi-signpost-split-fill text-primary me-2"></i>Alege varianta potrivită firmei tale</h4>
    <div class="row g-4 mb-5">

        <!-- Card 1: Directionare 177 -->
        <div class="col-lg-6">
            <div class="sponsor-card">
                <div class="sponsor-card-header header-blue">
                    <h3 class="fw-bold mb-0">Direcționare 177</h3>
                    <div class="deadline-pill">
                        <small>disponibil până la</small>
                        <strong>25 iun 2026</strong>
                    </div>
                </div>
                <div class="sponsor-card-body">
                    <p class="text-secondary text-center">
                        Prin <strong>Declarația 177</strong>, poți solicita ANAF să redirecționeze către
                        <strong><?php echo ASOC_DENUMIRE; ?></strong> bugetul de sponsorizare rămas nealocat în 2025.
                        Suma aferentă contractului de sponsorizare ne va fi virată de către ANAF în termen de 45 de zile.
                    </p>
                    <h6 class="fw-bold text-center mt-2 mb-0">Ce trebuie să fac?</h6>
                    <div class="step-grid">
                        <div class="step-item">
                            <div class="step-icon step-icon-blue"><i class="bi bi-calculator-fill"></i><span class="step-nr">1</span></div>
                            <p>Calculează suma rămasă disponibilă</p>
                        </div>
                        <div class="step-item">
                            <div class="step-icon step-icon-blue"><i class="bi bi-pen-fill"></i><span class="step-nr">2</span></div>
                            <p>Completează contractul de sponsorizare și semnează-l</p>
                        </div>
                        <div class="step-item">
                            <div class="step-icon step-icon-blue"><i class="bi bi-folder-symlink-fill"></i><span class="step-nr">3</span></div>
                            <p>Depune Declarația 177 la ANAF până pe 25 iunie 2026</p>
                        </div>
                        <div class="step-item">
                            <div class="step-icon step-icon-blue"><i class="bi bi-bank2"></i><span class="step-nr">4</span></div>
                            <p>ANAF face plata în contul Asociației</p>
                        </div>
                    </div>
                    <div class="d-grid gap-2 mt-auto">
                        <a href="#genereaza-contract" class="btn btn-ot js-select-tip" data-tip="directionare177" data-eticheta="Direcționare 177"><i class="bi bi-magic me-2"></i>Generează contractul de sponsorizare</a>
                        <?php if (file_exists($doc_model_pdf)): ?>
                        <a href="<?php echo $doc_model_pdf; ?>" class="btn btn-ot-outline" download><i class="bi bi-download me-2"></i>Descarcă model contract (PDF)</a>
                        <?php endif; ?>
                        <a href="<?php echo $doc_declaratia_177; ?>" class="btn btn-ot-outline" download><i class="bi bi-file-earmark-arrow-down me-2"></i>Descarcă Declarația 177</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Sponsorizare 20% -->
        <div class="col-lg-6">
            <div class="sponsor-card">
                <div class="sponsor-card-header header-gold">
                    <h3 class="fw-bold mb-0">Sponsorizare 20%</h3>
                    <div class="deadline-pill">
                        <small>disponibil până la</small>
                        <strong>31 dec 2026</strong>
                    </div>
                </div>
                <div class="sponsor-card-body">
                    <p class="text-secondary text-center">
                        Poți direcționa până la <strong>20% din impozitul pe profit</strong> datorat statului în anul 2026
                        (în limita a 0,75% din cifra de afaceri) către <strong><?php echo ASOC_DENUMIRE; ?></strong>.
                        Folosește impozitul pentru o cauză nobilă și ajută la sprijinirea celor mai greu încercați ca noi.
                    </p>
                    <h6 class="fw-bold text-center mt-2 mb-0">Ce trebuie să fac?</h6>
                    <div class="step-grid">
                        <div class="step-item">
                            <div class="step-icon step-icon-gold"><i class="bi bi-calculator-fill"></i><span class="step-nr step-nr-gold">1</span></div>
                            <p>Calculează suma disponibilă din contabilitate</p>
                        </div>
                        <div class="step-item">
                            <div class="step-icon step-icon-gold"><i class="bi bi-file-earmark-text-fill"></i><span class="step-nr step-nr-gold">2</span></div>
                            <p>Completează contractul de sponsorizare</p>
                        </div>
                        <div class="step-item">
                            <div class="step-icon step-icon-gold"><i class="bi bi-pen-fill"></i><span class="step-nr step-nr-gold">3</span></div>
                            <p>Semnează contractul și trimite-ni-l pe email</p>
                        </div>
                        <div class="step-item">
                            <div class="step-icon step-icon-gold"><i class="bi bi-heart-fill"></i><span class="step-nr step-nr-gold">4</span></div>
                            <p>Virează banii în contul Asociației până la 31 decembrie 2026</p>
                        </div>
                    </div>
                    <div class="d-grid gap-2 mt-auto">
                        <a href="#genereaza-contract" class="btn btn-gold js-select-tip" data-tip="sponsorizare20" data-eticheta="Sponsorizare directă 20%"><i class="bi bi-magic me-2"></i>Generează contractul de sponsorizare</a>
                        <?php if (file_exists($doc_model_docx)): ?>
                        <a href="<?php echo $doc_model_docx; ?>" class="btn btn-gold-outline" download><i class="bi bi-file-earmark-word me-2"></i>Descarcă model contract (DOCX)</a>
                        <?php endif; ?>
                        <?php if (file_exists($doc_model_pdf)): ?>
                        <a href="<?php echo $doc_model_pdf; ?>" class="btn btn-gold-outline" download><i class="bi bi-download me-2"></i>Descarcă model contract (PDF)</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="legal-note mb-5">
        <i class="bi bi-info-circle-fill me-2"></i>
        <strong>Bază legală:</strong> sponsorizarea este reglementată de Legea nr. 32/1994 privind sponsorizarea, cu modificările și completările ulterioare,
        iar facilitățile fiscale de art. 25 alin. (4) lit. i) din Codul fiscal — suma se scade din impozitul pe profit datorat, în limita a 0,75% din cifra de afaceri,
        dar nu mai mult de 20% din impozitul pe profit. Suma exactă disponibilă o obții de la contabilul firmei.
    </div>

    <!-- Formular generare contract -->
    <div id="genereaza-contract" class="form-card mb-5 <?php echo $formular_vizibil ? '' : 'd-none'; ?>">
        <div class="form-card-header">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <h3 class="fw-bold mb-1"><i class="bi bi-file-earmark-text me-2"></i>Generează contractul de sponsorizare</h3>
                <span class="tip-badge" id="tip-badge"><?php echo $eticheta_tip; ?></span>
            </div>
            <p class="mb-0" style="opacity:0.85">Completează datele firmei și primești pe loc contractul precompletat, în format PDF, gata de semnat.</p>
        </div>
        <div class="p-4 p-md-5">

            <?php if (!empty($contract_succes)): ?>
                <div class="alert alert-success d-flex align-items-center shadow-sm rounded-3" role="alert">
                    <i class="bi bi-check-circle-fill fs-3 me-3"></i>
                    <div>
                        <strong>Contractul <?php echo htmlspecialchars($contract_succes); ?> a fost generat cu succes!</strong><br>
                        <?php if (!empty($pdf_generat)): ?>
                            Descărcarea pornește automat. Dacă nu, <a href="<?php echo htmlspecialchars($pdf_generat); ?>" id="link-pdf" class="fw-bold" download>apasă aici pentru descărcare</a>.<br>
                        <?php endif; ?>
                        După semnare și ștampilare, te rugăm să ne trimiți contractul pe <a href="mailto:<?php echo ASOC_EMAIL; ?>" class="fw-bold"><?php echo ASOC_EMAIL; ?></a>.
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($eroare)): ?>
                <div class="alert alert-danger d-flex align-items-center shadow-sm rounded-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                    <div><?php echo htmlspecialchars($eroare); ?></div>
                </div>
            <?php endif; ?>

            <form action="genereaza-contract-sponsorizare.php" method="post" autocomplete="on">

                <!-- honeypot anti-spam: câmp invizibil, dacă e completat cererea e respinsă -->
                <div class="honey" aria-hidden="true">
                    <label for="website">Website</label>
                    <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
                </div>

                <input type="hidden" name="tip_sponsorizare" id="tip_sponsorizare" value="<?php echo $tip_preselectat; ?>">

                <h6 class="fw-bold text-primary mb-3"><i class="bi bi-building me-2"></i>Datele firmei (Sponsor)</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="denumire_firma" class="form-label">Denumirea firmei *</label>
                        <input type="text" class="form-control" name="denumire_firma" id="denumire_firma" placeholder="SC Exemplu SRL" required>
                    </div>
                    <div class="col-md-3">
                        <label for="cui" class="form-label">CUI / CIF *</label>
                        <input type="text" class="form-control" name="cui" id="cui" placeholder="RO12345678" required>
                    </div>
                    <div class="col-md-3">
                        <label for="nr_reg_com" class="form-label">Nr. Reg. Comerțului *</label>
                        <input type="text" class="form-control" name="nr_reg_com" id="nr_reg_com" placeholder="J17/123/2010" required>
                    </div>
                    <div class="col-12">
                        <label for="sediu" class="form-label">Sediul social (adresa completă) *</label>
                        <input type="text" class="form-control" name="sediu" id="sediu" placeholder="Localitate, stradă, număr, județ" required>
                    </div>
                    <div class="col-md-6">
                        <label for="banca" class="form-label">Banca firmei</label>
                        <input type="text" class="form-control" name="banca" id="banca" placeholder="ex: Banca Transilvania">
                    </div>
                    <div class="col-md-6">
                        <label for="iban" class="form-label">IBAN firmă</label>
                        <input type="text" class="form-control" name="iban" id="iban" placeholder="RO00 XXXX 0000 0000 0000 0000">
                    </div>
                    <div class="col-md-6">
                        <label for="reprezentant" class="form-label">Reprezentant legal (nume și prenume) *</label>
                        <input type="text" class="form-control" name="reprezentant" id="reprezentant" placeholder="Popescu Ion" required>
                    </div>
                    <div class="col-md-6">
                        <label for="functie" class="form-label">Funcția reprezentantului *</label>
                        <input type="text" class="form-control" name="functie" id="functie" placeholder="Administrator" value="Administrator" required>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email de contact *</label>
                        <input type="email" class="form-control" name="email" id="email" placeholder="contact@firma.ro" required>
                    </div>
                    <div class="col-md-6">
                        <label for="telefon" class="form-label">Telefon</label>
                        <input type="text" class="form-control" name="telefon" id="telefon" placeholder="07xx xxx xxx">
                    </div>
                </div>

                <h6 class="fw-bold text-primary mt-4 mb-3"><i class="bi bi-cash-coin me-2"></i>Detaliile sponsorizării</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="suma" class="form-label">Suma sponsorizată (lei) *</label>
                        <input type="number" step="0.01" min="1" class="form-control" name="suma" id="suma" placeholder="0.00" required>
                    </div>
                    <div class="col-md-6">
                        <label for="data_semnarii" class="form-label">Data semnării *</label>
                        <input type="date" class="form-control" name="data_semnarii" id="data_semnarii" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <div class="form-text mt-3">
                    <i class="bi bi-lock-fill me-1"></i>
                    Datele introduse sunt folosite exclusiv pentru completarea contractului de sponsorizare și nu sunt transmise terților.
                </div>

                <div class="text-center mt-4">
                    <button type="submit" name="submit" class="btn btn-ot btn-lg px-5 shadow">
                        <i class="bi bi-file-earmark-pdf-fill me-2"></i>Generează și descarcă contractul (PDF)
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Date bancare + contact -->
    <div class="bg-white p-4 p-md-5 rounded-4 shadow-sm border mb-5">
        <h4 class="section-title mb-4"><i class="bi bi-bank text-primary me-2"></i>Datele de virament ale Asociației</h4>
        <div class="row g-3">
            <div class="col-md-12">
                <div class="copy-group d-flex justify-content-between align-items-center">
                    <div>
                        <label>Titular cont</label>
                        <span class="copy-val" id="titular"><?php echo ASOC_DENUMIRE; ?></span>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" onclick="copy('titular')"><i class="bi bi-copy"></i></button>
                </div>
            </div>
            <div class="col-md-6">
                <div class="copy-group d-flex justify-content-between align-items-center">
                    <div>
                        <label>Cont LEI (RON)</label>
                        <span class="copy-val" id="iban-asoc"><?php echo ASOC_IBAN; ?></span>
                    </div>
                    <button class="btn btn-sm btn-primary" onclick="copy('iban-asoc')"><i class="bi bi-copy"></i></button>
                </div>
            </div>
            <div class="col-md-6">
                <div class="copy-group d-flex justify-content-between align-items-center">
                    <div>
                        <label>Cod de identificare fiscală (CUI)</label>
                        <span class="copy-val" id="cui-asoc"><?php echo ASOC_CUI; ?></span>
                    </div>
                    <button class="btn btn-sm btn-primary" onclick="copy('cui-asoc')"><i class="bi bi-copy"></i></button>
                </div>
            </div>
        </div>
        <div class="text-center mt-3 small text-muted">
            Banca: <strong><?php echo ASOC_BANCA; ?></strong> • Nr. înreg. Registrul asociațiilor și fundațiilor: <strong><?php echo ASOC_NR_REGISTRU; ?></strong>
        </div>
    </div>

    <div class="text-center pb-4">
        <h5 class="fw-bold">Ai întrebări? Suntem aici să te ajutăm.</h5>
        <p class="text-secondary mb-1">
            <i class="bi bi-envelope-fill text-primary me-1"></i> <a href="mailto:<?php echo ASOC_EMAIL; ?>" class="fw-bold"><?php echo ASOC_EMAIL; ?></a>
            &nbsp;•&nbsp;
            <i class="bi bi-whatsapp text-success me-1"></i> <strong><?php echo ASOC_TELEFON; ?></strong> (<?php echo ASOC_REPREZENTANT; ?>)
        </p>
        <p class="small text-muted">Vezi cum folosim fondurile pe <a href="donatii-publice.php" class="fw-bold">pagina de transparență a donațiilor</a>.</p>
    </div>

</div>

<footer class="bg-dark text-white-50 py-5 mt-2">
    <div class="container text-center">
        <p class="small mb-0">&copy; <?php echo date("Y"); ?> <?php echo ASOC_DENUMIRE; ?> • CUI <?php echo ASOC_CUI; ?> • <?php echo ASOC_SITE; ?></p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function copy(id) {
        const text = document.getElementById(id).innerText;
        navigator.clipboard.writeText(text).then(() => {
            alert('Copiat în clipboard: ' + text);
        });
    }

    // pornește automat descărcarea contractului generat
    document.addEventListener("DOMContentLoaded", function() {
        const linkPdf = document.getElementById('link-pdf');
        if (linkPdf) {
            setTimeout(() => linkPdf.click(), 600);
        }

        // formularul apare doar la apăsarea butonului „Generează" de pe un card,
        // care setează și tipul sponsorizării (transmis printr-un câmp ascuns)
        document.querySelectorAll('.js-select-tip').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('tip_sponsorizare').value = btn.dataset.tip;
                document.getElementById('tip-badge').innerText = btn.dataset.eticheta;
                const formular = document.getElementById('genereaza-contract');
                formular.classList.remove('d-none');
                formular.scrollIntoView({ behavior: 'smooth' });
            });
        });
    });
</script>
</body>
</html>
