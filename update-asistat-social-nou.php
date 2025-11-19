<?php
// update-asistat-social-nou.php - Adaugare Asistat Social (SECURIZAT)

// Include conexiunea la baza de date ($conn) și funcțiile (inclusiv test_input și replaceSpecialChars)
// Presupunem că $conn este definit în conexiune.php (inclus prin header.php)
include "header.php"; 

// Inițializări necesare pentru a preveni erorile de variabile nedefinite
$errors = []; 
$link_ci = ''; 
$nume_prenume_asistat = ''; 

if(isset ($_POST["submit"]) ) {
    
    // --- 1. Preluare și Sanitizare Date ---
    $nume = test_input($_POST['nume']);
    $prenume = test_input($_POST['prenume']);
    $cnp = test_input($_POST['cnp']);
    $serie_nr_ci = test_input($_POST['serie_nr_ci']);
    $adresa_completa = test_input($_POST['adresa_completa']);
    $localitate = test_input($_POST['localitate']);
    $judet = test_input($_POST['judet']);
    $stare_civila = $_POST['stare_civila'];
    
    // Logică pentru acord gen
    if (!empty($cnp) && strlen($cnp) >= 1) {
      $prima_cifra = $cnp[0];
      if (($prima_cifra == 2 || $prima_cifra == 6) && substr($stare_civila, -1) !== 'ă') {
        $stare_civila = $stare_civila . "ă";
      } 
    }
    
    $nr_copii = $_POST['nr_copii'] ?? 0;
    $descriere = test_input($_POST['descriere']);
    $contract_sponsorizare = $_POST['contract_sponsorizare'];
    $link_contract = $_POST['link_contract'];
    $telefon = $_POST['telefon'];
    
    $nume_complet_folder = replaceSpecialChars($nume) . "-" . replaceSpecialChars($prenume);
    $target_dir = "asistati-social/" . $nume_complet_folder;
    $nume_prenume_asistat = $nume . ' ' . $prenume;

    // --- 2. LOGICĂ UPLOAD FIȘIER (COPIE CI) ---
    
    // Creare director (dacă nu există)
    if (!is_dir($target_dir)) {
        if (!mkdir($target_dir, 0777, true)) {
            // Dacă directorul nu se poate crea, adăugăm eroare dar permitem inserarea datelor (fără CI)
            $errors[] = "Eroare la crearea directorului pentru documente. Datele vor fi salvate fără CI.";
        }
    }
    
    // Verificăm dacă există un fișier de încărcat și nu există erori inițiale
    if(isset($_FILES['copiebuletin']) && $_FILES['copiebuletin']['error'] === UPLOAD_ERR_OK){
        
        $file_name = $_FILES['copiebuletin']['name'];
        $file_tmp = $_FILES['copiebuletin']['tmp_name'];
        $file_size = $_FILES['copiebuletin']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_extensions = array("jpeg","jpg","png","pdf"); 
        $max_size = 5 * 1024 * 1024; // 5 MB
        
        // Validare extensie
        if(!in_array($file_ext, $allowed_extensions)){
          $errors[] = "Extensia nu este permisă. Folosiți JPEG, JPG, PNG sau PDF.";
        }
        
        // Validare mărime
        if($file_size > $max_size) {
          $errors[] = 'Mărimea fișierului trebuie să fie sub 5 MB.';
        }
        
        if(empty($errors)) {
          // Nume unic al fișierului pentru a preveni coliziunile și a fi mai specific
          $unique_file_name = "CI-" . $nume_complet_folder . "." . $file_ext;
          $destination_path = $target_dir . '/' . $unique_file_name;
          
          if(move_uploaded_file($file_tmp, $destination_path)) {
            $link_ci = $destination_path; 
          } else {
             $errors[] = "Eroare la mutarea fișierului (permisiuni server?). Datele vor fi salvate fără CI.";
          }
        }
    } 
    
    // --- 3. LOGICĂ INSERARE ÎN BAZA DE DATE (Prepared Statement) ---

    // Interogarea SQL (folosind `?` ca placeholder)
    $sql_insert = "
        INSERT INTO asistati_social 
        (nume, prenume, cnp, serie_nr_ci, adresa_completa, localitate, judet, stare_civila, nr_copii, 
         descriere, contract_sponsorizare, link_contract, link_ci, telefon)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    // Pregătirea interogării
    $stmt = $conn->prepare($sql_insert);
    
    if ($stmt === false) {
        // Eroare la pregătirea interogării (sintaxă SQL incorectă)
        $errors[] = "Eroare la pregătirea interogării SQL: " . $conn->error;
    } else {
        // Tipuri de date: "ssssssssisssss" (14 parametri: 13 string-uri, 1 integer)
        $tipuri = "ssssssssisssss"; 
        $nr_copii_int = (int)$nr_copii; // Asigurăm că este un integer

        $stmt->bind_param($tipuri, 
            $nume, $prenume, $cnp, $serie_nr_ci, $adresa_completa, $localitate, $judet, $stare_civila, 
            $nr_copii_int, // Parametru integer
            $descriere, $contract_sponsorizare, $link_contract, $link_ci, $telefon
        );

        // Executarea interogării
        if ($stmt->execute()) {
            // Succes: Redirecționare
            $stmt->close();
            header("Location: asistat-social-nou.php?asistat=" . urlencode($nume_prenume_asistat));
            exit(); 
        } else {
            // Eroare la executare 
            $errors[] = "Eroare la salvarea datelor în baza de date: " . $stmt->error;
        }
        
        $stmt->close();
    }
    
    // --- 4. AFIȘARE ERORI (Dacă s-a ajuns aici) ---
    if (!empty($errors)) {
        echo '<div class="container mt-4">';
        echo '<h2><i class="bi bi-x-circle-fill me-2 text-danger"></i> Eroare la adăugarea asistatului social</h2>';
        echo '<div class="alert alert-danger">';
        echo '<ul>';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul>';
        echo '<p class="mt-3"><a href="asistat-social-nou.php" class="btn btn-primary"><i class="bi bi-arrow-left me-1"></i> Înapoi la formular</a></p>';
        echo '</div>';
        echo '</div>';
    }


} else {
    // Acces direct la fișier, fără formular
    echo '<div class="container mt-4"><div class="alert alert-warning"><i class="bi bi-exclamation-triangle-fill me-2"></i> Acces invalid. Vă rugăm folosiți formularul de adăugare.</div></div>';
}

include "footer.php";
?>