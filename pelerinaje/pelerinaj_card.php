<?php
// Acest fișier este folosit ca template pentru afișarea unui pelerinaj
// Variabila $row trebuie să fie disponibilă din fișierul care include acest template

$status_badge = '';
switch($row['status']) {
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

$zi_start_formatted = date('d.m.Y', strtotime($row['zi_start']));
$zi_sfarsit_formatted = date('d.m.Y', strtotime($row['zi_sfarsit']));
?>

<div class="card mb-3 shadow-sm">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="card-title">
                    <i class="bi bi-airplane-fill text-primary me-2"></i>
                    <?php echo htmlspecialchars($row['denumire']); ?>
                    <?php echo $status_badge; ?>
                </h5>
                <p class="card-text mb-2">
                    <i class="bi bi-geo-alt-fill text-danger me-2"></i>
                    <strong>Locație:</strong> <?php echo htmlspecialchars($row['locatie']); ?>
                </p>
                <p class="card-text mb-2">
                    <i class="bi bi-calendar-range me-2"></i>
                    <strong>Perioada:</strong> <?php echo $zi_start_formatted; ?> - <?php echo $zi_sfarsit_formatted; ?>
                </p>
                <p class="card-text mb-0">
                    <i class="bi bi-people-fill text-info me-2"></i>
                    <strong>Pelerini înscriși:</strong> <?php echo $row['numar_pelerini']; ?>
                    <span class="text-muted ms-2">
                        (<?php echo $row['cu_avion']; ?> cu avion, <?php echo $row['fara_avion']; ?> fără avion)
                    </span>
                </p>
            </div>
            <div class="col-md-4 text-end">
                <div class="mb-2">
                    <small class="text-muted">Total Încasat:</small><br>
                    <strong class="text-success"><?php echo number_format($row['total_euro'], 0, ',', '.'); ?> €</strong><br>
                    <strong class="text-success"><?php echo number_format($row['total_dolari'], 0, ',', '.'); ?> $</strong>
                </div>
                <div class="btn-group" role="group">
                    <a href="pelerinaj.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary" title="Vizualizează">
                        <i class="bi bi-eye"></i>
                    </a>
                    <a href="edit_pelerinaj.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-warning" title="Editează">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <?php if($row['status'] == 'activ'): ?>
                    <a href="../formular_pelerin.php?pelerinaj=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-success" title="Link Formular" target="_blank">
                        <i class="bi bi-link-45deg"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
