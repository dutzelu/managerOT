<?php
$titlu_pg = "Tabel anual contabilitate";
include "includes/header.php";

if (isset($_GET['an'])) {
    $an = $_GET['an'];
} else {$an = "";}

if (isset($_GET['tip'])) {
    $tip = $_GET['tip'];
} else {$tip = "%";}

ob_start();

 
$stmt = $conn->prepare("
SELECT 
    asistati_social.id,
    asistati_social.nume as 'nume',
    asistati_social.prenume as 'prenume',
    donatii.id as 'id_donatie',
    donatii.suma_lei,
    donatii.tip_donatie as 'tip_donatie',
    donatii.act_doveditor,
    donatii.nr_act_doveditor,
    donatii.link_act,
    donatii.scop_donatie,
    donatii.data,
    donatii.proces_verbal,
    donatii.link_proces_verbal
FROM asistati_social
LEFT JOIN donatii
ON asistati_social.id = donatii.id_asistat
WHERE YEAR(data) = ? 
AND tip_donatie LIKE ?
ORDER BY id_donatie
ASC

");

$stmt->bind_param("ss", $an, $tip); 
$stmt->execute(); 
$result = $stmt->get_result();
?>

<p style="text-align:right">Asociația Ortodoxia Tinerilor <br>
Str.Frunzei nr.5, Bl.F3, 63 Galați, jud. Galați, România<br>
asociatia@ortodoxiatinerilor.ro<br>
www.ortodoxiatinerilor.ro | 0740.004.215
</p>

<p>&nbsp;</p>
<h1 style="text-align:center">Tabel donații  <?php echo $an;?> </h1>

<div style="text-align:center">

<table border="1">

    <thead>
        <tr>    
            <th>id</th>
            <th>Dată</th>
            <th>Nume</th>
            <th>Tip donație</th>
            <th>Scop donație</th>
            <th>Sumă (lei)</th>
            <th>Proces verbal</th>
        </tr>
    </thead>
 
    <tbody>

<?php

while ($date = mysqli_fetch_assoc($result)){    

    $nume = $date['nume'];
    $prenume = $date['prenume'];
    $data = $date['data'];
    $anul = date("Y", strtotime($date['data']));
    $luna = date("F", strtotime($date['data']));
    $luna_cu_numar = date("m", strtotime($date['data']));
    $suma_lei = $date['suma_lei'];
    $id_donatie = $date['id_donatie'];
    $tip_donatie = $date['tip_donatie'];
    $scop_donatie = $date['scop_donatie'];
    $act_doveditor = $date['act_doveditor'];
    $nr_act_doveditor = $date['nr_act_doveditor'];
    $link_act = $date['link_act'];
    $proces_verbal = $date['proces_verbal'];
    $link_proces_verbal = $date['link_proces_verbal'];

?>



    <tr>
            <td><?php echo $id_donatie;?></td>
            <td style="width:100px;"><?php echo strftime('%e. %m. %Y',strtotime($data)); ?></td>
            <td style="width:200px;"><?php echo $nume . ' ' . $prenume; ?></td>
            <td><?php echo $tip_donatie;?></td>
            <td><?php echo $scop_donatie ;?></td>
            <td><?php echo $suma_lei; ?></td>
            <td style="width:100px;"><?php echo $proces_verbal?></td>
        </tr>

        <?php };?>


    <tr>
    <td><?php echo $id_donatie;?></td>
    <td><?php echo strftime('%e. %m. %Y',strtotime($data)); ?></td>
    <td><?php echo $nume . ' ' . $prenume; ?></td>
    <td><?php echo $tip_donatie;?></td>
    <td><?php echo $scop_donatie ;?></td>
    <td><?php echo $suma_lei; ?></td>
    <td><?php echo $proces_verbal?></td>
</tr>
</tbody>

</table>

<?php  

  // directorul unde se vor încarca procesele verbale
  $target_dir = "tabele-contabilitate/";
  $file_name = 'tabel-contabilitate-' . $an . '.pdf';
  $link_proces_verbal = $target_dir . $file_name;
  // dacă directorul nu există, va fi creat

  if (!file_exists($target_dir)) {
  mkdir($target_dir, 0777, true);}


$html = ob_get_clean(); 

require_once __DIR__ . '/vendor/autoload.php';

// Crează o instanță Mpdf ----------------
$mpdf = new \Mpdf\Mpdf();


// Scriere fișierului PDF ----------------
$mpdf->WriteHTML($html);


// Salvarea fișierului pdf----------------
ob_clean(); 
$mpdf->Output($target_dir . $file_name, \Mpdf\Output\Destination::FILE);


// inserare link proces verbal în baza de date ----------------

if (file_exists($target_dir . $file_name)) {

     header('Location:tabel-donatii.php?an=' .  $an . "&raport=ok");

} else {echo "Problem updating record.MySQL Error: " . mysqli_error($query);}




?>