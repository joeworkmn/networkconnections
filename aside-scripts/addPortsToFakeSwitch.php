<?php

if (!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', '../../../');
}

require_once(GLPI_ROOT.'inc/dbmysql.class.php');
require_once(GLPI_ROOT.'config/config_db.php');

$dbParams = new DB();

$db = new PDO("mysql:host=$dbParams->dbhost;dbname=$dbParams->dbdefault", 
                          $dbParams->dbuser, $dbParams->dbpassword);


$stmt = $db->query("SELECT id FROM glpi_networkequipments WHERE name = 'hccmwc fake switch'");

$row = $stmt->fetch(PDO::FETCH_ASSOC);

$id = $row['id'];

// used to name the ports of the switch.
//$portCounter = 1; 

$stmt = $db->prepare("INSERT INTO glpi_networkports (items_id, itemtype, name) 
              VALUES ($id, 'NetworkEquipment', :portname)");

for ($i = 1; $i <= 254; $i++) {

   $portname = $i;

   if ($i < 10) {
      $portname = "0$i";
   }

   $stmt->execute( array('portname' => "$portname") );
}

//var_dump($id);

?>
