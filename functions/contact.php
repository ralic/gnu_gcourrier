<?php
/*
Contact input form with auto-completion
Copyright (C) 2007, 2010  Cliss XXI

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
*/

require_once(dirname(__FILE__) . '/db.php');

function contact_display($default_id=NULL) {
  $default_name = "";
  if ($default_id != NULL)
    {
      $result = db_execute('SELECT nom, prenom FROM destinataire'
			   . ' WHERE id=?', array($default_id));
      $row = mysql_fetch_array($result);
      $default_name = $row['nom'] . ' ' . $row['prenom'];
    }
?>
<input type="hidden" name="contact_id" id="contact_id" value="<?php echo $default_id; ?>" />
<div style="font-size: smaller; position: relative; text-align: left;">
  Utilisez % comme joker de recherche
  - <a href="creerDestinataire.php">Créer un nouveau</a>
  <div style="position:absolute; top: 0; right: 0; display: none;" id="indicator1">En cours...</div>
</div>
<input type="text" id="autocomplete" name="autocomplete_parameter" size=50 value="<?php echo $default_name; ?>"/>
<div id="autocomplete_choices" class="autocomplete"></div>
<script type="text/javascript" language="javascript">
// <![CDATA[
  new Ajax.Autocompleter("autocomplete", "autocomplete_choices", "completion-recipients.php", {
    indicator: 'indicator1', afterUpdateElement : getSelectionId});

function getSelectionId(text, li) {
  document.getElementById('contact_id').value = li.id;
}
// ]]>
</script>
<?php
}

function contact_get_references($id) {
  $ret = array();

  $res = db_execute("SELECT COUNT(*) FROM courrier WHERE type=1 AND idDestinataire=?",
		    array($_GET['contact_id']));
  $row = mysql_fetch_array($res);
  $count = $row[0];
  array_push($ret, $count);

  $res = db_execute("SELECT COUNT(*) FROM courrier WHERE type=2 AND idDestinataire=?",
		    array($_GET['contact_id']));
  $row = mysql_fetch_array($res);
  $count = $row[0];
  array_push($ret, $count);

  $res = db_execute("SELECT COUNT(*) FROM facture WHERE idFournisseur=?",
		    array($_GET['contact_id']));
  $row = mysql_fetch_array($res);
  $count = $row[0];
  array_push($ret, $count);

  return $ret;
}

function contact_is_deletable($id) {
  $refs = contact_get_references($id);
  if (array_sum($refs) > 0)
    return false;
  else
    return true;
}
