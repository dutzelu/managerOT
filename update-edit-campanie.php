<?php
include "header.php";

if (isset($_GET['id'])) {
    $id = $_GET['id'];
}

if(isset ($_POST["submit"]) ) {

    $nume = $_POST['nume'];
    $data_start = $_POST['data_start'];
    $data_final = $_POST['data_final'];
    $descriere = $_POST['descriere'];
    $link_ot = $_POST['link_ot'];
    $detalii_desf = $_POST['detalii_desf'];
    
    $query="
    UPDATE `campanii` 
    
    SET 
    
    `nume` = '$nume',
    `data_start` = '$data_start',
    `data_final` = '$data_final',
    `descriere` = '$descriere',
    `link_ot` = '$link_ot',
    `detalii_desf` = '$detalii_desf'

     WHERE `id` = '$id';";


    $rez=mysqli_query($conn, $query);
}

else {echo "Problem updating record.MySQL Error: " . mysqli_error($query);}

header ('Location:view-campanie.php?id='. $id . '&succes=' . $nume);


?>