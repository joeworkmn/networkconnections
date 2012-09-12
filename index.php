<?php
if (!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', '../../');
}
require_once(GLPI_ROOT.'inc/includes.php');
require_once(GLPI_ROOT.'inc/dbmysql.class.php');
require_once(GLPI_ROOT . 'config/config_db.php');

$dbParams = new DB();

$db = new PDO("mysql:host=$dbParams->dbhost;dbname=$dbParams->dbdefault", $dbParams->dbuser, $dbParams->dbpassword);

Html::header('NetworkConnections', '', 'plugins', 'NetworkConnections');

echo "<head> <link rel='stylesheet' type='text/css' href='css/main.css' /> </head>";

   prevFilesDiv();

   echo '<div class="center dhcpUpload">';
   if (isset($_SESSION['NetworkConnections']['result'])) {
      echo $_SESSION['NetworkConnections']['result'];
      unset($_SESSION['NetworkConnections']['result']);
   }
   echo    '<form enctype="multipart/form-data" action="compare_mac.php" method="POST">';
   echo       '<input type="hidden" name="MAX_FILE_SIZE" value="1000000"/>';
   echo       'Choose file to upload: <input name="uploadedfile" type="file" /> <br />';
   echo       '<input class="submit" type="submit" value="Upload"/>';
   echo    '</form>';
   echo '</div>';

echo "<div class='clear'></div>";
Html::footer();




/* FUNCTIONS */


/*
 * Fetches the previously uploaded files.
 *
 */
function fetchPrevFiles()
{
   global $db;

   $sql = "SELECT name, upload_date FROM glpi_plugin_networkconnections_uploadedfiles";

   $stmt = $db->query($sql);

   $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

   return $rows;
}


function prevFilesDiv()
{
   $prevFiles = fetchPrevFiles();

   echo "<div class='prevFiles'> <b><u> Previously uploaded files </b></u> <br /><br />";
   foreach ($prevFiles as $p) {
      $name = $p['name'];
      $date = $p['upload_date'];
      echo "$name on: $date <br /><br />";
   }
   echo "</div>";

}

?>




