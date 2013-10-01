<?php

/**
 * ical.php
 * Generate an ical.ics file in iCalendar format
 * rev 0.1 - 2013-09-11 : initial version
 * rev 0.2 - 2013-09-22 : parametrized some values | bugfix
 * rev 0.3 - 2013-09-23 : inserted description field formatted
 * rev 0.5 - 2013-09-25 : Make changes to work with the Ical Module and minor fixes
 * rev 0.6 - 2013-09-28 : added status field and inserted an easy format way | added calendar name and description as configuration fields
 * rev 0.7 - 2013-09-29 : added VTODO | added SEQUENCE to notify update, based on latest modification
 * rev 0.8 - 2013-10-01 : added uuid for GET request, remove other GET requests. get MySQL settings from CRM configurations. get user settings in control Panel. | switch to mysqli
 *
 * parameter requested: _employeeid
 *
 * _me: =1 to generate meetings
 * _pc: =1 to generate phonecalls
 * _ts: =1 to generate tasks
 */ 

define('VERSION','0.8');

// Set headers
header('Cache-Control: max-age=7200, private, must-revalidate');
header('Content-Type: text/calendar');
header('Content-Disposition: attachment; filename=ical.ics');

ob_start("writebuffer");
define('_VALID_ACCESS', 'ical Module');
require_once("../../../data/config.php"); //get DB connection parameters






/* Calendar Name and Description */
$calendarName = $_GET["calendarName"];
$calendarDescription = $_GET["calendarDescription"];
if (trim($calendarName) == "") {
    $calendarName = "EPESI CRM Calendar";	
}
if (trim($calendarDescription) == "") {
    $calendarDescription = "Meetings\, Phonecalls and Tasks from EPESI CRM";	
}


/* Connection to database using mysqli */
$mysqli = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_NAME);
if ($mysqli->connect_errno) {
    die('Could not connect: (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}


$check_hash = $mysqli->query("SELECT * FROM ical_hashlist WHERE hash = '".$_REQUEST['uid']."'");
if($mysqli->num_rows == 0)
{
 die("Invalid uid, Please use a different uid or generate a new one in Menu-\>My Settings-\>Control Panel-\>Calendar Export Settings");
}
//Get user Configurations
$row = $mysqli->fetch_assoc();
$loginId = $row["logged_user_id"];
$meetings = $row["_me"];
$phoneCalls = $row["_pc"];
$tasks = $row["_ts"];
$domain = $row["domain"];
$location = $row["location"];

/* Domain used as event UID trail */
if($domain != '' || $domain != ' ')
{
 define('DOMAIN', $domain);
}
else
{
 define('DOMAIN','domain.it');
}

/* Time Location of calendar */
define('LOCATION', $location);
/* Setting script timezone location */
date_default_timezone_set(LOCATION);

$get_employeeid = $mysqli->query("SELECT `id` FROM `contact_data_1` WHERE `f_login` = $loginId") or die($mysqli->error);
$row = $get_employeeid->fetch_assoc(); // get first row
$empId = $row['id'];

/* Create string for ical format */
$strCalendar = "BEGIN:VCALENDAR\r\n" .
               "VERSION:2.0\r\n" .
               "PRODID:-//EPESI-Ical//EpesiCal " . VERSION . "//EN\r\n" .
			   "CALSCALE:GREGORIAN\r\n" .
               "METHOD:PUBLISH\r\n" .
			   "X-WR-CALNAME:" . prepareField($calendarName) . "\r\n" .
			   "X-WR-TIMEZONE:" . LOCATION . "\r\n" .
			   "X-WR-CALDESC:" . prepareField($calendarDescription) . "\r\n" .
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
if ($tasks == 1) {
	$query = "SELECT `user_login`.`login`," .
			 "       `task_data_1`.`id`, `task_data_1`.`f_title`, `task_data_1`.`created_on`, `task_data_1`.`f_deadline`, `task_data_1`.`f_status`, `task_data_1`.`f_description`, `task_data_1`.`f_priority`" .
			 " FROM `task_data_1`" . 
			 " LEFT JOIN `user_login` ON `user_login`.`id` = `task_data_1`.`created_by`" .
			 " WHERE (`task_data_1`.`f_employees` LIKE '%\_\_" . $empId . "\_\_%') AND (`task_data_1`.`active` = 1)";
			 
	$result = $mysqli->query($query);
	if (!$result) {
		$message  = 'Invalid query: ' . $mysqli->error . "\r\n";
		$message .= 'Whole query: ' . $query;
		die($message);
	}

	while ($row = $result->fetch_assoc()) {

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
		
		/* SEQUENCE */
		$query1 = "SELECT `task_edit_history`.`edited_on`" .
			      " FROM `task_edit_history`" . 
				  " WHERE (`task_edit_history`.`task_id` = " . $row['id'] . ")" .
				  " ORDER BY `edited_on` DESC";
			 
		$result1 = $mysqli->query($query1);
		if (!$result1) {
			$message  = 'Invalid query: ' . $mysqli->error . "\r\n";
			$message .= 'Whole query: ' . $query1;
			die($message);
		}
		$sequence = $result1->num_rows;
			
		$strTmp = getVEVENT("TASK", 
		                    $row['id'], 
				    $dtDateCreation, 
				    $dtDateStart, 
				    $dtDateEnd, 
				    $row['f_title'], 
				    $row['f_description'], 
				    $row['f_priority'], 
				    $sequence, 
				    $row['f_status']);
		
		$strCalendar = $strCalendar . $strTmp;
	}
}


// ***** PHONECALL *****
// Perform Query
if ($phoneCalls == 1) {
	$query = "SELECT `user_login`.`login`," .
			 "       `phonecall_data_1`.`id`, `phonecall_data_1`.`f_subject`, `phonecall_data_1`.`created_on`, `phonecall_data_1`.`f_date_and_time`, `phonecall_data_1`.`f_status`, `phonecall_data_1`.`f_description`, `phonecall_data_1`.`f_priority`" .
			 " FROM `phonecall_data_1`" . 
			 " LEFT JOIN `user_login` ON `user_login`.`id` = `phonecall_data_1`.`created_by`" .
			 " WHERE (`phonecall_data_1`.`f_employees` LIKE '%\_\_" . $empId . "\_\_%') AND (`phonecall_data_1`.`active` = 1)";
			 
	$result = $mysqli->query($query);
	if (!$result) {
		$message  = 'Invalid query: ' . $mysqli->error . "\r\n";
		$message .= 'Whole query: ' . $query;
		die($message);
	}

	while ($row = $result->fetch_assoc()) {

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
		
		/* SEQUENCE */
		$query1 = "SELECT `phonecall_edit_history`.`edited_on`" .
			      " FROM `phonecall_edit_history`" . 
				  " WHERE (`phonecall_edit_history`.`phonecall_id` = " . $row['id'] . ")" .
				  " ORDER BY `edited_on` DESC";
			 
		$result1 = $mysqli->query($query1);
		if (!$result1) {
			$message  = 'Invalid query: ' . $mysqli->error . "\r\n";
			$message .= 'Whole query: ' . $query1;
			die($message);
		}
		$sequence = $result1->num_rows;
		
		$strTmp = getVEVENT("PHONECALL", 
		                    $row['id'], 
					$dtDateCreation, 
					$dtDateStart, 
					$dtDateEnd, 
					$row['f_subject'], 
					$row['f_description'], 
					$row['f_priority'], 
					$sequence, 
					$row['f_status']);
		
		$strCalendar = $strCalendar . $strTmp;
	}
}


// ***** MEETINGS *****
// Perform Query
if ($meetings == 1) {
	$query = "SELECT `user_login`.`login`," .
			 "       `crm_meeting_data_1`.`id`, `crm_meeting_data_1`.`f_title`, `crm_meeting_data_1`.`created_on`, `crm_meeting_data_1`.`f_date`, `crm_meeting_data_1`.`f_time`, `crm_meeting_data_1`.`f_duration`, `crm_meeting_data_1`.`f_status`, `crm_meeting_data_1`.`f_description`, `crm_meeting_data_1`.`f_priority`" .
			 " FROM `crm_meeting_data_1`" . 
			 " LEFT JOIN `user_login` ON `user_login`.`id` = `crm_meeting_data_1`.`created_by`" .
			 " WHERE (`crm_meeting_data_1`.`f_employees` LIKE '%\_\_" . $empId . "\_\_%') AND (`crm_meeting_data_1`.`active` = 1)";
			 
	$result = $mysqli->query($query);
	if (!$result) {
		$message  = 'Invalid query: ' . $mysqli->error . "\r\n";
		$message .= 'Whole query: ' . $query;
		die($message);
	}

	while ($row = $result->fetch_assoc()) {

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
		
		/* SEQUENCE */
		$query1 = "SELECT `crm_meeting_edit_history`.`edited_on`" .
			      " FROM `crm_meeting_edit_history`" . 
				  " WHERE (`crm_meeting_edit_history`.`crm_meeting_id` = " . $row['id'] . ")" .
				  " ORDER BY `edited_on` DESC";
			 
		$result1 = $mysqli->query($query1);
		if (!$result1) {
			$message  = 'Invalid query: ' . $mysqli->error . "\r\n";
			$message .= 'Whole query: ' . $query1;
			die($message);
		}
		$sequence = $result1->num_rows;
		
		$strTmp = getVEVENT("MEETING", 
		                    $row['id'], 
					$dtDateCreation, 
					$dtDateStart, 
					$dtDateEnd, 
					$row['f_title'], 
					$row['f_description'], 
					$row['f_priority'], 
					$sequence, 
					$row['f_status']);
		
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

/* GetVEVENT: get VEVENT formatted */
function getVEVENT($TYPE, $ID, $DTSTAMP, $DTSTART, $DTEND, $TITLE, $DESCRIPTION, $PRIORITY, $SEQUENCE, $STATUS) {

	$strTmp = "BEGIN:VEVENT\r\n" .
			  "UID:EVENT_" . $TYPE . str_pad($ID, 6, "0", STR_PAD_LEFT) . "@" . DOMAIN . "\r\n" .
			  "DTSTAMP:" . $DTSTAMP . "\r\n" .
			  "DTSTART:" . $DTSTART . "\r\n" .
			  "DTEND:" . $DTEND . "\r\n" .
			  "SUMMARY:" . sprintf('[%2$s] %1$s | ', $TYPE, getStatus($STATUS)) . prepareField($TITLE) . "\r\n" .
			  "DESCRIPTION:" . prepareField($DESCRIPTION) . "\r\n" .
			  "PRIORITY:" . getPriority($PRIORITY) . "\r\n" .
			  "STATUS:CONFIRMED" . "\r\n" .
			  "SEQUENCE:" . $SEQUENCE . "\r\n" .
			  "END:VEVENT\r\n";
	
	return $strTmp;
}

/* GetVTODO: get VTODO formatted */
function getVTODO($TYPE, $ID, $DTSTAMP, $DTSTART, $DTEND, $TITLE, $DESCRIPTION, $PRIORITY, $SEQUENCE, $STATUS) {

	$strTmp = "BEGIN:VTODO\r\n" .
			  "UID:TODO_" . $TYPE . str_pad($ID, 6, "0", STR_PAD_LEFT) . "@" . DOMAIN . "\r\n" .
			  "DTSTAMP:" . $DTSTAMP . "\r\n" .
			  "DTSTART:" . $DTSTART . "\r\n" .
			  "DUE:" . $DTEND . "\r\n" .
			  "SUMMARY:" . sprintf('[%2$s] %1$s | ', $TYPE, getStatus($STATUS)) . prepareField($TITLE) . "\r\n" .
			  "DESCRIPTION:" . prepareField($DESCRIPTION) . "\r\n" .
			  "PRIORITY:" . getPriority($PRIORITY) . "\r\n" .
			  "STATUS:" . getStatusTodo($STATUS) . "\r\n" .
			  "SEQUENCE:" . $SEQUENCE . "\r\n" .
			  "END:VTODO\r\n";
	
	return $strTmp;
}

/* GetStatus: return status string from its index (CommonData) */
function getStatus($istatus) {
	
	$status = "UNKNOWN";
	switch ($istatus) {
	    case "0":
	        $status = "OPEN";
	        break;
	    case "1":
	        $status = "WORKING";
	        break;
	    case "2":
	        $status = "WAITING";
	        break;
	    case "3":
	        $status = "CLOSED";
	        break;
            case "4":
	        $status = "CANCELLED";
	        break;
	    default:
	    	$status = "UNKNOWN";
	    	break;
	}
	return $status;
}


/* GetStatusTODO: get status for VTODO (Task) */
function getStatusTodo($istatus) {

	$status = "NEEDS-ACTION";
	switch ($istatus) {
	    case "0":
	        $status = "IN-PROCESS";
	        break;
	    case "1":
	        $status = "IN-PROCESS";
	        break;
	    case "2":
	        $status = "NEEDS-ACTION";
	        break;
	    case "3":
	        $status = "COMPLETED";
	        break;
        case "4":
	        $status = "CANCELLED";
	        break;
	    default:
	    	$status = "NEEDS-ACTION";
	    	break;
	}
	return $status;
}


/* GetPriority: get priority value according to iCal standard (1: High ... 3: Low) */
function getPriority($iPriority) {
	
	// 0: no priority
	if ($iPriority == 0) {
		return $iPriority;
	}
	return (3 - ((int) $iPriority));
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
