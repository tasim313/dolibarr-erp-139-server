<?php

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


class modDeliveryPoint extends DolibarrModules
{
	
	public function __construct($db)
	{
		global $langs, $conf;
		$this->db = $db;
		$this->numero = 500000; 
		$this->rights_class = 'deliverypoint';
		$this->family = "other";
		$this->module_position = '90';
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "DeliveryPointDescription";
		$this->descriptionlong = "DeliveryPointDescription";
		$this->editor_name = 'Editor name';
		$this->editor_url = 'https://www.example.com';
		$this->version = '1.0';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'generic';
		$this->module_parts = array(
			'triggers' => 1,
			'login' => 0,
			'substitutions' => 0,
			'menus' => 0,
			'tpl' => 0,
			'barcode' => 0,
			'models' => 0,
			'printing' => 0,
			'theme' => 0,
			'css' => array(
			),
			'js' => array(
			),
			'hooks' => array('invoicecard'),
			'moduleforexternal' => 0,
		);
		
		$this->dirs = array("/deliverypoint/temp");
		$this->config_page_url = array("setup.php@deliverypoint");
		$this->hidden = false;
		$this->depends = array();
		$this->requiredby = array(); 
		$this->conflictwith = array();
		$this->langfiles = array("deliverypoint@deliverypoint");
		$this->phpmin = array(5, 6); 
		$this->need_dolibarr_version = array(11, -3);
		$this->warnings_activation = array(); 
		$this->warnings_activation_ext = array(); 
		$this->const = array();
		if (!isset($conf->deliverypoint) || !isset($conf->deliverypoint->enabled)) {
			$conf->deliverypoint = new stdClass();
			$conf->deliverypoint->enabled = 0;
		}
		$this->tabs = array();
		$this->dictionaries = array();
		$this->boxes = array(
		);
		$this->cronjobs = array(
		);
		$this->rights = array();
		$r = 0;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); 
		$this->rights[$r][1] = 'Read objects of DeliveryPoint';
		$this->rights[$r][4] = 'myobject';
		$this->rights[$r][5] = 'read'; 
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); 
		$this->rights[$r][1] = 'Create/Update objects of DeliveryPoint'; 
		$this->rights[$r][4] = 'myobject';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); 
		$this->rights[$r][1] = 'Delete objects of DeliveryPoint';
		$this->rights[$r][4] = 'myobject';
		$this->rights[$r][5] = 'delete';
		$r++;
		$this->menu = array();
		$r = 0;
		$this->menu[$r++] = array(
			'fk_menu'=>'', 
			'type'=>'top', 
			'titre'=>'ModuleDeliveryPointName',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'deliverypoint',
			'leftmenu'=>'',
			'url'=>'/deliverypoint/deliverypointindex.php',
			'langs'=>'deliverypoint@deliverypoint', 
			'position'=>1000 + $r,
			'enabled'=>'$conf->deliverypoint->enabled', 
			'perms'=>'1', 
			'target'=>'',
			'user'=>2, 
		);
		$r = 1;
		$r = 1;
	}
	public function init($options = '')
	{
		global $conf, $user, $langs;

		// Initialize results and errors arrays (for hooks)
		$this->results = array();
		$this->errors = array();

		// Register triggers (hooks)
		include_once DOL_DOCUMENT_ROOT.'/core/class/interfaces.class.php';
		$interface = new Interfaces($this->db);
		$result = $interface->run_triggers('MODULE_DELIVERYPOINT_INIT', $this, $user, $langs, $conf);
		if ($result < 0) {
			$this->errors = $interface->errors;
			return -1;
		}

		// Original module setup (SQL, templates, etc.)
		$result = $this->_load_tables('/deliverypoint/sql/');
		if ($result < 0) return -1;

		$sql = array();
		$moduledir = dol_sanitizeFileName('deliverypoint');
		$myTmpObjects = array();
		$myTmpObjects['MyObject'] = array('includerefgeneration'=>0, 'includedocgeneration'=>0);

		foreach ($myTmpObjects as $myTmpObjectKey => $myTmpObjectArray) {
			if ($myTmpObjectKey == 'MyObject') continue;
			
			if ($myTmpObjectArray['includerefgeneration']) {
				$src = DOL_DOCUMENT_ROOT.'/install/doctemplates/'.$moduledir.'/template_myobjects.odt';
				$dirodt = DOL_DATA_ROOT.'/doctemplates/'.$moduledir;
				$dest = $dirodt.'/template_myobjects.odt';

				if (file_exists($src) && !file_exists($dest)) {
					require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
					dol_mkdir($dirodt);
					$result = dol_copy($src, $dest, 0, 0);
					if ($result < 0) {
						$langs->load("errors");
						$this->error = $langs->trans('ErrorFailToCopyFile', $src, $dest);
						return 0;
					}
				}

				$sql = array_merge($sql, array(
					"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = 'standard_".strtolower($myTmpObjectKey)."' AND type = '".$this->db->escape(strtolower($myTmpObjectKey))."' AND entity = ".((int) $conf->entity),
					"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('standard_".strtolower($myTmpObjectKey)."', '".$this->db->escape(strtolower($myTmpObjectKey))."', ".((int) $conf->entity).")",
					"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = 'generic_".strtolower($myTmpObjectKey)."_odt' AND type = '".$this->db->escape(strtolower($myTmpObjectKey))."' AND entity = ".((int) $conf->entity),
					"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('generic_".strtolower($myTmpObjectKey)."_odt', '".$this->db->escape(strtolower($myTmpObjectKey))."', ".((int) $conf->entity).")"
				));
			}
		}

		// Include our hook file
		include_once __DIR__.'/../triggers/deliverypoint_buttons.class.php';
		$conf->global->MAIN_ADD_JS[] = '/custom/deliverypoint/js/delivery_button.js';
    
		// Register hook
		$hookmanager->initHooks(array('invoicecard'));
		$hookmanager->addAction(
			'addMoreActionsButtons', 
			'DeliverypointButtons', 
			$this->db, 
			0
		);
		

		// Call parent init (essential for Dolibarr modules)
		return $this->_init($sql, $options);
	}
	public function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}
}
