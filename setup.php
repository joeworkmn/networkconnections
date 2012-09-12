<?php
   function plugin_init_NetworkConnections() 
   {
      global $PLUGIN_HOOKS, $CFG_GLPI, $LANGEXAMPLE,$LANG;


      $activeProfile = $_SESSION['glpiactiveprofile']['name'];
      if ($activeProfile == 'super-admin' || $activeProfile == 'admin') {
         $PLUGIN_HOOKS['menu_entry']['NetworkConnections'] = true;
         //$PLUGIN_HOOKS['config_page']['NetworkConnections']     = 'index.php';
         //$PLUGIN_HOOKS['submenu_entry']['NetworkConnections']['add'] = 'index.php';
         //$PLUGIN_HOOKS["helpdesk_menu_entry"]['NetworkConnections'] = true;
      }
   }
   
   function plugin_version_NetworkConnections()
   {
      return array( 
         'name'    => 'NetworkConnections',
         'version' => '0.1.0'
         );
   }
   
   function plugin_NetworkConnections_check_prerequisites()
   {
	   if (GLPI_VERSION>=0.72){
		   return true;
	   } else {
		   echo "GLPI version not compatible need 0.72";
	   }
   }
   
   function plugin_NetworkConnections_check_config()
   {
	   return true;
   }

?>
