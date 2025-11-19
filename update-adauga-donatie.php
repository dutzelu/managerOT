<?php
$titlu_pg = "Adaugă donație";
include "header.php";

if (isset($_GET['donatie'])) {
  $asistat = $_GET['donatie'];
} else { $asistat = "";}

if(isset ($_POST["submit"]) ) {

    $id_asistat = test_input($_POST['id_asistat']);
    $data = test_input($_POST['data']);
    $suma_lei = test_input($_POST['suma_lei']);
    $tip_donatie = test_input($_POST['tip_donatie']);
    $scop_donatie = test_input($_POST['scop_donatie']);
    $act_doveditor = test_input($_POST['act_doveditor']);
    $nr_act_doveditor = test_input($_POST['nr_act_doveditor']);
 
    $anul = substr ($data, 0,4);
    $luna = substr ($data, 5,2);
   
    // directorul unde se vor încarca actele doveditoare (facturi, chitante, etc.) 
    $target_dir = "donatii/" . $anul . '/' . $luna;
    
    if (!file_exists($target_dir)) {
      mkdir($target_dir, 0777, true);}
  

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
          move_uploaded_file($file_tmp, $target_dir .'/'.$file_name);
          echo "Success";
        } else{print_r($errors);}

        if ($file_size !== 0 && empty($errors)==true ) {
        $link_ci = $target_dir .'/'.$file_name; }
        else {$link_ci = "";}

    } 

    
    $query="
    INSERT INTO donatii 
    
    (`id_asistat`, `suma_lei`,`tip_donatie`, `act_doveditor`, `nr_act_doveditor`, `link_act`,`scop_donatie`,`data`)
    
   VALUES 

   ('$id_asistat','$suma_lei','$tip_donatie','$act_doveditor','$nr_act_doveditor', '$link_act','$scop_donatie','$data');";

    $rez=mysqli_query($conn, $query);


}
else {echo "Problem updating record.MySQL Error: " . mysqli_error($query);}

header('Location:adauga-donatie.php?donatiepentruid=' .  $id_asistat . '&suma=' . $suma_lei);













include "footer.php";

?>