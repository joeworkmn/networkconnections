<?php
 if (!defined('GLPI_ROOT')) {
     define('GLPI_ROOT', '../../../');
 }

require_once(GLPI_ROOT.'inc/dbmysql.class.php');
require_once(GLPI_ROOT.'config/config_db.php');
require_once(GLPI_ROOT.'inc/includes.php');

$dbParams = new DB();

$db = new PDO("mysql:host=$dbParams->dbhost;dbname=$dbParams->dbdefault", $dbParams->dbuser, $dbParams->dbpassword);

//fetchSwitchPorts();

echo excludedHosts();



// Returns network ports of the fake switch
function fetchSwitchPorts()
{
   global $db;
   $stmt = $db->query("SELECT np.id AS np_id FROM glpi_networkequipments AS ne 
      JOIN glpi_networkports AS np ON (ne.id = np.items_id AND itemtype = 'NetworkEquipment') 
      WHERE ne.name = 'hccmwc fake switch'");

   //var_dump($stmt);

   $count = 0;
   while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

      $count++;
   
   }
   echo "$count\n";
}


/*
 * Reads in the dhcpd.conf file and write to hosts.txt
*/
function makeHostsArray($dhcpconf)
{
   //$infile = fopen("dhcpd.conf.portl0cac", 'r');
   $infile = fopen($dhcpconf, 'r');
   $hosts = array();
   while($line = fgets($infile)) {
      $line = trim($line);

      $line = str_replace(";", "", $line);
      $tokens = preg_split('/\s+/', $line);
      if ($tokens[0] === "host") {

         $hosts[] = array('name' => $tokens[1], 'ip' => $tokens[4], 'mac' => $tokens[7]);

         //$name = $tokens[1];
         //$ip = $tokens[4];
         //$mac = $tokens[7];

         //fwrite($outfile, "$host, $ip, $mac\n");
      
      }
   
   }
   fclose($infile);

   return $hosts;
}


function fetchHostPorts($hosts)
{
    global $db;
    $sql = "SELECT id, mac FROM glpi_networkports WHERE itemtype = 'Computer' AND mac IN (";

    for ($i = 0; $i < count($hosts); $i++) {

       // mac address.
       $mac = $hosts[$i]['mac'];

       // if NOT last host in array.
       if ($i !== count($hosts) - 1) {
          $sql .= "$mac, ";
       } else {
          $sql .= "$mac)";
       }
    
    }

    $stmt = $db->query($sql);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



function excludedHosts($hosts)
{

   global $db;
   
   //$excludedHosts = "<div class='excludedHosts'> <b><u> Hosts not in GLPI </u></b><br /><br />";
   $excludedHosts = "<div class='excludedHosts'> <b><u> Hosts not in GLPI </u></b>\n\n";

   // All computers in GLPI.
   $stmt = $db->query("SELECT name FROM glpi_computers");

   // Foreach host in dhcpd.conf
   foreach ($hosts as $h) {
      $inDb = false;

      while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
         if ($h['name'] === $row['name']) {
            $inDb = true;
         }
      }

      if (!$inDb) {
         //$excludedHosts .= $h['name'] . "<br /><br />";
         $excludedHosts .= $h['name'] . "\n\n";
      }

   
   }

   $excludedHosts .= "</div>";

   return $excludedHosts;

}

?>
