<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
/**
 * @package    local
 * @subpackage sync
 * @copyright  2016 Hans Jeria (hansjeria@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
// Define whether the sync has been actived or not inactived
define('SYNC_STATUS_INACTIVE', 0);
define('SYNC_STATUS_ACTIVE', 1);

function sync_getusers_fromomega($academicids, $syncinfo){
	global $DB, $CFG;
	
	$curl = curl_init();
	$url = $CFG->sync_urlgetalumnos;
	$token = $CFG->sync_token;
	
	$fields = array(
			"token" => $token,
			"PeriodosAcademicos" => $academicids
	);
	
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($curl, CURLOPT_POST, TRUE);
	curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($fields));
	curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
	
	$result = json_decode(curl_exec($curl));
	curl_close($curl);
	
	// Needs the academic period to record the history of sync
	$coursesids = array_column($result, 'SeccionId');
	$academicdbycourseid = sync_getacademicbycourseids($coursesids);
	
	$users = array();
	foreach($result as $user) {
		$insertdata = new stdClass();
		$insertdata->course = utf8_encode($academicdbycourseid[$user->SeccionId]["shortname"]);
		$insertdata->user = utf8_encode($user->Email);
		$insertdata->role = utf8_encode($user->Tipo);
	
		$users[] = $insertdata;
	
		$academicid = $academicdbycourseid[$user->SeccionId]["academicid"];
		$syncinfo[$academicid]["enrol"] += 1;
	}
	
	return array($users, $syncinfo);
}

function sync_getcourses_fromomega($academicids, $syncinfo){
	global $CFG;

	$curl = curl_init();
	$url = $CFG->sync_urlgetcursos;
	$token = $CFG->sync_token;

	$fields = array(
			"token" => $token,
			"PeriodosAcademicos" => $academicids
	);

	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($curl, CURLOPT_POST, TRUE);
	curl_setopt($curl, CURLOPT_POSTFIELDS,json_encode($fields));
	curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));

	$result = json_decode(curl_exec($curl));
	curl_close($curl);

	$courses = array();
	foreach($result as $course) {
		$insertdata = new stdClass();
		$insertdata->dataid = $syncinfo[$course->PeriodoAcademicoId]["dataid"];
		$insertdata->fullname = utf8_encode($course->FullName);
		$insertdata->shortname = utf8_encode($course->ShortName);
		$insertdata->idnumber = $course->SeccionId;
		$insertdata->categoryid = $syncinfo[$course->PeriodoAcademicoId]["categoryid"];

		$courses[] = $insertdata;
		
		$syncinfo[$course->PeriodoAcademicoId]["course"] += 1;
	}

	return array($courses, $syncinfo);
}

function sync_getacademicperiod(){
	global $DB;
	
	// Get all ID from each academic period with status is active (value 1)
	$periods = $DB->get_records("sync_data", array(
			"status" => SYNC_STATUS_ACTIVE
	));
	
	$academicids = array();
	$syncinfo = array();
	if(count($periods) > 0){
		foreach($periods as $period) {
			$academicids[] = $period->academicperiodid;
			$syncinfo[$period->academicperiodid] = array(
					"dataid" => $period->id,
					"course" => 0,
					"enrol" => 0,
					"categoryid" => $period->categoryid
			);
		}
		return array($academicids, $syncinfo);
	}else{
		return array(FALSE, FALSE);
	}
}

function sync_getacademicbycourseids($coursesids){
	global $DB;
	
	// get_in_or_equal used after in the IN ('') clause of multiple querys
	list($sqlin, $param) = $DB->get_in_or_equal($coursesids);
	
	$sqlgetacademic = "SELECT c.id, 
			c.shortname, 
			c.idnumber, 
			s.academicperiodid
			FROM {sync_course} AS c INNER JOIN {sync_data} AS s ON (c.dataid = s.id)
			WHERE c. idnumber $sqlin";
	
	$academicinfo = $DB->get_records_sql($sqlgetacademic, $param);
	
	$shortnamebycourseid = array_column($academicinfo, 'shortname', 'idnumber');
	$academicbycourseid = array_column($academicinfo, 'academicperiodid', 'idnumber');
	
	$result = array();
	foreach ($shortnamebycourseid as $courseid => $shortname){
		$result[$courseid] = array(
				"shortname" => $shortname,
				"academicid" => $academicbycourseid[$courseid],
		);
	
	}
	
	return $result;
}

function sync_getacademicperiodids_fromomega() {
	global $CFG;
	
	$curl = curl_init();
	$url = $CFG->sync_urlgetacademicperiods;
	$token = $CFG->sync_token;
	
	$fields = array(
			"token" => $token
	);
		
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($curl, CURLOPT_POST, TRUE);
	curl_setopt($curl, CURLOPT_POSTFIELDS,json_encode($fields));
	curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
	
	$result = json_decode(curl_exec($curl));
	curl_close($curl);
	
	return $result;
}

function sync_tabs() {
    $tabs = array();
    // create sync
    $tabs[] = new tabobject("create", new moodle_url("/local/sync/create.php", array(
        "status" => 1
    )), get_string("create", 'local_sync'));
    //  history.
    $tabs[] = new tabobject("record", new moodle_url("/local/sync/record.php", array(
        "status" => 2
    )), get_string("record", 'local_sync'));
    
     return $tabs;
}