<?php
/*
GCourrier
Copyright (C) 2005, 2006  Cliss XXI

This file is part of GCourrier.

GCourrier is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

GCourrier is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

author VELU Jonathan
*/

require_once('init.php');
?>

<html>
<head><title>gCourrier</title>
<LINK HREF="styles2.css" REL="stylesheet">
</head>
<body>
<div id = pageGd><br>
<center>
<img src = images/banniere2.jpg><br><br>
</center>

<?php
$log = $_SESSION['login'];
$idCourrier =  $_GET['idCourrier'];
echo"<center>HISTORIQUE DU COURRIER NUMERO : ".$idCourrier."</center><br><br>";

$boul = 0;
echo "<table align=center>";
echo "<tr>";
echo "<tr>";
echo "<td align=center>service</td>";
echo "<td align=center>date de transmission</td>";
echo "<td align=center>date de retour</td>";
echo"</tr>";

$requete ="SELECT estTransmisCopie.dateTransmission as dateTransmission,
		  estTransmisCopie.dateRetour as dateRetour,
		  service.libelle as libelle,
		  service.designation as designation,
		  estTransmisCopie.id as idTransmis
	   FROM facture,estTransmisCopie,service
           WHERE facture.id=".$idCourrier."
	   AND facture.id = estTransmisCopie.idFacture
	   AND estTransmisCopie.idService = service.id;";
$result = mysql_query( $requete ) or die (mysql_error() );
while ( $ligne = mysql_fetch_array($result) ){

	if($boul == 0){
		$couleur = 'lightblue';
		$boul = 1;
	}
	else{
		$couleur = 'white';
		$boul = 0;	
	}


		echo "<tr>";	
	$date = "";	
	$date .= substr($ligne['dateTransmission'],8,2);
	$date .= "-";
	$date .= substr($ligne['dateTransmission'],5,2);
	$date .= "-";
	$date .= substr($ligne['dateTransmission'],0,4);
	
	$service = $ligne['libelle']." ".$ligne['designation'];
	$dateModif = $ligne['dateTransmission'];
	$dateRetour = $ligne['dateRetour'];

	echo "<td bgcolor=".$couleur.">".$service."</td>";
	echo "<td bgcolor=".$couleur.">".$dateModif."</td>";
	if(strcmp($dateRetour , '0000-00-00') == 0)
		echo "<td bgcolor=".$couleur."><a href=dateRetour.php?idCourrier=".$ligne['idTransmis']."&flag=1>ajouter</a></td>";
	else
		echo "<td bgcolor=".$couleur.">".$dateRetour."</td>";
}
echo "</table><br>";

?>
<center><a href="javascript:history.go(-1)"> <b>retourner au resultat</b></a>
</div>
</body>
</html>
