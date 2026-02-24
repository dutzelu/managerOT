<?php
// Setarea Titlului și includerea header-ului (care face verificarea de sesiune)
$titlu_pg = "Lista Simplificată a Asistaților Sociali";
include "includes/header.php";

// Nu mai interogăm baza de date pe baza parametrului GET, ci luăm toate datele
$sql = "SELECT id, nume, prenume, cnp, telefon FROM `asistati_social` ORDER BY `nume`, `prenume`";
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
                <tr class="clickable-row data-row" data-href="<?php echo $edit_url; ?>">
                    <td><?php echo $i++; ?></td>
                    <td class="search-col"><?php echo htmlspecialchars($nume_complet); ?></td>
                    <td class="search-col"><?php echo htmlspecialchars($data['cnp']); ?></td>
                    <td class="search-col"><?php echo htmlspecialchars($data['telefon']); ?></td>
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
        
        // Rânduri Clickabile 
        $(".clickable-row").click(function() {
            window.location = $(this).data("href");
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