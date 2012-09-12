<?php
if (!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', '../../');
}
require_once(GLPI_ROOT.'inc/includes.php');
require_once(GLPI_ROOT.'inc/dbmysql.class.php');
require_once(GLPI_ROOT . 'config/config_db.php');

Html::header('NetworkConnections', '', 'plugins', 'NetworkConnections');

   echo '<div class="center">';
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

Html::footer();

?>
