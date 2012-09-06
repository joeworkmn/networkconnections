<?php
    if (!defined('GLPI_ROOT')) {
        define('GLPI_ROOT', '../../');
    }

    require_once(GLPI_ROOT.'inc/dbmysql.class.php');
    require_once(GLPI_ROOT.'config/config_db.php');
    require_once(GLPI_ROOT.'inc/includes.php');

    if (isset($_FILES['uploadedfile']['tmp_name'])) {
       $file = $_FILES['uploadedfile']['tmp_name'];
       $infile = fopen($file, 'r');
       $linecount = countlines($infile); // total number of lines in file.
       $linenum = 1; // line we're currently on.

       $dbParams = new DB();

       $db = new PDO("mysql:host=$dbParams->dbhost;dbname=$dbParams->dbdefault", $dbParams->dbuser, $dbParams->dbpassword);

       $sql = "SELECT c.id AS cid, c.name, n.items_id, n.ip, n.mac FROM glpi_computers c 
                LEFT JOIN glpi_networkports n ON c.id = n.items_id AND n.itemtype = 'Computer'
                WHERE c.name IN (";

       // array of hosts from dhcpd.conf
       $hosts = array();

       while ($line = fgets($infile)) {
           
           $tokens = explode(", ", $line);
           $name = $tokens[0];
           $ip = $tokens[1];
           $mac = $tokens[2];

           if ($linenum < $linecount) {
               $sql .= "'$name', ";
           } else {
               $sql .= "'$name')";
           }

           $hosts[] = array('name' => $name, 'ip' => $ip, 'mac' => $mac);

           $linenum++;
       }

       $res = $db->query($sql);
       $rows = $res->fetchAll(PDO::FETCH_ASSOC);

       HTML::header('dhcp', '', 'plugins', 'dhcp');

       // List of hostnames not in GLPI.
       $notInDbList = "<div class='notInDb'> <b> <u> Hosts not in GLPI </u> </b> <br /><br />";

       foreach ($hosts as $h) {
           $inDb = false;
           foreach ($rows as $r) {
               $assigned = false; // determines if host has correct addresses.
               $hName = strtoupper(trim($h['name'])); // host name from dhcpd.conf file.
               $rName = strtoupper(trim($r['name'])); // host name from GLPI.
               $hMac = strtoupper(trim($h['mac'])); // MAC from dhcpd.conf.
               $rMac = strtoupper(trim($r['mac'])); // MAC from GLPI.
               $hIp = trim($h['ip']); // IP from dhcpd.conf.
               $rIp = trim($r['ip']); // IP from GLPI.

               if ($hName === $rName) {
                   $inDb = true;
               }

               if ($hMac === $rMac) {
                   if ($hIp !== $rIp) {
                       // Update IP to match what is is dhcpd.conf
                       $stmt = $db->prepare("UPDATE glpi_networkports SET ip = :ip WHERE mac = :mac");
                       $stmt->execute(array('ip' => $hIp, 'mac' => $hMac));
                       //echo "$hName, $hMac: dhcpd.conf ip = $hIp, GLPI ip = $rIp <br />";
                   }
               }

           }

           if (!$inDb) {
              $notInDbList .= "$hName <br />";
           }
       }

       $notInDbList .= "</div>";
       echo $notInDbList;

       HTML::footer();
    
    
    }

   /* FUNCTIONS */

   function countlines($infile)
   {
       $linecount = 0;
       while ($line = fgets($infile)) {
	       $linecount++;    
	   }
       rewind($infile);

	   return $linecount;
   }
?>
