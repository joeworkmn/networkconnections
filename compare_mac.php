<?php

 if (!defined('GLPI_ROOT')) {
     define('GLPI_ROOT', '../../');
 }

 require_once(GLPI_ROOT.'inc/dbmysql.class.php');
 require_once(GLPI_ROOT.'config/config_db.php');
 require_once(GLPI_ROOT.'inc/includes.php');

 if (isset($_FILES['uploadedfile']['tmp_name'])) {
    $file = $_FILES['uploadedfile']['tmp_name'];

    // Array of hosts from dhcpd.conf
    $hosts = makeHostsArray($file); 

    $dbParams = new DB();

    $db = new PDO("mysql:host=$dbParams->dbhost;dbname=$dbParams->dbdefault", $dbParams->dbuser, $dbParams->dbpassword);

    // Array of rows from glpi_networkports.
    $hostPorts = fetchHostPorts($hosts);

    HTML::header('dhcp', '', 'plugins', 'dhcp');

    // PDO statement.
    $switchPorts = fetchSwitchPorts(); 

    foreach ($hostPorts as $h) {

        // host networkport id.
        $hostPortId = $h['id'];

        // switch port row from GLPI.
        $swPort = $switchPorts->fetch(PDO::FETCH_ASSOC);

        // connect the host port to the switch port.
        if (connect($hostPortId, $swPort['np_id'])) {
           //echo $h['cname'] . " has been connected to port: " . $swPort['name'] . "<br /> <br />";
           echo "<p>" . $h['cname'] . " has been connected to port: " . $swPort['name'] . "</p> <br />";
        }

    }

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

    $sql = "SELECT c.name AS cname, n.id, n.mac, np_np.networkports_id_1 FROM glpi_computers c 
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
 * @return PDO statement object.
 */
function fetchSwitchPorts()
{
   global $db;

   $stmt = $db->query("SELECT np.id AS np_id, np.name, np_np.networkports_id_2 FROM glpi_networkequipments AS ne 
      JOIN glpi_networkports AS np ON (ne.id = np.items_id AND np.itemtype = 'NetworkEquipment') 
      LEFT JOIN glpi_networkports_networkports AS np_np ON (np.id = np_np.networkports_id_2)
      WHERE (ne.name = 'hccmwc fake switch' AND np_np.networkports_id_2 IS NULL)");

   return $stmt;
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

?>
