<?php
// Setarea Titlului și includerea header-ului (care face verificarea de sesiune)
$titlu_pg = "Lista Simplificată a Asistaților Sociali";
include "includes/header.php";

// Lista beneficiarilor impreuna cu ultima fisa sociala.
$sql = "
SELECT
    a.id,
    a.nume,
    a.prenume,
    a.cnp,
    a.telefon,
    a.localitate,
    a.judet,
    f.status_caz,
    f.data_evaluarii
FROM asistati_social a
LEFT JOIN (
    SELECT fs.*
    FROM fise_sociale fs
    INNER JOIN (
        SELECT beneficiar_id, MAX(id) AS max_id
        FROM fise_sociale
        GROUP BY beneficiar_id
    ) latest ON latest.max_id = fs.id
) f ON f.beneficiar_id = a.id
ORDER BY a.nume, a.prenume
";
$rezultate = mysqli_query($conn, $sql);

// Vom prelua tot termenul de căutare din $_GET pentru a-l afișa, dacă există, 
// dar filtrarea reală se va face prin JS.
$search_term = $_GET['search'] ?? ''; 
?>

<div class="container">
    <div class="row">
        <div class="col-md-3 d-none d-md-block">          
            <?php include "includes/sidebar.php";?>
        </div>

        <div class="col-12 col-md-9">


<div class="row mb-4 align-items-center">
    <div class="col-8">
        <h1>Lista Asistaților Sociali</h1>
    </div>
    <div class="col-4 text-end">
        <a href="asistat-social-nou.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Adaugă Asistat Nou
        </a>
    </div>
</div>

<div class="form-group mb-4">
    <div class="input-group">
        <input type="text" 
               id="search-input" 
               name="search" 
               class="form-control" 
               placeholder="Filtrează după Nume, Prenume sau CNP (Scrie câteva litere)..." 
               value="<?php echo htmlspecialchars($search_term); ?>">
        
        <span class="input-group-text bg-primary text-white"> 
             <i class="bi bi-search"></i>
        </span>
    </div>
</div>

<?php if (mysqli_num_rows($rezultate) == 0): ?>
    <div class="alert alert-info">
        Nu au fost găsiți asistați care să corespundă criteriilor de căutare.
    </div>
<?php else: ?>

    <div class="table-responsive">
        <table class="table table-hover" id="asistati-table">
            <thead class="table-primary">
                <tr>
                    <th>Nr.</th>
                    <th>Nume și Prenume</th>
                    <th>CNP</th>
                    <th>Telefon</th>
                    <th>Localitate</th>
                    <th>Status</th>
                    <th>Evaluare</th>
                    <th>Donații</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $i = 1;
                while ($data = mysqli_fetch_assoc($rezultate)): 
                    $nume_complet = $data['nume'] . ' ' . $data['prenume'];
                    $edit_url = 'edit-asistat.php?id=' . $data['id'];
                    $donatii_url = 'lista-donatii.php?id=' . $data['id'] . '&persoana=' . urlencode($nume_complet) . '&an=' . date("Y");
                ?>
                <tr class="clickable-row data-row" data-href="<?php echo htmlspecialchars($edit_url); ?>" tabindex="0" title="Deschide fișa beneficiarului">
                    <td><?php echo $i++; ?></td>
                    <td class="search-col"><?php echo htmlspecialchars($nume_complet); ?></td>
                    <td class="search-col"><?php echo htmlspecialchars($data['cnp']); ?></td>
                    <td class="search-col"><?php echo htmlspecialchars($data['telefon']); ?></td>
                    <td class="search-col"><?php echo htmlspecialchars(trim(($data['localitate'] ?? '') . ', ' . ($data['judet'] ?? ''), ', ')); ?></td>
                    <td class="search-col">
                        <span class="badge bg-secondary"><?php echo htmlspecialchars($data['status_caz'] ?? 'caz nou'); ?></span>
                    </td>
                    <td><?php echo htmlspecialchars($data['data_evaluarii'] ?? ''); ?></td>
                    <td>
                        <a href="<?php echo $donatii_url; ?>" 
                           class="btn btn-sm btn-outline-primary" 
                           title="Vezi Donații"
                           onclick="event.stopPropagation();">
                           Donații</i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

<?php endif; ?>

<script>
    $(document).ready(function() {
        
        // Rânduri clickabile
        document.querySelectorAll(".clickable-row[data-href]").forEach(function(row) {
            row.addEventListener("click", function(event) {
                if (event.target.closest("a, button, input, select, textarea")) {
                    return;
                }
                window.location.href = row.dataset.href;
            });

            row.addEventListener("keydown", function(event) {
                if (event.key === "Enter" || event.key === " ") {
                    event.preventDefault();
                    window.location.href = row.dataset.href;
                }
            });
        });

        // Filtrare Instantanee 
        $("#search-input").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            
            $("#asistati-table tbody tr").filter(function() {
                var row_text = $(this).text().toLowerCase();
                
                $(this).toggle(row_text.indexOf(value) > -1)
            });
        });
        
        // Aplică filtrarea imediat dacă există deja un termen 
        if ($('#search-input').val().length > 0) {
            $("#search-input").trigger('keyup');
        }
    });
</script>

<?php 
include "includes/footer.php"; 
?>
