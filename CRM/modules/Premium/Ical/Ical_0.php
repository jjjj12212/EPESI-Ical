<?php
defined("_VALID_ACCESS") || die('Direct access forbidden'); // This is a security feature.

class Premium_Ical extends Module { // Note, how the class' name reflects module's path.

	
         public function body(){
		//Base_ActionBarCommon::add('print',__('Export Calendar'), Base_BoxCommon::main_module_instance()->create_callback_href(array('Samco_Ical', 'ical')));
          $usr = ACL::get_user();
          $get_hash = DB::Execute("SELECT hash FROM ical_hashlist WHERE logged_user_id = $usr");

		Base_ActionBarCommon::add('print',__('Export Calendar'), "href='modules/Samco/Ical/ical.php?uid=".$get_hash->fields['hash']."'");
  }
	public function settings()
	{

		$form = $this->init_module('Libs/QuickForm');

		//Form Layout
		$form->addElement('header',null, __('Ical Export'));
		$form->addElement('checkbox','_me', __('Meetings'));
		$form->addElement('checkbox','_pc', __('Phone Calls'));
		$form->addElement('checkbox','_ts', __('Tasks'));
		$form->addElement('text','domain', __('Domain'));
		Base_ActionBarCommon::add('back', __('Back'), $this->create_main_href("Base_User_Settings"));
                Base_ActionBarCommon::add('save', __('Save'), $form->get_submit_form_href());
		 if($form->getSubmitValue('submited') && $form->validate() && $form->process(array(&$this,'submit_settings'))) {
                        Base_StatusBarCommon::message(__('Settings saved'));
                }
                $form->display();	
	}

	public function submit_settings($data) {
		$new_hash = uniqid();
	        $user = Acl::get_user();
		$_me = $data['_me'];
		$_pc = $data['_pc'];
		$_ts = $data['_ts'];
		$location = Base_User_SettingsCommon::get('Base_RegionalSettings','tz');
		$_domain = $data['domain'];
		if(empty($_me))
		{
		 $_me = 0;
		}
		if(empty($_pc))
		{
		 $_pc = 0;
		}
		if(empty($_ts))
		{
		 $_ts = 0;
		}

		$old_rec = DB::Execute("SELECT * FROM ical_hashlist WHERE logged_user_id = $user");
		if($old_rec->EOF)
		{
		 DB::Execute("INSERT INTO ical_hashlist (`hash`, `logged_user_id`, `_me`, `_pc`, `_ts`, `location`, `domain`) VALUES ( '$new_hash', $user, $_me, $_pc, $_ts, '$location', '$_domain' )");
		 return true;
		}
		else
		{
		 DB::Execute("UPDATE ical_hashlist SET `hash` = '$new_hash', `_me` = $_me, `_pc` = $_pc, `_ts` = $_ts, `location` = '$location', `domain` = '$_domain'  WHERE `logged_user_id` = $user");
		 return true;
		}
	}			
	
}
?>
