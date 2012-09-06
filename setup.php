<?php
   function plugin_init_dhcp() 
   {
      global $PLUGIN_HOOKS, $CFG_GLPI, $LANGEXAMPLE,$LANG;


      $activeProfile = $_SESSION['glpiactiveprofile']['name'];
      if ($activeProfile == 'super-admin' || $activeProfile == 'admin') {
         $PLUGIN_HOOKS['menu_entry']['dhcp'] = true;
         //$PLUGIN_HOOKS['config_page']['dhcp']     = 'index.php';
         //$PLUGIN_HOOKS['submenu_entry']['dhcp']['add'] = 'index.php';
         //$PLUGIN_HOOKS["helpdesk_menu_entry"]['dhcp'] = true;
      }
   }
   
   function plugin_version_dhcp()
   {
      return array( 
         'name'    => 'dhcp',
         'version' => '0.1.0'
         );
   }
   
   function plugin_dhcp_check_prerequisites()
   {
	   if (GLPI_VERSION>=0.72){
		   return true;
	   } else {
		   echo "GLPI version not compatible need 0.72";
	   }
   }
   
   function plugin_dhcp_check_config()
   {
	   return true;
   }

?>
