<?php
/*
Mail encapsulation
Copyright (C) 2010  Cliss XXI

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
require_once(dirname(__FILE__) . '/../classes/SQLDataGrid.php');
require_once('functions/status.php');
require_once('functions/priority.php');

function mail_get_replies($id) {
  $ret = array();

  $res = db_execute("SELECT mail_new_id FROM mail_reply WHERE mail_old_id = ?",
		    array($id));
  while ($row = mysql_fetch_array($res))
    array_push($ret, intval($row['mail_new_id']));

  return $ret;
}

function mail_get_origins($id) {
  $ret = array();

  $res = db_execute("SELECT mail_old_id FROM mail_reply WHERE mail_new_id = ?",
		    array($id));
  while ($row = mysql_fetch_array($res))
    array_push($ret, intval($row['mail_old_id']));

  return $ret;
}

function mail_exists($where, $where_params)
{
  $res = db_execute("SELECT id FROM courrier WHERE $where", $where_params);
  $row = mysql_fetch_array($res);
  return mysql_num_rows($res) > 0;
}

function mail_is_archived($id)
{
  $res = db_execute("SELECT validite AS archived FROM courrier WHERE id=?",
		    array(intval($id)));
  $row = mysql_fetch_array($res);
  return $row['archived'] == 1;
}

function mail_get_arrival_date($id)
{
  $res = db_execute("SELECT UNIX_TIMESTAMP(dateArrivee) AS arrival_date FROM courrier WHERE id=?",
		    array(intval($id)));
  $row = mysql_fetch_array($res);
  return $row['arrival_date'];
}

function mail_get_priority($id)
{
  $res = db_execute("SELECT idPriorite AS priority_id FROM courrier WHERE id=?",
		    array(intval($id)));
  $row = mysql_fetch_array($res);
  return $row['priority_id'];
}

// Only old value is stored in history to avoid duplication of
// information - this function return the values in a human-readable
// way
function mail_get_priority_history($id)
{
  $id = intval($id);
  $res = db_execute("SELECT UNIX_TIMESTAMP(event_timestamp) AS timestamp, old_value"
		    . " FROM mail_priority_history WHERE mail_id=?"
		    . " ORDER BY event_timestamp DESC",
		    array(intval($id)));

  $last = mail_get_priority($id);
  $ret = array();
  while ($row = mysql_fetch_array($res)) {
    $ret[$row['timestamp']] = $last;
    $last = $row['old_value'];
  }

  $first_date = mail_get_arrival_date($id);
  $ret[$first_date] = $last;
  return $ret;
}

function mail_set_priority($id, $priority_id)
{
  if (!priority_exists($priority_id))
    exit("Cette priorité n'existe pas.");

  db_autoexecute('mail_priority_history',
    array('mail_id' => intval($id),
          'old_value' => mail_get_priority($id)),
    DB_AUTOQUERY_INSERT);

  $res = db_execute("UPDATE courrier SET idPriorite=? WHERE id=?",
		    array(intval($priority_id), intval($id)));
  return $res;
}

function mail_reply_new($mail_old_id, $mail_new_id)
{
  $result = db_autoexecute('mail_reply',
    array('mail_old_id' => intval($mail_old_id),
          'mail_new_id' => intval($mail_new_id)),
    DB_AUTOQUERY_INSERT);
  return $result;
}

function mail_display_simple($ids)
{
    $query = "SELECT courrier.id AS mail_id, libelle AS label, CONCAT(nom, ' ', prenom) AS contact_name,"
      . " UNIX_TIMESTAMP(dateArrivee) AS date_here,"
      . " type, validite AS archived "
      . " FROM courrier JOIN destinataire ON courrier.idDestinataire = destinataire.id"
      . " WHERE courrier.id IN (" . join(',', $ids) . ")";

    function printId($params)
    {
      extract($params);
      $archived = '';
      if ($record['archived'] == 1)
	$archived = "type=archived=1&";
      return "<a href='mail_list.php?{$archived}type={$record['type']}"
	. "&idCourrierRecherche={$record[$fieldName]}"
	. "&rechercher=1#result'>{$record[$fieldName]}</a>";
    }

    $config = array();
    $config['No'] =
      array('sqlcol' => 'mail_id',
	    'callback' => 'printId');
    $config['Libellé'] =
      array('sqlcol' => 'label',
	    'callback' => 'printText');
    $config['Destinataire'] =
      array('sqlcol' => 'contact_name',
	    'callback' => 'printText');
    $config['Date Mairie'] =
      array('sqlcol' => 'date_here',
	    'callback' => 'printDate');

    $sdg = new SQLDataGrid($query, $config);
    $sdg->setDefaultSort(array('mail_id' => 'ASC'));
    $sdg->setClass('resultats');
    $sdg->display();
}

function mail_query_attachments($id) {
  $res = db_execute("SELECT id, filename FROM mail_attachment WHERE mail_id = ?",
		    array($id));
  return $res;
}

function mail_get_upload_dir($id) {
  $id = intval($id); // avoid '..' (for example)
  if ($id == 0)
    die('mail_get_upload_dir: invalid mail id');
  return "upload/courrier/$id";
}

function mail_attachment_get_path($attachment_id) {
  $res = db_execute("SELECT id, mail_id, filename FROM mail_attachment WHERE id = ?",
		    array($attachment_id));
  $row = mysql_fetch_array($res);
  if (strpos($row['filename'], '/') !== false)
    exit('Nom de fichier invalide');

  return mail_get_upload_dir($row['mail_id']) . '/' . $row['filename'];
}

function mail_attachment_delete($attachment_id) {
  $res = db_execute("SELECT mail_id, filename FROM mail_attachment WHERE id = ?",
		    array($attachment_id));
  $row = mysql_fetch_array($res);
  $id = intval($row['mail_id']);
  $filename = $row['filename'];
  if (mail_is_archived($id))
    exit('Cette pièce jointe est rattachée à un courrier archivé');

  $attachment_id = intval($attachment_id);
  $path = mail_attachment_get_path($attachment_id);
  @unlink($path);

  $res = db_execute("DELETE FROM mail_attachment WHERE id = ?",
		    array($attachment_id));  

  db_autoexecute('mail_history',
		 array('mail_id' => intval($id),
		       'service_id' => $_SESSION['idService'],
		       'message' => "Suppression de la PJ $filename",
		       ), DB_AUTOQUERY_INSERT);
  return $res;
}

function mail_attachment_new($id, $tmp_file, $filename)
{
  $new_id = 0;

  $old_umask = umask(0);
	
  $content_dir = mail_get_upload_dir($id); // dossier où sera déplacé le mail_file
  if (!file_exists($content_dir))
    mkdir($content_dir, 0755, true) or die("Impossible de créer $content_dir");
	
  // on copie le mail_file dans le dossier de destination
  if (strpos($filename, '/') !== false)
    exit('Nom de fichier invalide');
  $dest_file = "$content_dir/$filename";
  if (!rename($tmp_file, $dest_file)) {
    exit("Impossible de copier $tmp_file dans $dest_file");
  } else {
    // Give permissions to other users, including Apache. This is
    // necessary in a suPHP setup.
    chmod($dest_file, 0644);
    
    $res = db_execute('SELECT id FROM mail_attachment WHERE mail_id=? AND filename=?',
		      array($id, $filename));
    
    if (mysql_num_rows($res) == 0) {
      db_autoexecute('mail_attachment',
		     array('mail_id' => intval($id),
			   'filename' => $filename,
			   ), DB_AUTOQUERY_INSERT);
      $new_id = mysql_insert_id();
      status_push('Fichier joint au courrier');

      db_autoexecute('mail_history',
		     array('mail_id' => intval($id),
			   'service_id' => $_SESSION['idService'],
			   'message' => "Ajout de la PJ $filename",
			   ), DB_AUTOQUERY_INSERT);
    } else {
      status_push('Pièce jointe écrasée');

      db_autoexecute('mail_history',
		     array('mail_id' => intval($id),
			   'service_id' => $_SESSION['idService'],
			   'message' => "Écrasement de la PJ $filename",
			   ), DB_AUTOQUERY_INSERT);
    }
  }
  
  umask($old_umask);

  return $new_id;
}

function mail_handle_attachment($id) {
  if (mail_is_archived($id))
    exit("L'ajout de pièces jointes est désactivé pour les courriers archivés");

  // If a file was uploaded
  if (isset($_FILES['mail_file'])) {
    if ($_FILES['mail_file']['error'] == UPLOAD_ERR_OK) {
      mail_attachment_new($id,
			  $_FILES['mail_file']['tmp_name'],
			  $_FILES['mail_file']['name']);
    } elseif ($_FILES['mail_file']['error'] != UPLOAD_ERR_NO_FILE) {
      $msg = "Erreur lors de l'envoi du fichier {$_FILES['userfile']['name']}"
	. " (erreur {$_FILES['mail_file']['error']}: ";
      switch ($_FILES['mail_file']['error']) {
      case UPLOAD_ERR_INI_SIZE:
      case  UPLOAD_ERR_FORM_SIZE:
	$msg .= "le fichier est trop volumineux";
	break;
      case UPLOAD_ERR_PARTIAL:
	$msg .= "envoi incomplet";
	break;
      case UPLOAD_ERR_NO_TMP_DIR:
	$msg .= "répertoire temporaire manquant";
	break;
      case UPLOAD_ERR_CANT_WRITE:
	$msg .= "erreur d'écriture";
	break;
      case UPLOAD_ERR_EXTENSION:
	$msg .= "extension de fichier interdite";
	break;
      }
      $msg .= ")";
      status_push($msg);
    }
  }
}


/**
 * Historique unifié
 */
function mail_get_history($id)
{
  $id = intval($id);
  $res = db_execute("SELECT UNIX_TIMESTAMP(event_timestamp) AS timestamp, service_id, message"
		    . " FROM mail_history WHERE mail_id=?"
		    . " ORDER BY event_timestamp",
		    array(intval($id)));

  $ret = array();
  while ($row = mysql_fetch_array($res))
    $ret[$row['timestamp']] = $row;

  return $ret;
}
