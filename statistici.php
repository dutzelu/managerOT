<?php
$titlu_pg = "Toate donațiile";
include "header.php";

setlocale(LC_ALL, 'ro_RO');

if (isset($_GET['an'])) {
    $an = $_GET['an'];
} else {$an = "";}

if (isset($_GET['tip'])) {
    $tip = $_GET['tip'];
} else {$tip = "%";}

echo '<h2>Statistici anuale</h2>';
$total_luna = NULL;
?>


<p>Filtrează pe ani: 

<a href="total-donatii.php?an=<?php echo date("Y")-4;?>"><?php  echo date("Y")-4;?></a> |
<a href="total-donatii.php?an=<?php echo date("Y")-3;?>"><?php  echo date("Y")-3;?></a> |
<a href="total-donatii.php?an=<?php echo date("Y")-2;?>"><?php  echo date("Y")-2;?></a> |
<a href="total-donatii.php?an=<?php echo date("Y")-1;?>"><?php  echo date("Y")-1;?></a> |
<a href="total-donatii.php?an=<?php echo date("Y");?>"><?php  echo date("Y");?></a>
</p> 

<p>Filtrează pe tip de donație: 
<a href="total-donatii.php?an=<?php echo $an . '&tip=cash';?>">Cash</a> |
<a href="total-donatii.php?an=<?php echo $an . '&tip=cont';?>">Cont</a> |
<a href="total-donatii.php?an=<?php echo $an . '&tip=produse';?>">Produse</a> |
<a href="total-donatii.php?an=<?php echo $an . '&tip=WU';?>">WU</a> |
<a href="total-donatii.php?an=<?php echo $an . '&tip=Posta Romana';?>">Poșta Română</a> |


</p> 

<?php

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
ORDER BY donatii.data
ASC

");

$stmt->bind_param("ss", $an, $tip); 
$stmt->execute(); 
$result = $stmt->get_result();

echo '<ul class="list-group donatii">';

$ultimul_an = null;
$ultima_luna = null;
$adunare_donatii = "";


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
   
total_anual ($anul);
total_luna ($luna_cu_numar, $anul);
echo $total_luna;

    $adunare_donatii .= $id_donatie . '-';
    if (!empty($data)) {

        if ($ultimul_an != $anul){
            $ultimul_an = $anul;
            echo '<p class="anul btn btn-dark disabled">Anul: ' . $anul . ": " . $total_anual .  $total_luna . " lei</p>";
            
        }
        
        if ($ultima_luna != $luna){
            $ultima_luna = $luna;
            
            echo '<span class="btn list-group-item-warning">' . $luna . ' ' . $anul . $total_luna . '</span> ';
        }
        
 
        echo '<li class="list-group-item ">'. '<span class="badge badge-secondary">' . $id_donatie . '</span>';
        echo '<strong>' . date("d M. Y", strtotime($data)) .'</strong> - ';
        echo $nume . ' ' . $prenume . ' - <a href="edit-donatie.php?id=' . $id_donatie . '&persoana=' . $nume . '-' . $prenume . '">[edit]</a>' . '<br>' ;
        echo $suma_lei . " lei" . ' - ' . $tip_donatie . ' - <span style="color:green">' . $scop_donatie . '</span> - ';

        if (empty($link_act)) {
            echo ' ' . $act_doveditor . ' ' . $nr_act_doveditor;
        } else {echo $act_doveditor . ' - <a href="' . $link_act . '">' . $nr_act_doveditor . "</a><br>";}

        if (empty($link_proces_verbal)){
            echo '<p><a href="proces-verbal-unic.php?iddonatie=' .$id_donatie .  '" class="btn btn-outline-primary btn-sm">creează PV</a></p>';
         } else {echo '<p>proces verbal: '.'<a href="' . $link_proces_verbal . '">' . $proces_verbal . "</a></p>";}
     
        echo "</li>";
        
    }
}
 


echo "</ul>";



 



include "footer.php";
?>












