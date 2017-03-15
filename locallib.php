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
 * @copyright  2017 Mark Michaelsen (mmichaelsen678@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
// Define whether the sync has been actived or not inactived
define('SYNC_STATUS_INACTIVE', 0);
define('SYNC_STATUS_ACTIVE', 1);
define('MODULE_FORUM', 'forum');

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
	
	// Check the version to use the corrects functions
	if(PHP_MAJOR_VERSION < 7){
		$coursesids = array();
		foreach ($result as $course){
			$coursesids[] = $course->SeccionId;
		}
	}else{
		// Needs the academic period to record the history of sync
		$coursesids = array_column($result, 'SeccionId');
	}
	
	$academicdbycourseid = sync_getacademicbycourseids($coursesids);
	
	mtrace("#### Adding Enrollments ####");
	$users = array();
	foreach($result as $user) {
		$insertdata = new stdClass();
		$academicid = $user->PeriodoAcademicoId;
		if(!isset($academicdbycourseid[$user->SeccionId]) || empty($academicdbycourseid[$user->SeccionId])){
			$insertdata->course = NULL;
		}else{
			$insertdata->course = $academicdbycourseid[$user->SeccionId];
		}
		$insertdata->user = ($CFG->sync_emailexplode) ? explode("@", $user->Email)[0] : $user->Email;
		switch ($user->Tipo) {
			case 'EditingTeacher':
				$insertdata->role = $CFG->sync_teachername;
				break;
			case 'Student':
				$insertdata->role = $CFG->sync_studentname;
				break;
			default:
				$insertdata->role = $CFG->sync_studentname;
				break;
		};
	
		if($insertdata->course != NULL){
			$users[] = $insertdata;
			$syncinfo[$academicid]["enrol"] += 1;
			mtrace("USER: ".$insertdata->user." TYPE: ".$insertdata->role." COURSE: ".$insertdata->course);
		}
		
		$generalcoursedata = new stdClass();
		$generalcoursedata->course = ($insertdata->role == $CFG->sync_teachername) ? $academicid."-PROFESORES" : $academicid."-ALUMNOS";
		$generalcoursedata->user = $insertdata->user;
		$generalcoursedata->role = $CFG->sync_studentname;
			
		if(!in_array($generalcoursedata, $users)) {
			$users[] = $generalcoursedata;
			mtrace("USER: ".$insertdata->user." TYPE: ".$generalcoursedata->role." COURSE: ".$generalcoursedata->course);
		}
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
	mtrace("#### Adding Courses ####");
	$courses = array();
	foreach($result as $course) {
		$insertdata = new stdClass();
		$insertdata->dataid = $syncinfo[$course->PeriodoAcademicoId]["dataid"];
		// Format ISO-8859-1 Fullname
		$insertdata->fullname = $course->FullName;
		// Validate encode Fullname
		//mtrace(mb_detect_encoding($course->FullName,"ISO-8859-1, GBK, UTF-8"));		
		$insertdata->shortname = $course->ShortName;
		$insertdata->idnumber = $course->SeccionId;
		$insertdata->categoryid = $syncinfo[$course->PeriodoAcademicoId]["categoryid"];
		if($insertdata->fullname != NULL && $insertdata->shortname != NULL && $insertdata->idnumber != NULL){
			$courses[] = $insertdata;		
			$syncinfo[$course->PeriodoAcademicoId]["course"] += 1;
			mtrace("COURSE: ".$insertdata->shortname." IDNUMBER: ".$insertdata->idnumber." CATEGORY: ".$insertdata->categoryid);
		}
	}
	
	foreach($academicids as $periodid) {
		// Build the academic period's general students course
		$studentscourse = new StdClass();
		$studentscourse->dataid = $syncinfo[$periodid]["dataid"];
		$studentscourse->fullname = "Alumnos ".$syncinfo[$periodid]["periodname"];
		$studentscourse->shortname = $periodid."-ALUMNOS";
		$studentscourse->idnumber = NULL;
		$studentscourse->categoryid = $syncinfo[$periodid]["categoryid"];
		
		// Build the academic period's general teachers course
		$teacherscourse = new StdClass();
		$teacherscourse->dataid = $syncinfo[$periodid]["dataid"];
		$teacherscourse->fullname = "Profesores ".$syncinfo[$periodid]["periodname"];
		$teacherscourse->shortname = $periodid."-PROFESORES";
		$teacherscourse->idnumber = NULL;
		$teacherscourse->categoryid = $syncinfo[$periodid]["categoryid"];
		mtrace("COURSE: ".$studentscourse->shortname." CATEGORY: ".$studentscourse->categoryid);
		mtrace("COURSE: ".$teacherscourse->shortname." CATEGORY: ".$teacherscourse->categoryid);
		$courses[] = $studentscourse;
		$courses[] = $teacherscourse;
	}
	return array($courses, $syncinfo);
}

function sync_getacademicperiod(){
	global $DB;
	
	// Get all ID from each academic period with status is active (value 1)
	$periods = $DB->get_records("sync_data", array(
			"status" => SYNC_STATUS_ACTIVE
	));
	mtrace("Academic Period to synchronize \n");
	$academicids = array();
	$syncinfo = array();
	if(count($periods) > 0){
		foreach($periods as $period) {
			$academicids[] = $period->academicperiodid;
			$syncinfo[$period->academicperiodid] = array(
					"dataid" => $period->id,
					"course" => 0,
					"enrol" => 0,
					"categoryid" => $period->categoryid,
					"periodname" => $period->academicperiodname
			);
			mtrace("ID: ".$period->academicperiodid." NAME: ".$period->academicperiodname." CATEGORY: ".$period->categoryid." \n");
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
	// Check the version to use the corrects functions
	if(PHP_MAJOR_VERSION < 7){
		$shortnamebycourseid = array();
		foreach ($academicinfo as $academic){
			$shortnamebycourseid[$academic->idnumber] = $academic->shortname;
		}
	}else{
		$shortnamebycourseid = array_column($academicinfo, 'shortname', 'idnumber');
	}
	return $shortnamebycourseid;
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
	// Create sync
	$tabs[] = new tabobject(
			"create",
			new moodle_url("/local/sync/create.php"),
			get_string("create", "local_sync")
	);
	// Records.
	$tabs[] = new tabobject(
			"record",
			new moodle_url("/local/sync/record.php"),
			get_string("record", "local_sync")
	);
	// History
	$tabs[] = new tabobject(
			"history",
			new moodle_url("/local/sync/history.php"),
			get_string("history", "local_sync")
	);
	return $tabs;
}

function sync_delete_enrolments($enrol, $categoryid){
	global $DB, $OUTPUT;
	
	$success = false;
	$message = "";
	
	if($enrol == "manual" || $enrol == "self") {
		$sql = "SELECT ue.id
				FROM {user_enrolments} AS ue
				INNER JOIN {enrol} AS e ON (e.id = ue.enrolid AND e.enrol = ?)
				INNER JOIN {course} AS c ON (c.id = e.courseid)
				INNER JOIN {course_categories} AS cc ON (cc.id = c.category AND cc.id = ?)";
		$todelete = $DB->get_records_sql($sql, array($enrol, $categoryid));
		
		$userenrolmentsid = array();
		foreach($todelete as $idtodelete){
			$userenrolmentsid[] = $idtodelete->id;
		}
		
		if (!empty($userenrolmentsid)){
			list($sqlin, $param) = $DB->get_in_or_equal($userenrolmentsid);
			$query = "DELETE
					FROM {user_enrolments}
					WHERE id $sqlin";
			
			if($DB->execute($query, $param)) {
				$success = true;
				$message .= $OUTPUT->notification(get_string("unenrol_success", "local_sync"), "notifysuccess");
			} else {
				$message .= $OUTPUT->notification(get_string("unenrol_fail", "local_sync"));
			}
		} else {
			$message .= $OUTPUT->notification(get_string("unenrol_empty", "local_sync"));
		}
	} else {
		$message .= $OUTPUT->notification(get_string("unenrol_fail", "local_sync"));
	}
	
	return array($success, $message);
}

function sync_deletecourses($syncid) {
	global $DB;
	
	$data = $DB->get_record("sync_data", array(
			"id" => $syncid
	));
	$categoryid = $data->categoryid;
	
	if($categoryid != 0) {
		return $DB->delete_records("course", array(
				"category" => $categoryid
		));
	} else {
		return false;
	}
}

function sync_validate_deletion($syncid) {
	global $OUTPUT, $DB;
	
	$capable = true;
	$message = "";
	
	if($syncdata = $DB->get_record("sync_data", array(
			"id" => $syncid
		))) {
		$categoryid = $syncdata->categoryid;	
		// Category without children
		if($DB->record_exists("course_categories", array(
				"parent" => $categoryid
		))) {
			$capable = false;
			$message .= $OUTPUT->notification(get_string("category_haschildren", "local_sync"));
		} else {
			// Course without users
			$enrolmentssql = "SELECT ue.id,
					COUNT(ue.id) AS instances,
					sd.academicperiodid AS periodid,
					sd.academicperiodname AS periodname,
					c.fullname AS coursefullname,
					c.shortname AS courseshortname
					FROM {sync_data} AS sd
					INNER JOIN {course} AS c ON (sd.categoryid = c.category AND c.category = ?)
					INNER JOIN {enrol} AS e ON (c.id = e.courseid)
					INNER JOIN {user_enrolments} AS ue ON (e.id = ue.enrolid)
					GROUP BY c.id";
			
			$enrolmentsparams = array($categoryid);
			// Course without modules
			$modulessql = "SELECT cm.id,
					COUNT(cm.id) AS instances,
					sd.academicperiodid AS periodid,
					sd.academicperiodname AS periodname,
					c.fullname AS coursefullname,
					c.shortname AS courseshortname
					FROM {sync_data} AS sd
					INNER JOIN {course} AS c ON (sd.categoryid = c.category AND c.category = ?)
					INNER JOIN {course_modules} AS cm ON (c.id = cm.course)
					INNER JOIN {modules} AS m ON (m.id = cm.module AND m.name <> ?)
					GROUP BY c.id";
			$modulesparams = array($categoryid, MODULE_FORUM);
			
			$enrolments = $DB->get_records_sql($enrolmentssql, $enrolmentsparams);
			$modules = $DB->get_records_sql($modulessql, $modulesparams);
			
			if(!empty($enrolments)) {
				$capable = false;
				foreach($enrolments as $enrolment) {
					$message .= $OUTPUT->notification(
							get_string("courses_delete_description", "local_sync").
							$enrolment->periodname.
							"' (ID: ".
							$enrolment->periodid.
							get_string("courses_delete_cause", "local_sync").
							$enrolment->coursefullname.
							get_string("courses_delete_shortname", "local_sync").
							$enrolment->courseshortname.
							get_string("courses_delete_has", "local_sync").
							$enrolment->instances.
							get_string("courses_delete_enroled", "local_sync")
					);
				}
			} else {
				$message .= $OUTPUT->notification(get_string("courses_enroled_success", "local_sync"), "notifysuccess");
			}
			
			if(!empty($modules)) {
				$capable = false;
				foreach($modules as $module) {
					$message .= $OUTPUT->notification(
							get_string("courses_delete_description", "local_sync").
							$module->periodname.
							"' (ID: ".
							$module->periodid.
							get_string("courses_delete_cause", "local_sync").
							$module->coursefullname.
							get_string("courses_delete_shortname", "local_sync").
							$module->courseshortname.
							get_string("courses_delete_has", "local_sync").
							$module->instances.
							get_string("courses_delete_modules", "local_sync")
					);
				}
			} else {
				$message .= $OUTPUT->notification(get_string("courses_modules_success", "local_sync"), "notifysuccess");
			}
		}
	} else {
		$capable = false;
		$message .= $OUTPUT->notification(get_string("courses_missingid", "local_sync"));
	}		
	return array($capable, $message);
}

function sync_records_tabs() {
	$tabs = array();
	// Active
	$tabs[] = new tabobject(
			"active",
			new moodle_url("/local/sync/record.php", array(
					"view" => "active"
			)),
			get_string("active", "local_sync")
	);	
	// Inactive
	$tabs[] = new tabobject(
			"inactive",
			new moodle_url("/local/sync/record.php", array(
					"view" => "inactive"
			)),
			get_string("inactive", "local_sync")
	);	
	return $tabs;
}