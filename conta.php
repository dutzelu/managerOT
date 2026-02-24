<?php
$titlu_pg = "TABEL donații";
// Asigură-te că "header.php" a fost actualizat pentru a folosi Bootstrap 5 și Bootstrap Icons
include "includes/header.php";

setlocale(LC_ALL, 'ro_RO');

// Anul curent implicit
$an_curent = date("Y");

// Setează anul pe baza parametrului GET, sau folosește anul curent dacă nu e setat
// Deoarece folosim o listă dinamică, putem lăsa "toate" ca valoare implicită dacă nu e setat
$an = $_GET['an'] ?? $an_curent; 

// Setează tipul donației (folosim '%' pentru a include toate tipurile implicit)
$tip = $_GET['tip'] ?? "%";

// --- NOU: EXTRAGEREA ANILOR UNICI DIN BAZA DE DATE ---
$sql_ani = "SELECT DISTINCT YEAR(data) AS an FROM donatii WHERE data IS NOT NULL ORDER BY an DESC";
$rezultate_ani = mysqli_query($conn, $sql_ani);
$ani_disponibili = [];
while ($rand = mysqli_fetch_assoc($rezultate_ani)) {
    $ani_disponibili[] = $rand['an'];
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-3 d-none d-md-block">          
            <?php include "includes/sidebar.php";?>
        </div>

        <div class="col-12 col-md-9">

<div class="row mb-4 align-items-center">
    <div class="col-12">
        <h2 class="mb-3">Tabel Donații Anul <?php echo (is_numeric($an) ? htmlspecialchars($an) : 'Curent'); ?></h2>
    </div>
</div>

<form method="GET" action="tabel-donatii.php" class="mb-4">
    <div class="row g-3 align-items-center">
        <div class="col-auto">
            <label for="select-an" class="col-form-label fw-bold">Selectează Anul:</label>
        </div>
        <div class="col-auto">
            <select name="an" id="select-an" class="form-select">
                <?php if (empty($ani_disponibili)): ?>
                    <option value="<?php echo $an_curent; ?>"><?php echo $an_curent; ?> (Nu există donații)</option>
                <?php else: ?>
                    <?php foreach ($ani_disponibili as $an_opt): ?>
                        <option value="<?php echo $an_opt; ?>" <?php echo ($an_opt == $an) ? 'selected' : ''; ?>>
                            <?php echo $an_opt; ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-filter"></i> Filtrează
            </button>
        </div>
    </div>
</form>

<?php
    // Mesaj de succes (dacă există)
    if (isset($_GET['raport'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
        echo 'Am creat cu succes raportul pentru anul contabil ' . htmlspecialchars($an) . '.';
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
?>

<p>
    <a href="tabel-anual-contabilitate.php?an=<?php echo htmlspecialchars($an);?>" 
       class="btn btn-primary btn-sm mb-4">
       <i class="bi bi-file-earmark-spreadsheet"></i> Creează raport anual <?php echo htmlspecialchars($an);?>
    </a>
</p>

<div class="table-responsive">
    <table class="table table-bordered table-hover" id="donatii-table"> 

        <thead class="table-primary">
            <tr>    
                <th>ID</th>
                <th>Dată</th>
                <th>Asistat (Nume)</th>
                <th>Tip donație</th>
                <th>Scop donație</th>
                <th class="text-end">Sumă (lei)</th>
                <th>Proces Verbal</th>
                <th>Acțiuni</th> 
            </tr>
        </thead>
     
        <tbody>

<?php
// QUERY SQL (RĂMÂNE APROAPE IDENTIC)
$stmt = $conn->prepare("
    SELECT 
        asistati_social.nume as 'nume',
        asistati_social.prenume as 'prenume',
        donatii.ID as 'id_donatie',
        donatii.suma_lei,
        donatii.tip_donatie,
        donatii.act_doveditor,
        donatii.nr_act_doveditor,
        donatii.link_act,
        donatii.scop_donatie,
        donatii.data,
        donatii.proces_verbal,
        donatii.link_proces_verbal
    FROM asistati_social
    LEFT JOIN donatii ON asistati_social.id = donatii.id_asistat
    WHERE YEAR(data) = ? AND tip_donatie LIKE ?
    ORDER BY donatii.data ASC, donatii.ID ASC
");

// $an este acum din dropdown, $tip este implicit '%'
$stmt->bind_param("ss", $an, $tip); 
$stmt->execute(); 
$result = $stmt->get_result();

while ($date = mysqli_fetch_assoc($result)){    
    $nume_complet = $date['nume'] . ' ' . $date['prenume'];
    $data_formatata = strftime('%e. %m. %Y', strtotime($date['data']));
    $suma_lei_formatata = number_format($date['suma_lei'], 0, ',', '.'); 
?>

            <tr class="clickable-row" data-href="edit-donatie.php?id=<?php echo $date['id_donatie'];?>"> 
                <td><?php echo $date['id_donatie'];?></td>
                <td><?php echo $data_formatata; ?></td>
                <td><?php echo htmlspecialchars($nume_complet); ?></td>
                <td><?php echo htmlspecialchars($date['tip_donatie']);?></td>
                <td><?php echo htmlspecialchars($date['scop_donatie']);?></td>
                <td class="text-end fw-bold"><?php echo $suma_lei_formatata; ?></td>
            
                
                <td>
                    <?php if (!empty($date['link_proces_verbal'])): ?>
                        <a href="<?php echo htmlspecialchars($date['link_proces_verbal']); ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Vezi Proces Verbal" onclick="event.stopPropagation();">
                           <i class="bi bi-file-earmark-check"></i>
                        </a>
                    <?php else: ?>
                        <?php echo htmlspecialchars($date['proces_verbal']); ?> 
                    <?php endif; ?>
                </td>
                
                <td>
                    <a href="edit-donatie.php?id=<?php echo $date['id_donatie'];?>" 
                       class="btn btn-sm btn-outline-secondary" 
                       title="Editează Donația"
                       onclick="event.stopPropagation();">
                       <i class="bi bi-pencil"></i> 
                    </a>
                </td>
            </tr>

<?php };?>
        </tbody>
    </table>
</div>

<script>
    $(document).ready(function () {
        
        // 1. Inițializare Datatables
        $('#donatii-table').DataTable({
            "order": [], 
            "searching": true, 
            language: {
                url: '<?php echo BASE_URL; ?>js/dataTablesRomana.json'
            }
        });

        // 2. Rânduri Clickabile
        $(".clickable-row").click(function() {
            window.location = $(this).data("href");
        });
        
    });
</script>
<?php include "includes/footer.php"; ?>
