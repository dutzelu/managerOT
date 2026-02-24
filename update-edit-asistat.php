<?php
include "includes/header.php";

if (isset($_GET['id'])) {
    $id = $_GET['id'];
}

if(isset ($_POST["submit"]) ) {

    $nume = test_input($_POST['nume']);
    $prenume = test_input($_POST['prenume']);
    $cnp = test_input($_POST['cnp']);
    $serie_nr_ci = test_input($_POST['serie_nr_ci']);
    $adresa_completa = test_input($_POST['adresa_completa']);
    $localitate = test_input($_POST['localitate']);
    $judet = test_input($_POST['judet']);
    $stare_civila = test_input($_POST['stare_civila']);

   if(!empty($cnp)){$prima_cifra = $cnp['0'];} else {$prima_cifra = "";}
    if ($prima_cifra == 2 || $prima_cifra == 6) {
      $stare_civila = $stare_civila . "ă";
    } 
    
    $nr_copii = $_POST['nr_copii'];
    $descriere = test_input($_POST['descriere']);
    $contract_sponsorizare = $_POST['contract_sponsorizare'];
    $link_contract = $_POST['link_contract'];
     
    
    $target_dir = "asistati-social/" . replaceSpecialChars($nume) . "-" . replaceSpecialChars($prenume);


    if(isset($_FILES['copiebuletin'])){
        $errors= array();
        $file_name = $_FILES['copiebuletin']['name'];
        $file_size = $_FILES['copiebuletin']['size'];
        $file_tmp = $_FILES['copiebuletin']['tmp_name'];
        $file_type = $_FILES['copiebuletin']['type'];
        $file_ext= strtolower(pathinfo($file_name,PATHINFO_EXTENSION));
        
        $extensions= array("jpeg","jpg","png");
        
        if(in_array($file_ext,$extensions)=== false){
          $errors[]="extension not allowed, please choose a JPEG or PNG file.";
        }
        
        if($file_size > 5097152) {
          $errors[]='File size must be excately 2 MB';
        }
        
        if(empty($errors)==true) {
          move_uploaded_file($file_tmp, $target_dir .'/'.$file_name);
          echo "Success";
        } else{print_r($errors);}

        if ($file_size !== 0 && empty($errors)==true ) {
        $link_ci = $target_dir .'/'.$file_name; }
        else {$link_ci = "";}

      } 

    $telefon = $_POST['telefon'];
    
    $query="
    UPDATE `asistati_social` 
    
    SET 
    
    `nume` = '$nume', 
    `prenume` = '$prenume',
    `cnp` = '$cnp', 
    `serie_nr_ci` = '$serie_nr_ci', 
    `adresa_completa` = '$adresa_completa', 
    `localitate` = '$localitate',
    `judet` = '$judet',
    `stare_civila` = '$stare_civila',
    `nr_copii` = '$nr_copii', 
    `descriere` = '$descriere', 
    `contract_sponsorizare` = '$contract_sponsorizare', 
    `link_contract` = '$link_contract',
    `link_ci` = '$link_ci',
    `telefon` = '$telefon'

     WHERE `id` = '$id';";


    $rez=mysqli_query($conn, $query);
}

else {echo "Problem updating record.MySQL Error: " . mysqli_error($query);}

header ('Location:edit-asistat.php?id='. $id . '&asistat=' . $nume . '-' . $prenume);


?>