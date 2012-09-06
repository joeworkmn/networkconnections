<?php
	$host = "localhost";
	$user = "root";
	$pass = "batman";
	$dbname = "glpi";

	$infile = fopen("hosts.txt", 'r');

	$linecount = countlines($infile); // total number of lines in file.
	$linenum = 1; // line we're currently on.

	$db = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);

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

                if ($r['items_id'] === null) {
                    // Add network port?
                    echo "$hName: no network port\n";
                } elseif ($hIp !== $rIp || $hMac !== $rMac) {
                    // Update ip and mac?
                    echo "$hName: non matching addresses, $hIp, $rIp, $hMac, $rMac\n";
                } else {
                    // Set $assigned to true if necessary.
                    echo "$hName: matching addresses, $hIp, $rIp, $hMac, $rMac\n";
                }

                //echo $hName . ': ' . $rName . "\n";
            }

        }

        if (!$inDb) {
           echo "$hName is not in db\n";
        }
    }


//	$stmt = $db->prepare("SELECT name FROM glpi_computers WHERE name = :name");
//
//	$data = array(":name" => "00LIBA");
//
//	$stmt->execute($data);
//
//	while ($row = $stmt->fetch()) {
//	    echo $row['name'] . "\n";
//	}



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
