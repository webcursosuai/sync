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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 *
*
* @package    local
* @subpackage sync
* @copyright  2016 Joaquin Rivano (jrivano@alumnos.uai.cl)
* @copyright  2016 Mark Michaelsen (mmichaelsen678@gmail.com)
* @copyright  2016 Javier González (javiergonzalez@alumnos.uai.cl)
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

//Configuraciones globales
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once ($CFG->dirroot . "/local/sync/locallib.php");
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . "/local/sync/forms/edit_form.php");
global $CFG, $DB, $OUTPUT,$PAGE;

$page = optional_param('page', 0, PARAM_INT);
$perpage = 10;
$insert = optional_param("insert", "", PARAM_TEXT);
$action = optional_param("action", "view", PARAM_TEXT);
$syncid = optional_param("syncid", null, PARAM_INT);
$unenrol = optional_param("unenrol", null, PARAM_TEXT);
$view = optional_param("view", "active", PARAM_TEXT);

// User must be logged in.
require_login();
if (isguestuser()) {
    //die();
}

//Pagina moodle basico
//User needs capability to access
$context = context_system::instance();
if(!has_capability("local/sync:record", $context)) {
	print_error("ACCESS DENIED");
}
$url = new moodle_url("/local/sync/record.php");
$PAGE->navbar->add(get_string("sync_title", "local_sync"));
$PAGE->navbar->add(get_string("syncrecordtitle", "local_sync"),$url);
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout("standard");
$PAGE->set_title(get_string("sync_page", "local_sync"));
$PAGE->set_heading(get_string("sync_heading", "local_sync"));

if($insert == "success") {
	echo $OUTPUT->notification(get_string("sync_success", "local_sync"), "notifysuccess");
}

//action edit
if ($action == "edit") {

	if ($syncid== null) {
		print_error(get_string("syncdoesnotexist", "local_sync"));
		$action = "view";
	}
	else {
		$query = "SELECT s.responsible
                      FROM {sync_data} AS s
                      WHERE s.id = ? ";

		if ($module = $DB->get_record_sql($query, array($syncid))){
			$editform = new sync_editmodule_form(null, array("datossync" => $module, "syncid"=> $syncid));
			if ($editform->is_cancelled()) {
				$url = new moodle_url('/local/sync/record.php', array("action"=>"view"));
				redirect($url);
			}
			elseif ($formdata = $editform->get_data()){

				$paramupdate = array(
						$formdata -> responsible,
						$syncid
				);
				$update = "UPDATE {sync_data}
				           SET responsible = ?
				           WHERE id = ?";
				$DB->execute($update,$paramupdate);
				$url = new moodle_url('/local/sync/record.php', array("action"=>"view"));
				redirect($url);
			}
			echo $OUTPUT->header();
			echo $OUTPUT->heading(get_string("synctable", "local_sync"));
			echo $OUTPUT->tabtree(sync_tabs(), "record");
			$editform->display();
		}
	}
}

if($action == "activate") {
	$updatedata = new stdClass();
	
	$updatedata->id = $syncid;
	$updatedata->status = 1;
	
	$DB->update_record("sync_data", $updatedata);
	$action = "view";
} else if($action == "deactivate") {
	$updatedata = new stdClass();
	
	$updatedata->id = $syncid;
	$updatedata->status = 0;
	
	$DB->update_record("sync_data", $updatedata);
	$action = "view";
}

if ($action == "manual" || $action == "self"){
	if ($checkstatus = $DB->get_record("sync_data", array("id" => $syncid))){
		if ($checkstatus->status == 0){
			if (sync_delete_enrolments($action, $syncid)){
				$unenrol = "success";
			}
			else{
				$unenrol = "fail";
			}
		}
		else{
			$unenrol = "status1";
			//status1 means status is set to 1, the sync. is still active and the users can't be unenrol
		}
	}
	else{
		$unenrol = "fail";
	}
	$action = "view";
}

//action view
if ($action == "view") {
	echo $OUTPUT->header();
	echo $OUTPUT->heading(get_string("synctable", "local_sync"));
	echo $OUTPUT->tabtree(sync_tabs(), "record");
	echo $OUTPUT->tabtree(sync_records_tabs(), $view);

	if ($unenrol == "success"){
		echo $OUTPUT->notification(get_string("unenrol_success", "local_sync"), "notifysuccess");		
	}
	else if ($unenrol == "fail"){
		echo $OUTPUT->notification(get_string("unenrol_fail", "local_sync"));
	}
	else if ($unenrol == "status1"){
		echo $OUTPUT->notification(get_string("unenrol_status", "local_sync"));
	}
	
	$tablecount = 10 * $page;
	$synctable = new flexible_table("sync");
	$synctable->define_baseurl(new moodle_url("/local/sync/record.php"));
	$synctable->define_columns(array(
			"number",
			"timecreated",
			"academicperiodname",
			"academicperiodid" ,
			"category",
			"categoryid",
			"campus",
			"responsible",
			"1",
			"2",
			"3",
			"4"
	));
	$synctable->define_headers(array(
			"#",
			get_string("timecreated", "local_sync"),
			get_string("academicperiod", "local_sync"),
			get_string("periodid","local_sync"),
			get_string("category","local_sync"),
			get_string("categoryid","local_sync"),
			get_string("sede","local_sync"),
			get_string("in_charge","local_sync"),
			get_string("activation","local_sync"),
			get_string("manualunsub","local_sync"),
			get_string("selfunsub","local_sync"),
			get_string("edit","local_sync")));
	$synctable->sortable(true,"timecreated",SORT_DESC);
	$synctable->no_sorting("number","1","2","3");
	$synctable->setup();
	$synctable->style = array("3%","10%","9%","9%","10%","10%","10%","10%","8%","8%","8%","5%");
	if ($synctable->get_sql_sort()) {
		$sort = 'ORDER BY '. $synctable->get_sql_sort();
	} else {
		$sort = '';
	}
	
	list($where, $params) = $synctable->get_sql_where();
	
	if($view == "active") {
		$status = 1;
	} else if($view == "inactive") {
		$status = 0;
	}
	
	array_push($params, $status);
	
	if ($where) {
		$where = "WHERE ". $where;
	}
	
	$querycount = "SELECT count(*) FROM {sync_data} AS s";

	$query = "SELECT s.id AS id,
		s.timecreated,
		s.academicperiodid,
		s.academicperiodname,
		s.categoryid,
		s.campus,
		s.status,
		c.name AS category,
		s.responsible AS responsible
		FROM {sync_data} AS s
		INNER JOIN {course_categories} c ON (c.id = s.categoryid )
		$where
		AND s.status = ?
		$sort";
	
	$datos = $DB->get_records_sql($query,
			$params,
			$page * $perpage,
			($page + 1) * $perpage);

	$synccount = $DB->count_records_sql($querycount, $params);
	
	foreach($datos as $dato){	 
		//Define activation icon and url
		if ($dato->status == 1){
			$activateicon_sync = new pix_icon("e/preview", get_string("deactivate", "local_sync"));
			$actionsent = "deactivate";
		}
		else if ($dato->status == 0){
			$activateicon_sync = new pix_icon("e/accessibility_checker", get_string("activate","local_sync"));
			$actionsent = "activate";
		}
		$activateurl_sync= new moodle_url("/local/sync/record.php", array(
				"action" => $actionsent,
				"syncid" => $dato->id
		));
		
		$activatection_sync = $OUTPUT->action_icon(
				$activateurl_sync,
				$activateicon_sync,
				new confirm_action(get_string("deletesync", "local_sync"))
				);
		//Define manual_unsub icon and url
		$manualurl_sync= new moodle_url("/local/sync/record.php", array(
				"action" => "manual",
				"syncid" => $dato->id,));
		$manualicon_sync = new pix_icon("t/delete", get_string("unenrol","local_sync"));
		$manualaction_sync = $OUTPUT->action_icon(
				$manualurl_sync,
				$manualicon_sync,
				new confirm_action(get_string("deletesync", "local_sync"))
				);
		//Define icon and url to eliminate self enrolled
		$selfurl_sync= new moodle_url("/local/sync/record.php", array(
				"action" => "self",
				"syncid" => $dato->id,));
		$selficon_sync = new pix_icon("t/delete", get_string("unenrol","local_sync"));
		$selfaction_sync = $OUTPUT->action_icon(
				$selfurl_sync,
				$selficon_sync,
				new confirm_action(get_string("deletesync", "local_sync"))
				);
		$editurl_sync = new moodle_url("/local/sync/record.php", array(
				"action" => "edit",
				"syncid" => $dato->id));
		$editicon_sync = new pix_icon("i/edit", "Editar");
		$editaction_sync = $OUTPUT->action_icon(
				$editurl_sync,
				$editicon_sync,
				new confirm_action(get_string("editform", "local_sync"))
				);
		 
		$extra = array();
		$extra[] = $tablecount+1;
		$extra[]=date("Y-m-d", $dato->timecreated);
		$extra[]=$dato->academicperiodname;
		$extra[]=$dato->academicperiodid;
		$extra[]=$dato->category;
		$extra[]=$dato->categoryid;
		$extra[]=$dato->campus;
		$extra[]=$dato->responsible;
		$extra[]=$activatection_sync;
		$extra[]=$manualaction_sync;
		$extra[]=$selfaction_sync;
		$extra[]=$editaction_sync;
		$synctable->add_data($extra);
		$tablecount++;
	}

	$synctable->finish_html();
	echo $OUTPUT->paging_bar($synccount, $page, $perpage,
			$CFG->wwwroot . '/local/sync/record.php');
}

//fin de la pagina	
echo $OUTPUT->footer();