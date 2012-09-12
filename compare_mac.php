<?php

 if (!defined('GLPI_ROOT')) {
     define('GLPI_ROOT', '../../');
 }

 require_once(GLPI_ROOT.'inc/dbmysql.class.php');
 require_once(GLPI_ROOT.'config/config_db.php');
 require_once(GLPI_ROOT.'inc/includes.php');

 if (isset($_FILES['uploadedfile']['tmp_name'])) {
    $uploadedfile = $_FILES['uploadedfile'];

    // Used as the file resource.
    $file = $_FILES['uploadedfile']['tmp_name'];

    //die(var_dump($_FILES['uploadedfile']));

    $dbParams = new DB();

    $db = new PDO("mysql:host=$dbParams->dbhost;dbname=$dbParams->dbdefault", $dbParams->dbuser, $dbParams->dbpassword);

    // Array of hosts from dhcpd.conf
    $hosts = makeHostsArray($file); 

    HTML::header('NetworkConnections', '', 'plugins', 'NetworkConnections');

    echo "<head> <link rel='stylesheet' type='text/css' href='css/main.css'/> </head>";

    echo "<div class='myPlugin'>";

    // Array of rows from glpi_networkports.
    $hostPorts = fetchHostPorts($hosts);

    // PDO statement.
    $switchPorts = fetchSwitchPorts(); 

    echo "<div class='newlyConnected'>";

    makeConnections($hostPorts, $switchPorts);

    echo "</div>";

    logFileUpload($uploadedfile);

    $excludedHosts = excludedHosts($hosts);
    echo $excludedHosts;

    echo "</div>";
    echo "<div class='clear'></div>";

    HTML::footer();
 
 }



/* FUNCTIONS */


/*
 * Reads in the dhcpd.conf file and returns an array of the hosts.
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


/* 
 * Fetches all host networkports from GLPI that aren't 
 * already connected, and which matches the mac address of a
 * host in the dhcpd.conf file.
 *
 * @param $hosts array Hosts from dhcpd.conf
 *
 */
function fetchHostPorts($hosts)
{
    global $db;
    //$sql = "SELECT id, mac FROM glpi_networkports WHERE itemtype = 'Computer' AND mac IN (";
    
    //$sql = "SELECT c.name AS cname, n.id, n.mac FROM glpi_computers c 
    //          JOIN glpi_networkports n ON c.id = n.items_id AND n.itemtype = 'Computer'
    //          WHERE n.mac IN(";

    $sql = "SELECT c.name AS cname, n.id, n.mac, n.ip, np_np.networkports_id_1 FROM glpi_computers c 
              JOIN glpi_networkports n ON (c.id = n.items_id AND n.itemtype = 'Computer')
              LEFT JOIN glpi_networkports_networkports np_np ON (n.id = np_np.networkports_id_1)
              WHERE n.mac IN(";

    for ($i = 0; $i < count($hosts); $i++) {

       // mac address.
       $mac = $hosts[$i]['mac'];

       // if NOT last host in array.
       if ($i !== count($hosts) - 1) {
          $sql .= "'$mac', ";
       } else {
          $sql .= "'$mac')";
       }
    
    }

    $sql .= " AND np_np.networkports_id_1 IS NULL"; 

    $stmt = $db->query($sql);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/*
 * Returns AVAILABLE network ports (ones that aren't already connected to a host) of the fake switch.
 *
 * @return array
 */
function fetchSwitchPorts()
{
   global $db;

   $stmt = $db->query("SELECT np.id AS np_id, np.name, np_np.networkports_id_2 FROM glpi_networkequipments AS ne 
      JOIN glpi_networkports AS np ON (ne.id = np.items_id AND np.itemtype = 'NetworkEquipment') 
      LEFT JOIN glpi_networkports_networkports AS np_np ON (np.id = np_np.networkports_id_2)
      WHERE (ne.name = 'hccmwc fake switch' AND np_np.networkports_id_2 IS NULL)");

   $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

   return $rows;
}



/*
 * Connects a host network port to an available switch port
 * by inserting the pair of IDs into glpi_networkports_networkports.
 *
 */
function connect($hostPortId, $swPortId)
{
   global $db;
   $stmt = $db->prepare("INSERT INTO glpi_networkports_networkports (networkports_id_1, networkports_id_2)
                   VALUES (:portId1, :portId2)");

   $result = $stmt->execute( array('portId1' => $hostPortId, 'portId2' => $swPortId) );

   return $result;
}



/*
 * Iterates through the hostports and uses connect() 
 * to connect the host port to the next available switchport.
 * Then echoes the resulting connection.
 *
 * @param $hostPorts Array; The host ports to connect.
 * @param $switchPorts Array; The switch ports being connected to.
 */
function makeConnections($hostPorts, $switchPorts) 
{
    foreach ($hostPorts as $h) {

        // host networkport id.
        $hostPortId = $h['id'];
        $hostIp = $h['ip'];
        $octets = explode('.', $hostIp);

        //die(var_dump($octets));

        foreach ($switchPorts as $sp) {
           $swPortId = $sp['np_id'];

           // if fourth octet of ip matches port name, then connect it.
           if ($octets[3] === $sp['name']) {
              
              // connect the host port to the switch port.
              if (connect($hostPortId, $swPortId)) {

                 //echo $h['cname'] . " has been connected to port: " . $swPort['name'] . "";
                 echo "<p>" . $h['cname'] . " has been connected to port: " . $sp['name'] . "</p> <br />";

              }
           }
        
        }

    }

}


/*
 * Creates a list of hosts which are in dhcpd.conf but not in GLPI
 * and puts it in a <div>.
 *
 * @param $hosts Array; An array of hosts from dhcpd.conf.
 *
 * @returns String; A list of hosts in an HTML <div>.
 *
 */
function excludedHosts($hosts)
{
   global $db;
   
   $excludedHosts = "<div class='excludedHosts'> <b><u> Hosts not in GLPI </u></b><br /><br />";
   //$excludedHosts = "<div class='excludedHosts'> <b><u> Hosts not in GLPI </u></b>\n\n";

   // All computers in GLPI.
   $stmt = $db->query("SELECT name FROM glpi_computers");

   // Foreach host in dhcpd.conf
   foreach ($hosts as $h) {
      $inDb = false;

      while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
         if (trim(strtolower($h['name'])) === trim(strtolower($row['name']))) {
            $inDb = true;
         }
      }

      if (!$inDb) {
         $excludedHosts .= $h['name'] . "<br /><br />";
         //$excludedHosts .= $h['name'] . "\n\n";
      }
   }

   $excludedHosts .= "</div>";

   return $excludedHosts;

}


/*
 * Inserts the uploaded filename into the database, or
 * updates if already exists.
 *
 *
 */
function logFileUpload($file)
{
   global $db;
   $filename = $file['name'];

   // Bound value for filename.
   $fileBinding = array('filename' => $filename);

   // Check if file name is already in table.
   $select = "SELECT name from glpi_plugin_networkconnections_uploadedfiles WHERE name = :filename";
   $selStmt = $db->prepare($select);
   $selStmt->execute($fileBinding);
   $row = $selStmt->fetchAll();

   // If not in table, insert.
   if (empty($row)) {
      $insert = "INSERT INTO glpi_plugin_networkconnections_uploadedfiles (name, upload_date)
                 VALUES (:filename, NOW())";
      $insStmt = $db->prepare($insert);
      $insStmt->execute($fileBinding);
   // else, update.
   } else {
      $update = "UPDATE glpi_plugin_networkconnections_uploadedfiles set upload_date = NOW() 
                    WHERE name = :filename";
      $updateStmt = $db->prepare($update);
      $updateStmt->execute($fileBinding);
   }

}



?>
