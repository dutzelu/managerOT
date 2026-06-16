<?php
include "includes/header.php";

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $persoana = $_GET['persoana'];
}

if(isset ($_POST["submit"]) ) {

    $suma_lei = test_input($_POST['suma_lei']);
    $tip_donatie = test_input($_POST['tip_donatie']);
    $mod_acordare = test_input($_POST['mod_acordare'] ?? '');
    $act_doveditor = test_input($_POST['act_doveditor']);
    $nr_act_doveditor = test_input($_POST['nr_act_doveditor']);
    $cont_beneficiar = test_input($_POST['cont_beneficiar'] ?? '');
    $numar_ordin_plata = test_input($_POST['numar_ordin_plata'] ?? '');
    $sursa_fondurilor = test_input($_POST['sursa_fondurilor'] ?? '');
    $link_act = test_input($_POST['link_act']);
    $scop_donatie = test_input($_POST['scop_donatie']);
    $observatii_ajutor = test_input($_POST['observatii_ajutor'] ?? '');
    $data_donatiei = test_input($_POST['data']);
    $proces_verbal = test_input($_POST['proces_verbal']);
    $link_proces_verbal = test_input($_POST['link_proces_verbal']);
    $anul = substr ($data_donatiei, 0,4);
    $luna = substr ($data_donatiei, 5,2);
    
    $target_dir = "asistati-social/" . replaceSpecialChars($persoana);


    if(isset($_FILES['act'])){

        $errors= array();
        $file_name = $_FILES['act']['name'];
        $file_size = $_FILES['act']['size'];
        $file_tmp = $_FILES['act']['tmp_name'];
        $file_type = $_FILES['act']['type'];
        $file_ext= strtolower(pathinfo($file_name,PATHINFO_EXTENSION));
        
        $extensions= array("jpeg","jpg","png");
        
        if(in_array($file_ext,$extensions)=== false){
          $errors[]="extension not allowed, please choose a JPEG or PNG file.";
        }
        
        if($file_size > 5097152) {
          $errors[]='File size must be excately 2 MB';
        }
        
        if(empty($errors)==true) {

          // directorul unde se vor încarca actele doveditoare (facturi, chitante, etc.)
          $target_dir = "procese-verbale/" . $anul . '/' . $luna;

          // dacă directorul nu există, va fi creat
          if (!file_exists($target_dir)) {
          mkdir($target_dir, 0777, true);}

          move_uploaded_file($file_tmp, $target_dir .'/'.$file_name);
          echo "Success";
        } else{print_r($errors);}

        if ($file_size !== 0 && empty($errors)==true ) {
        $link_act = $target_dir .'/'.$file_name; }
        // else {$link_act = "";}

    } 
    
    $query="
    UPDATE `donatii` 
    
    SET 
    
    `suma_lei` = '$suma_lei',
    `tip_donatie` = '$tip_donatie',
    `mod_acordare` = '$mod_acordare',
    `act_doveditor` = '$act_doveditor',
    `nr_act_doveditor` = '$nr_act_doveditor',
    `cont_beneficiar` = '$cont_beneficiar',
    `numar_ordin_plata` = '$numar_ordin_plata',
    `sursa_fondurilor` = '$sursa_fondurilor',
    `link_act` = '$link_act',
    `proces_verbal` = '$proces_verbal',
    `link_proces_verbal` = '$link_proces_verbal',
    `scop_donatie` = '$scop_donatie',
    `observatii_ajutor` = '$observatii_ajutor',
    `data` = '$data_donatiei'

     WHERE `id` = '$id';";


    $rez=mysqli_query($conn, $query);
}

else {echo "Problem updating record.MySQL Error: " . mysqli_error($query);}

header ('Location:edit-donatie.php?id='. $id . '&succes=' . $persoana);


?>
