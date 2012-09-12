<?php


require_once(GLPI_ROOT.'/inc/dbmysql.class.php');
require_once(GLPI_ROOT.'/config/config_db.php');
require_once(GLPI_ROOT.'/inc/includes.php');

$dbParams = new DB();

$db = new PDO("mysql:host=$dbParams->dbhost;dbname=$dbParams->dbdefault", $dbParams->dbuser, $dbParams->dbpassword);


function plugin_NetworkConnections_install()
{
   $dbParams = new DB();

   $db = new PDO("mysql:host=$dbParams->dbhost;dbname=$dbParams->dbdefault", $dbParams->dbuser, $dbParams->dbpassword);
   /*
   require_once(GLPI_ROOT.'/inc/dbmysql.class.php');
   require_once(GLPI_ROOT.'/config/config_db.php');
   require_once(GLPI_ROOT.'/inc/includes.php');

   $dbParams = new DB();

   $db = new PDO("mysql:host=$dbParams->dbhost;dbname=$dbParams->dbdefault", $dbParams->dbuser, $dbParams->dbpassword);
    */

   $sql = "CREATE TABLE glpi_plugin_networkconnections_uploadedfiles (
              id INT NOT NULL AUTO_INCREMENT,
              name TEXT,
              upload_date DATETIME,
              PRIMARY KEY(id)
           )";

   $db->exec($sql);

   return true;

}

function plugin_NetworkConnections_uninstall()
{
   $dbParams = new DB();

   $db = new PDO("mysql:host=$dbParams->dbhost;dbname=$dbParams->dbdefault", $dbParams->dbuser, $dbParams->dbpassword);

   $sql = "DROP TABLE glpi_plugin_networkconnections_uploadedfiles";

   $db->exec($sql);

   return true;
}


?>
