<?php
defined("_VALID_ACCESS") || die('Direct access forbidden'); // This is a security feature.

class Premium_Ical extends Module { // Note, how the class' name reflects module's path.

	public function admin() {
		if($this->is_back()) {
			if($this->parent->get_type()=='Base_Admin')
				$this->parent->reset();
			else
				location(array());
			return;
		}

		$form = $this->init_module('Libs/QuickForm');
		$defaults = array();
		$defaults['mysql_srv'] = Variable::get('mysql_srv');
		$defaults['mysql_db'] = Variable::get('mysql_db');
		$defaults['mysql_username'] = Variable::get('mysql_username');
		$defaults['mysql_password'] = Variable::get('mysql_password');
		$form->setDefaults($defaults);

		//Form Layout
		$form->addElement('header',null, __('Ical Export'));
		$form->addElement('text','mysql_srv', __('Server IP'));
		$form->addRule('mysql_srv', __('Field required'), 'required');
		$form->addElement('text','mysql_db', __('CRM Database'));
		$form->addRule('mysql_db', __('Field required'), 'required');
		$form->addElement('text','mysql_username', __('MySQL Username'));
		$form->addRule('mysql_username', __('Field required'), 'required');
		$form->addElement('text','mysql_password', __('MySQL Password'));
		$form->addRule('mysql_password', __('Field required'), 'required');
		Base_ActionBarCommon::add('back', __('Back'), $this->create_back_href());
                Base_ActionBarCommon::add('save', __('Save'), $form->get_submit_form_href());
		 if($form->getSubmitValue('submited') && $form->validate() && $form->process(array(&$this,'submit_admin'))) {
                        Base_StatusBarCommon::message(__('Settings saved'));
                }
                $form->display();	
	}

	public function submit_admin($data) {
		Variable::set('mysql_srv', $data['mysql_srv']);
		Variable::set('mysql_db', $data['mysql_db']);
		Variable::set('mysql_username', $data['mysql_username']);
		Variable::set('mysql_password', $data['mysql_password']);
		return true;
	}			
	
         public function body(){
	  $srv = Variable::get('mysql_srv');
	  $musr = Variable::get('mysql_username');
	  $pwd = Variable::get('mysql_password');
	  $db =  Variable::get('mysql_db');
          $usr = ACL::get_user();
	  $loc = Base_User_SettingsCommon::get('Base_RegionalSettings','tz');	

		Base_ActionBarCommon::add('print',__('Export Calendar'), "href='modules/Premium/Ical/ical.php?_employeeid=$usr&_me=1&srv=$srv&usr=$musr&pwd=$pwd&db=$db&loc=$loc&_ts=1&_pc=1'");
  }

} 
?>
