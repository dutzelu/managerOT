<?php 
$titlu_pg = "Tablou de Bord (Dashboard) - Statistici Donații";
include "header.php";

// --- LOGICA PHP PENTRU EXTRAGEREA DATELOR ---

// 1. Totalul Donațiilor pe Ultimii 5 Ani
$stmt_ani = $conn->prepare("
    SELECT YEAR(data) AS an, ROUND(SUM(suma_lei)) AS total_an
    FROM donatii
    WHERE data >= DATE_SUB(CURDATE(), INTERVAL 5 YEAR)
    GROUP BY an ORDER BY an DESC
");
$stmt_ani->execute();
$result_ani = $stmt_ani->get_result();
$date_ani = [];
while ($row = $result_ani->fetch_assoc()) {
    $date_ani[$row['an']] = $row['total_an'];
}
$ani = json_encode(array_keys($date_ani));
$sume_ani = json_encode(array_values($date_ani));

// 2. Evoluția Lunară (Anul Curent vs. Anul Anterior)
$an_curent = date('Y');
$an_anterior = date('Y') - 1;

// Date lunar curente
$stmt_lunar_curent = $conn->prepare("
    SELECT MONTH(data) AS luna, ROUND(SUM(suma_lei)) AS total_luna
    FROM donatii WHERE YEAR(data) = ? GROUP BY luna ORDER BY luna
");
$stmt_lunar_curent->bind_param("s", $an_curent);
$stmt_lunar_curent->execute();
$result_lunar_curent = $stmt_lunar_curent->get_result();
$lunar_curent = array_fill(1, 12, 0); // Array cu 12 elemente inițializate cu 0
while ($row = $result_lunar_curent->fetch_assoc()) {
    $lunar_curent[(int)$row['luna']] = $row['total_luna'];
}

// Date lunar anterior
$stmt_lunar_anterior = $conn->prepare("
    SELECT MONTH(data) AS luna, ROUND(SUM(suma_lei)) AS total_luna
    FROM donatii WHERE YEAR(data) = ? GROUP BY luna ORDER BY luna
");
$stmt_lunar_anterior->bind_param("s", $an_anterior);
$stmt_lunar_anterior->execute();
$result_lunar_anterior = $stmt_lunar_anterior->get_result();
$lunar_anterior = array_fill(1, 12, 0);
while ($row = $result_lunar_anterior->fetch_assoc()) {
    $lunar_anterior[(int)$row['luna']] = $row['total_luna'];
}

$lunar_labels = json_encode(['Ian', 'Feb', 'Mar', 'Apr', 'Mai', 'Iun', 'Iul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']);
$lunar_curent_json = json_encode(array_values($lunar_curent));
$lunar_anterior_json = json_encode(array_values($lunar_anterior));

// 3, 7, 8. Statistici Generale
$stmt_general = $conn->query("
    SELECT 
        ROUND(SUM(suma_lei)) AS total_general,
        COUNT(ID) AS nr_tranzactii,
        ROUND(AVG(suma_lei)) AS suma_medie,
        MAX(suma_lei) AS max_suma,
        DATE(MAX(CASE WHEN suma_lei = (SELECT MAX(suma_lei) FROM donatii) THEN data END)) AS max_data
    FROM donatii
");
$general = $stmt_general->fetch_assoc();
$total_general = number_format($general['total_general'] ?? 0, 0, '', '.');
$suma_medie = number_format($general['suma_medie'] ?? 0, 0, '', '.');
$nr_tranzactii = number_format($general['nr_tranzactii'] ?? 0, 0, '', '.');
$max_suma = number_format($general['max_suma'] ?? 0, 0, '', '.');
$max_data = $general['max_data'] ? date("d.m.Y", strtotime($general['max_data'])) : 'N/A';


// 5. Distribuția Donațiilor pe Tip (Donut Chart)
$stmt_tip = $conn->query("
    SELECT tip_donatie, ROUND(SUM(suma_lei)) AS total_tip
    FROM donatii GROUP BY tip_donatie ORDER BY total_tip DESC
");
$labels_tip = [];
$sume_tip = [];
while ($row = $stmt_tip->fetch_assoc()) {
    $labels_tip[] = $row['tip_donatie'];
    $sume_tip[] = $row['total_tip'];
}
$labels_tip_json = json_encode($labels_tip);
$sume_tip_json = json_encode($sume_tip);


// 6. Top 5 Asistați Social care au primit cele mai multe donații (Suma)
$stmt_top = $conn->query("
    SELECT 
        CONCAT(a.nume, ' ', a.prenume) AS nume_asistat, 
        ROUND(SUM(d.suma_lei)) AS total_primit
    FROM donatii d
    JOIN asistati_social a ON d.id_asistat = a.id
    GROUP BY a.id
    ORDER BY total_primit DESC
    LIMIT 5
");
$labels_top = [];
$sume_top = [];
while ($row = $stmt_top->fetch_assoc()) {
    $labels_top[] = $row['nume_asistat'];
    $sume_top[] = $row['total_primit'];
}
$labels_top_json = json_encode($labels_top);
$sume_top_json = json_encode($sume_top);

?>

<div class="container">
    <div class="row">
        <div class="col-md-3 d-none d-md-block">          
            <?php include "sidebar.php";?>
        </div>

        <div class="col-12 col-md-9">

<h1 class="mb-4">Dashboard Donații 📈</h1>
<hr>

<div class="row mb-5">
    
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-success shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-wallet-fill me-2"></i>Total General</h5>
                <p class="card-text fs-3 fw-bold"><?php echo $total_general; ?> Lei</p>
                <p class="card-text small">Donații totale de la început.</p>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card text-white bg-warning shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-trophy-fill me-2"></i>Max. Donație</h5>
                <p class="card-text fs-3 fw-bold"><?php echo $max_suma; ?> Lei</p>
                <p class="card-text small">Înregistrată la data: <?php echo $max_data; ?></p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-info shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-graph-up-arrow me-2"></i>Suma Medie</h5>
                <p class="card-text fs-3 fw-bold"><?php echo $suma_medie; ?> Lei</p>
                <p class="card-text small">Media pe toate tranzacțiile.</p>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card text-white bg-secondary shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-hash me-2"></i>Tranzacții</h5>
                <p class="card-text fs-3 fw-bold"><?php echo $nr_tranzactii; ?></p>
                <p class="card-text small">Numărul total de donații înregistrate.</p>
            </div>
        </div>
    </div>
</div>

<div class="row mb-5">
    
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-light fw-bold">1. Total Donații pe Ultimii 5 Ani</div>
            <div class="card-body">
                <canvas id="graficAni"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-light fw-bold">5. Distribuția pe Tip Donație (Suma totală)</div>
            <div class="card-body d-flex justify-content-center align-items-center">
                <canvas id="graficTip" style="max-height: 400px;"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row mb-5">
    
    <div class="col-md-8 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-light fw-bold">2. Evoluția Donațiilor (Lunar - <?php echo $an_curent . ' vs ' . $an_anterior; ?>)</div>
            <div class="card-body">
                <canvas id="graficLuni"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-light fw-bold">6. Top 5 Beneficiari (Suma primită)</div>
            <div class="card-body">
                <canvas id="graficTop"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Culori de bază pentru grafice
    const primary = 'rgba(0, 123, 255, 0.7)';
    const primaryBorder = 'rgba(0, 123, 255, 1)';
    const secondary = 'rgba(108, 117, 125, 0.7)';
    const secondaryBorder = 'rgba(108, 117, 125, 1)';
    
    // Funcție pentru a genera culori dinamice (pentru Donut)
    function generateColors(count) {
        const colors = [];
        const baseColors = ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6c757d', '#0dcaf0'];
        for (let i = 0; i < count; i++) {
            colors.push(baseColors[i % baseColors.length]);
        }
        return colors;
    }

    // --- Grafic 1: Donații pe Ani (Bar) ---
    const ctxAni = document.getElementById('graficAni').getContext('2d');
    const dataAni = {
        labels: <?php echo $ani; ?>,
        datasets: [{
            label: 'Total Lei',
            data: <?php echo $sume_ani; ?>,
            backgroundColor: primary,
            borderColor: primaryBorder,
            borderWidth: 1
        }]
    };
    new Chart(ctxAni, {
        type: 'bar',
        data: dataAni,
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // --- Grafic 2: Evoluția Lunar (Line) ---
    const ctxLuni = document.getElementById('graficLuni').getContext('2d');
    const dataLuni = {
        labels: <?php echo $lunar_labels; ?>,
        datasets: [{
            label: 'Anul Curent (<?php echo $an_curent; ?>)',
            data: <?php echo $lunar_curent_json; ?>,
            borderColor: primary,
            backgroundColor: 'transparent',
            tension: 0.3
        },
        {
            label: 'Anul Anterior (<?php echo $an_anterior; ?>)',
            data: <?php echo $lunar_anterior_json; ?>,
            borderColor: secondary,
            backgroundColor: 'transparent',
            tension: 0.3
        }]
    };
    new Chart(ctxLuni, {
        type: 'line',
        data: dataLuni,
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // --- Grafic 5: Distribuția pe Tip (Donut) ---
    const ctxTip = document.getElementById('graficTip').getContext('2d');
    const tipLabels = <?php echo $labels_tip_json; ?>;
    const tipSume = <?php echo $sume_tip_json; ?>;
    const tipColors = generateColors(tipLabels.length);

    const dataTip = {
        labels: tipLabels,
        datasets: [{
            data: tipSume,
            backgroundColor: tipColors,
            hoverOffset: 4
        }]
    };
    new Chart(ctxTip, {
        type: 'doughnut',
        data: dataTip,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed !== null) {
                                label += new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(context.parsed) + ' Lei';
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });

    // --- Grafic 6: Top 5 Beneficiari (Bar Orizontal) ---
    const ctxTop = document.getElementById('graficTop').getContext('2d');
    const dataTop = {
        labels: <?php echo $labels_top_json; ?>,
        datasets: [{
            label: 'Total Primit (Lei)',
            data: <?php echo $sume_top_json; ?>,
            backgroundColor: primary,
            borderColor: primaryBorder,
            borderWidth: 1
        }]
    };
    new Chart(ctxTop, {
        type: 'bar',
        data: dataTop,
        options: {
            responsive: true,
            indexAxis: 'y', // Face bara orizontală
            scales: {
                x: { beginAtZero: true }
            }
        }
    });

});
</script>

<?php 
include "footer.php";
?>