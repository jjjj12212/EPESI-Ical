<?php
defined("_VALID_ACCESS") || die('Direct access forbidden');

class Premium_IcalInstall extends ModuleInstall {

	public function install() {
// Here you can place installation process for the module
		$ret = true;
		if($ret) $ret = Variable::set('mysql_srv', '127.0.0.1');
		if($ret) $ret = Variable::set('mysql_db', 'DATABASE');
		if($ret) $ret = Variable::set('mysql_username', 'root');
		if($ret) $ret = Variable::set('mysql_password', 'password');
		DB::CreateTable("ical_hashlist", "hash C(64) KEY, logged_user_id I(5) NOTNULL, _me I1, _pc I1, _ts I1, location C(25), domain C(25)"); 
		Utils_RecordBrowserCommon::register_processing_callback('crm_calendar',array('Samco_IcalCommon','add_action_bar'));
		return $ret; // Return false on success and false on failure
	}
	
	public function uninstall() {
// Here you can place uninstallation process for the module
		$ret = true;
		if($ret) $ret = Variable::delete('mysql_srv');
		if($ret) $ret = Variable::delete('mysql_db');
		if($ret) $ret = Variable::delete('mysql_username');
		if($ret) $ret = Variable::delete('mysql_password');
		DB::DropTable("ical_hashlist");
		return $ret; // Return false on success and false on failure
	}

	public function info() {
// Returns basic information about the module which will be available in the epesi Main Setup
		return array(	'Author'=>'jjjj12212 & Zumiani',
                                'License'=>'GPL version 3',
                                'Description'=>'Export Your EPESI CRM Calendar to your Calendar Client');
	}
	
	public function simple_setup() {
// Indicates if this module should be visible on the module list in Main Setup's simple view
		return true; 
	}

	public function requires($v) {
// Returns list of modules and their versions, that are required to run this module
		return array(); 
	}
	
	public function version() {
// Return version name of the module
		return array('0.1'); 
	}
}

?>
