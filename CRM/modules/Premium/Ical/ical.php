<?php

/**
 * ical.php
 * Genera file ical per importare calendario in Google Calendar 
 * rev 0.1 - 2013-09-11 : initial version
 * rev 0.2 - 2013-09-22 : parametrized some values | bugfix
 * rev 0.3 - 2013-09-23 : inserted description field formatted
 * rev 0.5 - 2013-09-25 : Make changes to work with the Ical Module and minor fixes
 * rev 0.6 - 2013-09-28 : added status field and inserted an easy format way | added calendar name and description as configuration fields
 *
 * parameter requested: _employeeid
 *
 * _me: =1 to generate meetings
 * _pc: =1 to generate phonecalls
 * _ts: =1 to generate tasks
 */ 

define('VERSION','0.6');

// Set headers
header('Cache-Control: max-age=7200, private, must-revalidate');
header('Content-Type: text/calendar');
header('Content-Disposition: attachment; filename=ical.ics');

ob_start("writebuffer");


/* Database connection parameters */
define('DATABASE_HOST', $_GET['srv']);
define('DATABASE_USER', $_GET['usr']);
define('DATABASE_PASSWORD', $_GET['pwd']);
define('DATABASE_NAME', $_GET['db']);

/* Time Location of calendar */
define('LOCATION', $_GET['loc']);


/* Domain used as event UID trail */
define('DOMAIN', $_GET['domain']);



/* Parameters to be used by SELECT statement */
$LoginId = $_GET["_employeeid"];
$tasks = ($_GET["_ts"]) && ($_GET["_ts"] == '1');
$phoneCalls = ($_GET["_pc"]) && ($_GET["_pc"] == '1');
$meetings = ($_GET["_me"]) && ($_GET["_me"] == '1');


/* Calendar Name and Description */
$calendarName = $_GET["calendarName"];
$calendarDescription = $_GET["calendarDescription"];
if (trim($calendarName) == "") {
    $calendarName = "EPESI CRM Calendar";	
}
if (trim($calendarDescription) == "") {
    $calendarDescription = "Meetings\, Phonecalls and Tasks from EPESI CRM";	
}


/* Retrieve emloyee Id from its username */
$get_employeeid = mysql_query("SELECT `id` FROM `contact_data_1` WHERE `f_login` = $loginId") or die(mysql_error);
$empId = mysql_result($get_employeeid, 0, 'id');


/* Setting script timezone location */
date_default_timezone_set(LOCATION);


/* Connecting to database */
$link = mysql_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD);
if (!$link) {
    die('Could not connect: ' . mysql_error());
}
$db_selected = mysql_select_db(DATABASE_NAME, $link);
if (!$db_selected) {
    die ('Can\'t use ' . DATABASE_NAME . ' : ' . mysql_error());
}



/* Create string for ical format */
$strCalendar = "BEGIN:VCALENDAR\r\n" .
               "VERSION:2.0\r\n" .
               "PRODID:-//EPESI-Ical//EpesiCal " . VERSION . "//EN\r\n" .
			   "CALSCALE:GREGORIAN\r\n" .
               "METHOD:PUBLISH\r\n" .
			   "X-WR-CALNAME:" . prepareField($calendarName) . "\r\n" .
			   "X-WR-TIMEZONE:" . LOCATION . "\r\n" .
			   "X-WR-CALDESC:" . prepareField($calendarName) . "\r\n" .
			   "X-PUBLISHED-TTL:PT2H\r\n" .
			   "BEGIN:VTIMEZONE\r\n" .
			   "TZID:" . LOCATION . "\r\n" .
			   "X-LIC-LOCATION:" . LOCATION . "\r\n" .
			   "BEGIN:DAYLIGHT\r\n" .
			   "TZOFFSETFROM:+0100\r\n" .
			   "TZOFFSETTO:+0200\r\n" .
			   "TZNAME:CEST\r\n" .
			   "DTSTART:19700329T020000\r\n" .
			   "RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU\r\n" .
			   "END:DAYLIGHT\r\n" .
			   "BEGIN:STANDARD\r\n" .
			   "TZOFFSETFROM:+0200\r\n" .
			   "TZOFFSETTO:+0100\r\n" .
			   "TZNAME:CET\r\n" .
			   "DTSTART:19701025T030000\r\n" .
			   "RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU\r\n" .
			   "END:STANDARD\r\n" .
			   "END:VTIMEZONE\r\n";


			   
// ***** TASK *****
// Perform Query
if ($tasks) {
	$query = "SELECT `user_login`.`login`," .
			 "       `task_data_1`.`id`, `task_data_1`.`f_title`, `task_data_1`.`created_on`, `task_data_1`.`f_deadline`, `task_data_1`.`f_status`, `task_data_1`.`f_description`" .
			 " FROM `task_data_1`" . 
			 " LEFT JOIN `user_login` ON `user_login`.`id` = `task_data_1`.`created_by`" .
			 " WHERE (`task_data_1`.`f_employees` LIKE '%\_\_" . $empId . "\_\_%') AND (`task_data_1`.`active` = 1)";
			 
	$result = mysql_query($query);
	if (!$result) {
		$message  = 'Invalid query: ' . mysql_error() . "\r\n";
		$message .= 'Whole query: ' . $query;
		die($message);
	}

	while ($row = mysql_fetch_assoc($result)) {

		if (!$row['f_deadline']) {
			$dtDateStart = getDatetime($row['created_on']) . "Z";
			$iDateEnd = strtotime(getDatetime($row['created_on'])) + 3600;
			$dtDateEnd = gmdate("Ymd", $iDateEnd) . "T" . gmdate("His", $iDateEnd) . "Z";
		} else {
			$iDateStart = strtotime(getDatetime($row['f_deadline']));
			$dtDateStart = gmdate("Ymd", $iDateStart) . "T" . gmdate("His", $iDateStart) . "Z";
			$iDateEnd = strtotime(getDatetime($row['f_deadline'])) + 3600;
			$dtDateEnd = gmdate("Ymd", $iDateEnd) . "T" . gmdate("His", $iDateEnd) . "Z";
		}	

		
		$iDateCreation = strtotime(getDatetime($row['created_on']));
		$dtDateCreation = gmdate("Ymd", $iDateCreation) . "T" . gmdate("His", $iDateCreation) . "Z";
			
		$strTmp = "BEGIN:VEVENT\r\n" .
				  "UID:" . "t" . str_pad($row['id'], 10, "0", STR_PAD_LEFT) . "@" . DOMAIN . "\r\n" .
				  "DTSTAMP:" . $dtDateCreation . "\r\n" .
				  "DTSTART:" . $dtDateStart . "\r\n" .
				  "DTEND:" . $dtDateEnd . "\r\n" .
				  "SUMMARY:" . sprintf('[%2$s] %1$s | ', 'TASK', getStatus($row['f_status'])) . prepareField($row['f_title']) . "\r\n" .
				  "DESCRIPTION:" . prepareField($row['f_description']) . "\r\n" .
				  "END:VEVENT\r\n";
		
		$strCalendar = $strCalendar . $strTmp;
	}
}


// ***** PHONECALL *****
// Perform Query
if ($phoneCalls) {
	$query = "SELECT `user_login`.`login`," .
			 "       `phonecall_data_1`.`id`, `phonecall_data_1`.`f_subject`, `phonecall_data_1`.`created_on`, `phonecall_data_1`.`f_date_and_time`, `phonecall_data_1`.`f_status`, `phonecall_data_1`.`f_description`" .
			 " FROM `phonecall_data_1`" . 
			 " LEFT JOIN `user_login` ON `user_login`.`id` = `phonecall_data_1`.`created_by`" .
			 " WHERE (`phonecall_data_1`.`f_employees` LIKE '%\_\_" . $empId . "\_\_%') AND (`phonecall_data_1`.`active` = 1)";
			 
	$result = mysql_query($query);
	if (!$result) {
		$message  = 'Invalid query: ' . mysql_error() . "\r\n";
		$message .= 'Whole query: ' . $query;
		die($message);
	}

	while ($row = mysql_fetch_assoc($result)) {

		if (!$row['f_date_and_time']) {
			$dtDateStart = getDatetime($row['created_on']) . "Z";
			$iDateEnd = strtotime(getDatetime($row['created_on'])) + 3600;
			$dtDateEnd = gmdate("Ymd", $iDateEnd) . "T" . gmdate("His", $iDateEnd) . "Z";
		} else {
			$iDateStart = strtotime(getDatetime($row['f_date_and_time']));
			$dtDateStart = gmdate("Ymd", $iDateStart) . "T" . gmdate("His", $iDateStart) . "Z";
			$iDateEnd = strtotime(getDatetime($row['f_date_and_time'])) + 3600;
			$dtDateEnd = gmdate("Ymd", $iDateEnd) . "T" . gmdate("His", $iDateEnd) . "Z";
		}	

		
		$iDateCreation = strtotime(getDatetime($row['created_on']));
		$dtDateCreation = gmdate("Ymd", $iDateCreation) . "T" . gmdate("His", $iDateCreation) . "Z";
		
		$strTmp = "BEGIN:VEVENT\r\n" .
				  "UID:" . "p" . str_pad($row['id'], 10, "0", STR_PAD_LEFT) . "@" . DOMAIN . "\r\n" .
				  "DTSTAMP:" . $dtDateCreation . "\r\n" .
				  "DTSTART:" . $dtDateStart . "\r\n" .
				  "DTEND:" . $dtDateEnd . "\r\n" .
				  "SUMMARY:" . sprintf('[%2$s] %1$s | ', 'PHONECALL', getStatus($row['f_status'])) . prepareField($row['f_subject']) . "\r\n" .
				  "DESCRIPTION:" . prepareField($row['f_description']) . "\r\n" .
				  "END:VEVENT\r\n";
		
		$strCalendar = $strCalendar . $strTmp;
	}
}


// ***** MEETINGS *****
// Perform Query
if ($meetings) {
	$query = "SELECT `user_login`.`login`," .
			 "       `crm_meeting_data_1`.`id`, `crm_meeting_data_1`.`f_title`, `crm_meeting_data_1`.`created_on`, `crm_meeting_data_1`.`f_date`, `crm_meeting_data_1`.`f_time`, `crm_meeting_data_1`.`f_duration`, `crm_meeting_data_1`.`f_status`, `crm_meeting_data_1`.`f_description`" .
			 " FROM `crm_meeting_data_1`" . 
			 " LEFT JOIN `user_login` ON `user_login`.`id` = `crm_meeting_data_1`.`created_by`" .
			 " WHERE (`crm_meeting_data_1`.`f_employees` LIKE '%\_\_" . $empId . "\_\_%') AND (`crm_meeting_data_1`.`active` = 1)";
			 
	$result = mysql_query($query);
	if (!$result) {
		$message  = 'Invalid query: ' . mysql_error() . "\r\n";
		$message .= 'Whole query: ' . $query;
		die($message);
	}

	while ($row = mysql_fetch_assoc($result)) {

		$duration = ((int) $row['f_duration']);
		if ($duration < 0) {
			$duration = 86400;
		}

		if ((!$row['f_time']) || ($row['f_time'] == '0000-00-00 00:00:00')) {
			$iDateStart = strtotime(getDatetime($row['f_date']));
			$dtDateStart = gmdate("Ymd", $iDateStart) . "T" . gmdate("His", $iDateStart) . "Z";
			$iDateEnd = $iDateStart + $duration;
			$dtDateEnd = gmdate("Ymd", $iDateEnd) . "T" . gmdate("His", $iDateEnd) . "Z";
		} else {
			$iDateStart = strtotime(getDatetime($row['f_time']));
			$dtDateStart = gmdate("Ymd", $iDateStart) . "T" . gmdate("His", $iDateStart) . "Z";
			$iDateEnd = $iDateStart + $duration;
			$dtDateEnd = gmdate("Ymd", $iDateEnd) . "T" . gmdate("His", $iDateEnd) . "Z";
		}	

		
		$iDateCreation = strtotime(getDatetime($row['created_on']));
		$dtDateCreation = gmdate("Ymd", $iDateCreation) . "T" . gmdate("His", $iDateCreation) . "Z";
		
		$strTmp = "BEGIN:VEVENT\r\n" .
				  "UID:" . "m" . str_pad($row['id'], 10, "0", STR_PAD_LEFT) . "@" . DOMAIN . "\r\n" .
				  "DTSTAMP:" . $dtDateCreation . "\r\n" .
				  "DTSTART:" . $dtDateStart . "\r\n" .
				  "DTEND:" . $dtDateEnd . "\r\n" .
				  "SUMMARY:" . sprintf('[%2$s] %1$s | ', 'MEETING', getStatus) . prepareField($row['f_title']) . "\r\n" .
				  "DESCRIPTION:" . prepareField($row['f_description']) . "\r\n" .
				  "END:VEVENT\r\n";
		
		$strCalendar = $strCalendar . $strTmp;
	}
}




$strCalendar = $strCalendar .
               "END:VCALENDAR";

echo $strCalendar;

ob_end_flush();





function writebuffer($buffer) {
	return ($buffer);
}


/** Utility functions **/

/* GetStatus: return status string from its index (CommonData) */
function getStatus($istatus) {
	
	$status = "UNKNOWN";
	switch ($istatus) {
	    case 0:
	        $status = "OPEN";
	        break;
	    case 1:
	        $status = "WORKING";
	        break;
	    case 2:
	        $status = "WAITING";
	        break;
	    case 3:
	        $status = "CLOSED";
	        break;
            case 4:
	        $status = "CANCELLED";
	        break;
	    default:
	    	$status = "UNKNOWN";
	    	break;
	}
	return $status;
}


/* GetDatetime: return datetime string formatted in the right way from the one in EPESI database */
function getDatetime($str) {
	
	$strTmp = $str;
	$strTmp = str_replace(' ', 'T', $strTmp);
	$strTmp = str_replace('-', '', $strTmp);
	$strTmp = str_replace(':', '', $strTmp);
	
	return $strTmp;
}


/* PrepareField: return string formatted in right way to be inserted in ical format */
function prepareField($str) {

	$strTmp = $str;
	$strTmp = str_replace(',', '\,', $strTmp);
	$strTmp = str_replace(array("\r\n","\n","\r"),"\\n",$strTmp);
	//$strTmp = str_replace(':', '\:', $strTmp);
	//$strTmp = str_replace(';', '\;', $strTmp);
	//$strTmp = str_replace('\'', '\\\'', $strTmp);
	//$strTmp = str_replace('"', '\"', $strTmp);
	
	return $strTmp;
}

?>
