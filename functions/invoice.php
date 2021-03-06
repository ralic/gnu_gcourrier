<?php
/*
GCourrier
Copyright (C) 2009  Cliss XXI

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

function invoice_getbyid($id) {
  $req = "SELECT id, montant, refFacture, dateFacture,
            dateFactureOrigine, observation, validite,
            dateArchivage, idFournisseur, idServiceCreation,
            idPriorite, histo, refuse,
            UNIX_TIMESTAMP(dateSaisie) AS internal_timestamp
          FROM facture
          WHERE id = '" . mysql_real_escape_string($id) . "'";
  $result = mysql_query($req) or die(mysql_error());
  $line = mysql_fetch_assoc($result);
  
  return $line;
}

function invoice_delete($id) {
  db_execute('DELETE FROM facture WHERE id=?', array(intval($id)));
}
