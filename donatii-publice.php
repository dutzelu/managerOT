<?php
include "conexiune.php";
include "functii.php";

$titlu_pg = "Transparență și Donații în timp real - Asociația Ortodoxia Tinerilor";
$descriere_pg = "Vezi în timp real cum folosim donațiile tale. Sprijin pentru centre sociale, familii nevoiașe și proiecte culturale.";
$url_pagina = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$imagine_share = "https://" . $_SERVER['HTTP_HOST'] . "/managerot/logo-OT.png"; 

setlocale(LC_ALL, 'ro_RO.UTF-8');

// --- 1. STATISTICI ANUALE (4 pe rând) ---
$sql_stats_ani = "
    SELECT 
        YEAR(data) as an, 
        SUM(suma_lei) as total_an, 
        COUNT(DISTINCT id_asistat) as beneficiari_unici 
    FROM donatii 
    WHERE YEAR(data) >= 2018 
    GROUP BY YEAR(data) 
    ORDER BY an DESC
";
$rezultat_stats = mysqli_query($conn, $sql_stats_ani);

// --- 2. FLUX DONAȚII (JOIN cu descriere_scurta) ---
$sql_detalii = "
    SELECT 
        d.ID, 
        ROUND(d.suma_lei) as suma_lei, 
        d.tip_donatie, 
        d.scop_donatie, 
        d.data,
        a.descriere_scurta
    FROM donatii d
    LEFT JOIN asistati_social a ON d.id_asistat = a.id
    WHERE d.data >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
    ORDER BY d.id DESC
";
$result_detalii = mysqli_query($conn, $sql_detalii);

// Calcul totaluri lunare
$sql_luni = "SELECT MONTH(data) as luna, YEAR(data) as an, SUM(suma_lei) as total_luna 
             FROM donatii WHERE data >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
             GROUP BY an, luna";
$rez_luni = mysqli_query($conn, $sql_luni);
$tot_luna = [];
while($r = mysqli_fetch_assoc($rez_luni)) { $tot_luna[$r['an'].'-'.$r['luna']] = $r['total_luna']; }

// --- 3. CAMPANII (pentru finalul paginii) ---
$sql_campanii = "SELECT * FROM campanii ORDER BY data_start DESC LIMIT 5";
$rez_campanii = mysqli_query($conn, $sql_campanii);

$month_names_ro = [1=>'Ianuarie', 2=>'Februarie', 3=>'Martie', 4=>'Aprilie', 5=>'Mai', 6=>'Iunie', 7=>'Iulie', 8=>'August', 9=>'Septembrie', 10=>'Octombrie', 11=>'Noiembrie', 12=>'Decembrie'];
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

    <title><?php echo $titlu_pg; ?></title>
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
        :root { --ot-blue: #0d47a1; --ot-gold: #fbc02d; --bg-light: #f4f7f9; }
        body { background-color: var(--bg-light); font-family: 'Inter', sans-serif; color: #333; }
        
        /* Header Centrat */
        .header-centrat { background: #fff; padding: 50px 0; border-bottom: 1px solid #e0e0e0; }
        .logo-img-large { max-height: 180px; width: auto; transition: transform 0.3s; }
        .logo-img-large:hover { transform: scale(1.05); }
        
        /* Intro Transparență */
        .transparency-box { max-width: 800px; margin: 0 auto; text-align: center; }
        .divider-custom { height: 4px; width: 60px; background: var(--ot-blue); margin: 20px auto; border-radius: 2px; }

        /* Feature Boxes */
        .feature-box { background: #fff; padding: 30px; border-radius: 20px; border: 1px solid #edf2f7; height: 100%; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
        .icon-circle { width: 60px; height: 60px; border-radius: 18px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; font-size: 1.5rem; }

        /* Stats 4 per row */
        .stat-card { background: #fff; border-radius: 15px; padding: 20px; text-align: center; border-bottom: 4px solid var(--ot-blue); box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        
        /* Feed Donații Modern */
        .donation-feed-card { background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); border: none; }
        .acc-btn-custom { background: #fff !important; color: #1a202c !important; font-weight: 700 !important; padding: 20px !important; }
        .acc-btn-custom:not(.collapsed) { border-bottom: 1px solid #edf2f7; }
        .donation-item { padding: 20px; border-bottom: 1px solid #f7fafc; transition: background 0.2s; }
        .donation-item:hover { background: #f8fafc; }
        .sum-badge { background: #e6fffa; color: #2c7a7b; padding: 8px 15px; border-radius: 10px; font-weight: 800; font-size: 1.1rem; border: 1px solid #b2f5ea; }
        .desc-scurta { background: #fffaf0; border-left: 4px solid #ed8936; padding: 10px 15px; border-radius: 0 8px 8px 0; font-size: 0.9rem; color: #7b341e; margin-top: 10px; }

        /* Proiecte Orizontale */
        .project-card-full { background: white; border-radius: 15px; padding: 25px; border: 1px solid #e2e8f0; height: 100%; transition: all 0.3s; }
        .project-card-full:hover { border-color: var(--ot-blue); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }

        /* Bancar / Copy */
        .copy-group { background: #f1f5f9; border-radius: 12px; padding: 15px; margin-bottom: 10px; border: 1px solid #e2e8f0; }
        .copy-group label { display: block; font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 5px; }
        .copy-val { font-family: 'Monaco', 'Consolas', monospace; font-weight: 700; color: #0f172a; font-size: 1.05rem; }
    </style>
</head>
<body>

<header class="header-centrat">
    <div class="container text-center">
        <img src="logo-OT.png" alt="Logo Asociația Ortodoxia Tinerilor" class="logo-img-large mb-4">
        <div class="d-flex justify-content-center gap-4 flex-wrap text-muted small fw-600">
            <span><i class="bi bi-geo-alt-fill text-primary"></i> Str. Frunzei nr. 5, Galați</span>
            <span><i class="bi bi-whatsapp text-success"></i> +4 0740 004 215</span>
            <span><i class="bi bi-person-badge text-warning"></i> Claudiu Balan</span>
        </div>
        
        <div class="transparency-box mt-5">
            <h2 class="fw-bold text-dark">Donațiile noastre în timp real</h2>
            <p class="lead text-secondary">
                Află în timp real cum folosim donațiile primite pentru a sprijini familii nevoiașe. 
            </p>
            <div class="divider-custom"></div>
        </div>
    </div>
</header>

<div class="container py-5">
    
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="feature-box">
                <div class="icon-circle" style="background: #ebf8ff; color: #2b6cb0;"><i class="bi bi-cpu-fill"></i></div>
                <h5 class="fw-bold">Actualizare în timp real</h5>
                <p class="text-muted small mb-0">Sistemul nostru este conectat la sistemul de administrare al asociației. Orice donație oferită beneficiarilor noștri apare imediat în lista publică, asigurând vizibilitate.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-box">
                <div class="icon-circle" style="background: #fff5f5; color: #c53030;"><i class="bi bi-heart-fill"></i></div>
                <h5 class="fw-bold">Sprijin Direct & Uman</h5>
                <p class="text-muted small mb-0">Ajutăm familiile și persoanele aflate în nevoie (sărăcie, boală, etc). Donațiile tale ajung direct la beneficiari sub formă de bani, hrană, medicamente sau plata utilităților.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-box">
                <div class="icon-circle" style="background: #f0fff4; color: #2f855a;"><i class="bi bi-hand-thumbs-up-fill"></i></div>
                <h5 class="fw-bold">Respectăm Demnitatea</h5>
                <p class="text-muted small mb-0">Protejăm identitatea persoanelor vulnerabile. Oferim detalii despre scopul ajutorului, păstrând în același timp demnitatea celor asistați, neafișând numele.</p>
            </div>
        </div>
    </div>

    <div class="mb-5">
        <h5 class="fw-bold mb-4 d-flex align-items-center"><i class="bi bi-graph-up-arrow me-2 text-primary"></i> Impactul nostru în cifre</h5>
        <div class="row row-cols-2 row-cols-md-4 g-3">
            <?php while($stat = mysqli_fetch_assoc($rezultat_stats)): ?>
            <div class="col">
                <div class="stat-card">
                    <div class="text-muted small fw-bold mb-1"><?php echo $stat['an']; ?></div>
                    <div class="h3 fw-800 text-primary mb-0"><?php echo number_format($stat['total_an'], 0, '', '.'); ?></div>
                    <div class="text-secondary" style="font-size: 0.7rem;">LEI DONAȚI / <?php echo $stat['beneficiari_unici']; ?> CAZURI</div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div class="mb-5">
        <div class="donation-feed-card shadow-lg">
            <div class="p-4 bg-white border-bottom">
                <h4 class="fw-800 m-0"><i class="bi bi-clock-history me-2 text-primary"></i> Flux Donații în Timp Real</h4>
                <p class="text-muted small m-0">Lista ultimelor donații înregistrate în ultimele 12 luni</p>
            </div>
            <div class="accordion accordion-flush" id="donatiiAcc">
                <?php
                $i = 0; $ultima_luna_an = "";
                if(mysqli_num_rows($result_detalii) > 0):
                    while ($date = mysqli_fetch_assoc($result_detalii)):
                        $luna_nume = $month_names_ro[date("n", strtotime($date['data']))] . " " . date("Y", strtotime($date['data']));
                        $luna_cheie = date("Y-n", strtotime($date['data']));
                        
                        if ($ultima_luna_an != $luna_nume):
                            if ($i > 0) echo '</div></div></div>'; 
                            $id_c = "c-" . $i;
                ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button acc-btn-custom <?php echo ($i > 0) ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $id_c; ?>">
                                    <div class="d-flex justify-content-between w-100 align-items-center pe-3">
                                        <span><i class="bi bi-calendar-check me-2 text-primary"></i><?php echo $luna_nume; ?></span>
                                        <span class="badge bg-primary rounded-pill">Total: <?php echo number_format($tot_luna[$luna_cheie] ?? 0, 0, '', '.'); ?> lei</span>
                                    </div>
                                </button>
                            </h2>
                            <div id="<?php echo $id_c; ?>" class="accordion-collapse collapse <?php echo ($i === 0) ? 'show' : ''; ?>" data-bs-parent="#donatiiAcc">
                                <div class="accordion-body p-0">
                <?php endif; $ultima_luna_an = $luna_nume; $i++; ?>
                                    <div class="donation-item">
                                        <div class="row align-items-center">
                                            <div class="col-md-9">
                                                <div class="small text-muted mb-1 fw-bold"><?php echo date("d.m.Y", strtotime($date['data'])); ?> • Beneficiar ID  #<?php echo $date['ID']; ?></div>
                                                <h6 class="fw-bold text-primary mb-1"><?php echo htmlspecialchars($date['scop_donatie']); ?></h6>
                                                <div class="small text-secondary">Tip donație: <?php echo htmlspecialchars($date['tip_donatie']); ?></div>
                                                
                                                <?php if(!empty($date['descriere_scurta'])): ?>
                                                <div class="desc-scurta">
                                                    <i class="bi bi-info-circle-fill me-1"></i> <?php echo htmlspecialchars($date['descriere_scurta']); ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-3 text-md-end mt-3 mt-md-0">
                                                <span class="sum-badge"><?php echo number_format($date['suma_lei'], 0, '', '.'); ?> lei</span>
                                            </div>
                                        </div>
                                    </div>
                <?php endwhile; ?>
                                </div>
                            </div>
                        </div>
                <?php else: ?>
                    <div class="p-5 text-center text-muted">Nu sunt înregistrări disponibile.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="py-5">
        <div class="text-center mb-5">
            <h3 class="fw-800">Proiecte & Cauze Susținute</h3>
            <p class="text-muted">Aceste proiecte sunt în plină desfășurare și necesită <strong>finanțare continuă</strong> pentru a supraviețui.</p>
        </div>
        <div class="row g-4">

            <div class="col-lg-4">
                <div class="project-card-full">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-circle mb-0 me-3 shadow-sm" style="background: #fff5f5; color: #e53e3e;"><i class="bi bi-house-heart"></i></div>
                        <h5 class="fw-bold m-0">Centrul Social "Casa Justin Pârvu"</h5>
                    </div>
                    <p class="text-secondary small">Este cel mai amplu proiect al nostru: construcția unui centru care va găzdui copii și mame aflate în dificultate. Avem nevoie de fonduri lunare pentru materiale de construcție și manoperă.</p>
                    <a href="https://casajustinparvu.ro" target="_blank" class="btn btn-outline-danger btn-sm rounded-pill px-4">Vezi stadiul construcției &rarr;</a>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="project-card-full">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-circle mb-0 me-3 shadow-sm" style="background: #f0fff4; color: #38a169;"><i class="bi bi-person-heart"></i></div>
                        <h5 class="fw-bold m-0">Sprijin Lunar: Mamă cu copii</h5>
                    </div>
                    <p class="text-secondary small">Susținem o familie cu nevoi constante. Donațiile acoperă chiria lunară, utilitățile și hrana pentru o mamă singură care luptă să își crească copiii.</p>
                    <span class="badge bg-success">Caz cu finanțare permanentă</span>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="project-card-full">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-circle mb-0 me-3 shadow-sm" style="background: #f0fff4; color: #38a169;"><i class="bi bi-person-heart"></i></div>
                        <h5 class="fw-bold m-0">Alte familii necăjite</h5>
                    </div>
                    <p class="text-secondary small">O dată sau de mai multe ori pe lună mai multe familii necăjite și persoane bolnave ne cer sprijinul pentru hrană, medicamente, cheltuieli curente, lemne de foc sau alte lucruri necesare.</p>
                    <span class="badge bg-info">Cazuri cu finanțare temporară</span>
                </div>
            </div>
             
        </div>
    </div>

    <div class="my-5 p-3 p-md-5 bg-white shadow-sm rounded-4" id="conturi">
        <h3 class="fw-800 text-center mb-5">Cum poți dona?</h3>
        
        <div class="row g-4 justify-content-center">
            <div class="col-md-4">
                <button class="btn btn-primary w-100 py-4 fw-bold shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#detaliiBanca">
                    <i class="bi bi-bank fs-3 d-block mb-2"></i> Prin Bancă
                </button>
            </div>
            <div class="col-md-4">
                <a href="https://revolut.me/claudiu4ot" target="_blank" class="btn btn-dark w-100 py-4 fw-bold shadow-sm">
                    <i class="bi bi-r-circle fs-3 d-block mb-2"></i> Prin Revolut
                </a>
            </div>
            <div class="col-md-4">
                <a href="https://www.paypal.me/ortodoxiatinerilor" target="_blank" class="btn btn-warning w-100 py-4 fw-bold shadow-sm">
                    <i class="bi bi-paypal fs-3 d-block mb-2"></i> Prin PayPal
                </a>
            </div>
        </div>

        <div class="collapse mt-4" id="detaliiBanca">
            <div class="bg-light p-4 rounded-4 border">
                <div class="row g-3">
                    <div class="col-md-12">
                        <div class="copy-group d-flex justify-content-between align-items-center">
                            <div>
                                <label>Titular Cont</label>
                                <span class="copy-val" id="titular">Asociația Ortodoxia Tinerilor</span>
                            </div>
                            <button class="btn btn-sm btn-outline-secondary" onclick="copy('titular')"><i class="bi bi-copy"></i></button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="copy-group d-flex justify-content-between align-items-center">
                            <div>
                                <label>Cont LEI (RON)</label>
                                <span class="copy-val" id="lei">RO71 UGBI 0000 7020 0130 8RON</span>
                            </div>
                            <button class="btn btn-sm btn-primary" onclick="copy('lei')"><i class="bi bi-copy"></i></button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="copy-group d-flex justify-content-between align-items-center">
                            <div>
                                <label>Cont EURO</label>
                                <span class="copy-val" id="euro">RO81 UGBI 0000 7020 0133 0EUR</span>
                            </div>
                            <button class="btn btn-sm btn-primary" onclick="copy('euro')"><i class="bi bi-copy"></i></button>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-3 small text-muted">
                    Banca: <strong>GarantiBBVA România</strong> • Revolut: <strong>@claudiu4ot</strong> sau <strong>0740 004 215</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="py-5">
        <h5 class="fw-bold mb-4"><i class="bi bi-megaphone-fill text-warning me-2"></i> Campanii de donații</h5>
        <div class="row g-3">
            <?php while($camp = mysqli_fetch_assoc($rez_campanii)): ?>
            <div class="col-md-4">
                <div class="p-3 bg-white border rounded-3 shadow-sm h-100">
                    <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($camp['nume']); ?></h6>
                    <small class="text-muted">Perioada: <?php echo date("d.m.Y", strtotime($camp['data_start'])); ?> - <?php echo ($camp['data_final'] != '0000-00-00') ? date("d.m.Y", strtotime($camp['data_final'])) : 'Prezent'; ?></small>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

</div>

<footer class="bg-dark text-white-50 py-5 mt-5">
    <div class="container text-center">
        <p class="small mb-0">&copy; <?php echo date("Y"); ?> Asociația Ortodoxia Tinerilor. Toate datele sunt preluate automat din sistemul de evidență al donațiilor.</p>
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
</script>
</body>
</html>