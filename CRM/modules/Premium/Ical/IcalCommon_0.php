<?php
defined("_VALID_ACCESS") || die('Direct access forbidden');

class Premium_IcalCommon extends ModuleCommon {


 public static function admin_caption() {
 	return array('label'=>__('Ical Export Settings'), 'section'=>__('Server Configuration'));
 }

}
