<?php
defined("_VALID_ACCESS") || die('Direct access forbidden');

class Premium_IcalCommon extends ModuleCommon {


 public static function user_settings() {

	return array(__("Calendar Export Settings")=> 'settings');
 }
}
