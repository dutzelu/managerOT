<?php
$titlu_pg = "Detalii Pelerinaj";
include "../includes/header.php";

// Verifică dacă avem ID-ul pelerinajului
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: pelerinaje.php");
    exit;
}

$pelerinaj_id = intval($_GET['id']);

// Obține detaliile pelerinajului
$query_pelerinaj = "SELECT * FROM pelerinaje WHERE id = ?";
$stmt = $conn->prepare($query_pelerinaj);
$stmt->bind_param("i", $pelerinaj_id);
$stmt->execute();
$result_pelerinaj = $stmt->get_result();

if($result_pelerinaj->num_rows == 0) {
    header("Location: pelerinaje.php");
    exit;
}

$pelerinaj = $result_pelerinaj->fetch_assoc();
$stmt->close();

// Obține lista pelerinilor
$query_pelerini = "SELECT * FROM pelerini WHERE pelerinaj_id = ? ORDER BY data_inscriere DESC";
$stmt_pelerini = $conn->prepare($query_pelerini);
$stmt_pelerini->bind_param("i", $pelerinaj_id);
$stmt_pelerini->execute();
$result_pelerini = $stmt_pelerini->get_result();

// Calculează totaluri
$total_pelerini = $result_pelerini->num_rows;
$total_cu_avion = 0;
$total_fara_avion = 0;
$total_euro = 0;
$total_dolari = 0;

$pelerini_array = [];
while($pelerin = $result_pelerini->fetch_assoc()) {
    $pelerini_array[] = $pelerin;
    if($pelerin['cu_sau_fara_avion'] == 'cu avion') {
        $total_cu_avion++;
    } else {
        $total_fara_avion++;
    }
    $total_euro += $pelerin['plata_euro'];
    $total_dolari += $pelerin['plata_dolari'];
}

$stmt_pelerini->close();

$status_badge = '';
switch($pelerinaj['status']) {
    case 'activ':
        $status_badge = '<span class="badge bg-success">Activ</span>';
        break;
    case 'finalizat':
        $status_badge = '<span class="badge bg-secondary">Finalizat</span>';
        break;
    case 'anulat':
        $status_badge = '<span class="badge bg-danger">Anulat</span>';
        break;
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-3 d-none d-md-block">          
            <?php include "../includes/sidebar.php";?>
        </div>

        <div class="col-12 col-md-9">

<div class="container mt-4 mb-5">

    <?php if(isset($_GET['deleted']) && $_GET['deleted'] === 'success'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>Pelerinul a fost șters cu succes.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="bi bi-airplane-fill me-2"></i>
            <?php echo htmlspecialchars($pelerinaj['denumire']); ?>
            <?php echo $status_badge; ?>
        </h2>
        <div class="btn-group">
            <a href="edit_pelerinaj.php?id=<?php echo $pelerinaj_id; ?>" class="btn btn-sm btn-outline-warning">
                <i class="bi bi-pencil me-1"></i>Editează
            </a>
            <a href="pelerinaje.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Înapoi
            </a>
        </div>
    </div>

    <!-- Informații Pelerinaj -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle-fill me-2"></i>Informații Pelerinaj</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong><i class="bi bi-geo-alt-fill text-danger me-2"></i>Locație:</strong><br>
                            <?php echo htmlspecialchars($pelerinaj['locatie']); ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong><i class="bi bi-calendar-range me-2"></i>Perioada:</strong><br>
                            <?php echo date('d.m.Y', strtotime($pelerinaj['zi_start'])); ?> - 
                            <?php echo date('d.m.Y', strtotime($pelerinaj['zi_sfarsit'])); ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong><i class="bi bi-currency-euro me-2"></i>Cost Euro:</strong><br>
                            <?php echo number_format($pelerinaj['cost_euro'], 0, ',', '.'); ?> €
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong><i class="bi bi-currency-dollar me-2"></i>Cost Dolari:</strong><br>
                            <?php echo number_format($pelerinaj['cost_dolari'], 0, ',', '.'); ?> $
                        </div>
                    </div>
                    <?php if(!empty($pelerinaj['link_ot'])): ?>
                    <div class="mb-3">
                        <strong><i class="bi bi-link-45deg me-2"></i>Link OT:</strong><br>
                        <a href="<?php echo htmlspecialchars($pelerinaj['link_ot']); ?>" target="_blank" class="text-primary">
                            <?php echo htmlspecialchars($pelerinaj['link_ot']); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    <?php if(!empty($pelerinaj['descriere'])):
                        $words = str_word_count(strip_tags($pelerinaj['descriere']));
                        $needs_toggle = $words > 50;
                    ?>
                    <div class="mb-0">
                        <strong><i class="bi bi-file-text me-2"></i>Descriere:</strong>
                        <div class="mt-2 descriere-wrap">
                            <div id="descriereScurta" <?php if($needs_toggle): ?>class="descriere-collapsed"<?php endif; ?>>
                                <?php echo $pelerinaj['descriere']; ?>
                            </div>
                            <?php if($needs_toggle): ?>
                            <a href="#" class="descriere-toggle small" onclick="toggleDescriere(this); return false;">
                                <i class="bi bi-chevron-down me-1"></i>Citește mai mult
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Statistici -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-3 bg-light">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-bar-chart-fill me-2"></i>Statistici</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Total Pelerini</h6>
                        <h2 class="mb-0 text-primary"><?php echo $total_pelerini; ?></h2>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <i class="bi bi-airplane-fill text-info me-2"></i>Cu avion: <strong><?php echo $total_cu_avion; ?></strong><br>
                        <i class="bi bi-x-circle text-secondary me-2"></i>Fără avion: <strong><?php echo $total_fara_avion; ?></strong>
                    </div>
                    <hr>
                    <div class="mb-0">
                        <h6 class="text-muted mb-2">Total Încasat</h6>
                        <div class="text-success">
                            <strong style="font-size: 1.2rem;"><?php echo number_format($total_euro, 0, ',', '.'); ?> €</strong><br>
                            <strong style="font-size: 1.2rem;"><?php echo number_format($total_dolari, 0, ',', '.'); ?> $</strong>
                        </div>
                    </div>
                </div>
            </div>

            <?php if($pelerinaj['status'] == 'activ'): ?>
            <div class="card shadow-sm bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title"><i class="bi bi-link-45deg me-2"></i>Link Formular Înscriere</h6>
                    <p class="card-text small mb-2">Distribuie acest link pentru înscrieri:</p>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" id="linkFormular" 
                               value="<?php echo BASE_URL; ?>pelerinaje/formular_pelerin.php?pelerinaj=<?php echo $pelerinaj_id; ?>" readonly>
                        <button class="btn btn-light" type="button" onclick="copyLink()">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Lista Pelerini -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-people-fill me-2"></i>Pelerini Înscriși (<?php echo $total_pelerini; ?>)</h5>
        </div>
        <div class="card-body">
            <?php if($total_pelerini == 0): ?>
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    Nu există pelerini înscriși încă.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="tabelPelerini">
                        <thead>
                            <tr>
                                <th>Nr.</th>
                                <th>Nume Complet</th>
                                <th>Călătorie</th>
                                <th>Plată Euro</th>
                                <th>Plată Dolari</th>
                                <th>Data Înscriere</th>
                                <th>Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $nr = 1;
                            foreach($pelerini_array as $pelerin): 
                            $pelerin_url = 'pelerin.php?id=' . $pelerin['id'] . '&nume=' . urlencode($pelerin['nume']) . '&prenume=' . urlencode($pelerin['prenume']);
                            ?>
                            <tr style="cursor:pointer;" onclick="window.location='<?php echo $pelerin_url; ?>'">
                                <td><?php echo $nr++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($pelerin['nume'] . ' ' . $pelerin['prenume']); ?></strong>
                                </td>
                                <td>
                                    <?php if($pelerin['cu_sau_fara_avion'] == 'cu avion'): ?>
                                        <span class="badge bg-info">
                                            <i class="bi bi-airplane-fill"></i> Cu avion
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Fără avion</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($pelerin['plata_euro'], 0, ',', '.'); ?> €</td>
                                <td><?php echo number_format($pelerin['plata_dolari'], 0, ',', '.'); ?> $</td>
                                <td><?php echo date('d.m.Y H:i', strtotime($pelerin['data_inscriere'])); ?></td>
                                <td style="white-space:nowrap;" onclick="event.stopPropagation();">
                                    <a href="<?php echo $pelerin_url; ?>" 
                                       class="btn btn-sm btn-outline-primary" title="Vezi detalii">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="delete_pelerin.php?id=<?php echo $pelerin['id']; ?>&pelerinaj_id=<?php echo $pelerinaj_id; ?>" 
                                       class="btn btn-sm btn-outline-danger ms-1" title="Șterge pelerin"
                                       onclick="return confirm('Ești sigur că vrei să ștergi pelerinul <?php echo addslashes(htmlspecialchars($pelerin['nume'] . ' ' . $pelerin['prenume'])); ?>?');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<style>
.descriere-collapsed {
    max-height: 7em;
    overflow: hidden;
    position: relative;
}
.descriere-collapsed::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 2.5em;
    background: linear-gradient(transparent, white);
}
.descriere-toggle {
    display: inline-block;
    margin-top: 4px;
    color: #0d6efd;
    text-decoration: none;
}
.descriere-toggle:hover { text-decoration: underline; }
</style>

<script>
function toggleDescriere(link) {
    var div = document.getElementById('descriereScurta');
    var expanded = div.classList.toggle('descriere-collapsed');
    if (!expanded) {
        link.innerHTML = '<i class="bi bi-chevron-up me-1"></i>Arată mai puțin';
    } else {
        link.innerHTML = '<i class="bi bi-chevron-down me-1"></i>Citește mai mult';
    }
}

function copyLink() {
    var copyText = document.getElementById("linkFormular");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value);
    
    var button = event.target.closest('button');
    var originalHTML = button.innerHTML;
    button.innerHTML = '<i class="bi bi-check"></i>';
    button.classList.add('btn-success');
    button.classList.remove('btn-light');
    
    setTimeout(function() {
        button.innerHTML = originalHTML;
        button.classList.remove('btn-success');
        button.classList.add('btn-light');
    }, 2000);
}

$(document).ready(function() {
    $('#tabelPelerini').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.12.1/i18n/ro.json'
        },
        order: [[5, 'desc']],
        paging: false,
        info: false
    });
});
</script>

        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
