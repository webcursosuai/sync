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
* @copyright  2016 Hans Jeria (hansjeria@gmail.com)
* @copyright  2016 Joaquin Rivano (jrivano@alumnos.uai.cl)
* @copyright  2016 Mark Michaelsen (mmichaelsen678@gmail.com)
* @copyright  2016 Javier González (javiergonzalez@alumnos.uai.cl)
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir . "/tablelib.php");
require_once ($CFG->dirroot . "/local/sync/locallib.php");
require_once($CFG->dirroot . "/local/sync/forms/edit_form.php");
global $CFG, $DB, $OUTPUT, $PAGE, $USER;

// User must be logged in.
require_login();
if (isguestuser()) {
	die();
}

$insert = optional_param("insert", "", PARAM_TEXT);
$action = optional_param("action", "view", PARAM_TEXT);
$syncid = optional_param("syncid", null, PARAM_INT);
$unenrol = optional_param("unenrol", null, PARAM_TEXT);
$view = optional_param("view", "active", PARAM_TEXT);
$dataid = optional_param("dataid", 0, PARAM_INT);
$page = optional_param("page", 0, PARAM_INT);
$sesskey = optional_param("sesskey", "", PARAM_ALPHANUM);
$perpage = 10;

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

if($action == "delete" && $USER->sesskey == $sesskey) {
	
	list($capable, $message) = sync_validate_deletion($syncid);

	if($capable) {
		if(sync_deletecourses($syncid)) {
			$message .= $OUTPUT->notification(get_string("courses_delete_success", "local_sync"), "notifysuccess");
		} else {
			$message .= $OUTPUT->notification(get_string("courses_delete_failed", "local_sync"));
		}
	} else {
		$message .= $OUTPUT->notification(get_string("courses_delete_check", "local_sync"));
	}
	$recordsurl = new moodle_url("/local/sync/record.php");
	$message .= $OUTPUT->action_link($recordsurl, get_string("back", "local_sync"));
}

if ($action == "edit" && $USER->sesskey == $sesskey) {
	if ($syncid == null) {
		print_error(get_string("syncdoesnotexist", "local_sync"));
		$action = "view";
	}
	else {
		if ($module = $DB->get_record("sync_data", array("id" => $syncid))){
			$editform = new sync_editmodule_form(null, 
					array(
							"datossync" => $module,
							"syncid"=> $syncid
			));
			if ($editform->is_cancelled()) {
				redirect(new moodle_url('/local/sync/record.php', array("action"=>"view")));
			}
			else if ($formdata = $editform->get_data()){
				$updatedata = "UPDATE {sync_data}
				           SET responsible = ?
				           WHERE id = ?";
				$paramupdate = array(
						$formdata -> responsible,
						$syncid
				);
				$DB->execute($updatedata,$paramupdate);
				redirect(new moodle_url('/local/sync/record.php', array("action"=>"view")));
			}
		}
	}
}

if($action == "activate" && $USER->sesskey == $sesskey) {
	$updatedata = new stdClass();	
	$updatedata->id = $syncid;
	$updatedata->status = 1;
	$DB->update_record("sync_data", $updatedata);
	$action = "view";
} else if($action == "deactivate" && $USER->sesskey == $sesskey) {
	$updatedata = new stdClass();	
	$updatedata->id = $syncid;
	$updatedata->status = 0;
	$DB->update_record("sync_data", $updatedata);
	$action = "view";
}

if (($action == "manual" || $action == "self") && $USER->sesskey == $sesskey) {
	$success = false;
	if ($checkstatus = $DB->get_record("sync_data", array("id" => $syncid))) {
		if ($checkstatus->status == 0) {
			list($success, $message) = sync_delete_enrolments($action, $checkstatus->categoryid);
		} else {
			$success = false;
			$message .= $OUTPUT->notification(get_string("unenrol_error_status", "local_sync"));
		}
	} else {
		$success = false;
		$message .= $OUTPUT->notification(get_string("unenrol_fail", "local_sync"));
	}
	$action = "view";
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string("synctable", "local_sync"));

if($action == "edit"){
	echo $OUTPUT->tabtree(sync_tabs(), "record");
	$editform->display();
}

if ($action == "view") {
	echo $OUTPUT->tabtree(sync_tabs(), "record");
	echo $OUTPUT->tabtree(sync_records_tabs(), $view);
	
	if(!empty($message)) {
		echo $message;
	}
	
	$tablecount = 10 * $page;
	$synctable = new flexible_table("sync");
	$synctable->define_baseurl(new moodle_url("/local/sync/record.php"), array("view" => $view));
	
	if($view == "active") {
		$synctable->define_columns(array(
				"number",
				"timecreated",
				"academicperiodname",
				"academicperiodid" ,
				"category",
				"categoryid",
				"campus",
				"responsible",
				"status",
				"edit"
		));	
		$synctable->define_headers(array(
				"#",
				get_string("timecreated", "local_sync"),
				get_string("academicperiod", "local_sync"),
				get_string("periodid", "local_sync"),
				get_string("category", "local_sync"),
				get_string("categoryid", "local_sync"),
				get_string("sede", "local_sync"),
				get_string("in_charge", "local_sync"),
				get_string("activation", "local_sync"),
				get_string("edit", "local_sync")
		));		
		$synctable->style = array(
				"3%",
				"15%",
				"9%",
				"16%",
				"10%",
				"9%",
				"15%",
				"10%",
				"8%",				
				"5%"
		);
		$synctable->no_sorting("number");
		$synctable->no_sorting("status");
		$synctable->no_sorting("edit");
		$synctable->no_sorting("responsible");
		$status = 1;
	} else if($view == "inactive") {
		$synctable->define_columns(array(
				"number",
				"timecreated",
				"academicperiodname",
				"academicperiodid" ,
				"category",
				"categoryid",
				"campus",
				"responsible",
				"status",
				"edit",
				"manual",
				"self",
				"delete"
		));	
		$synctable->define_headers(array(
				"#",
				get_string("timecreated", "local_sync"),
				get_string("academicperiod", "local_sync"),
				get_string("periodid", "local_sync"),
				get_string("category", "local_sync"),
				get_string("categoryid", "local_sync"),
				get_string("sede", "local_sync"),
				get_string("in_charge", "local_sync"),
				get_string("activation", "local_sync"),
				get_string("edit", "local_sync"),
				get_string("manualunsub","local_sync"),
				get_string("selfunsub","local_sync"),
				get_string("delete", "local_sync")
		));		
		$synctable->style = array(
				"3%",
				"10%",
				"9%",
				"9%",
				"10%",
				"10%",
				"10%",
				"10%",
				"6%",
				"4%",
				"6%",
				"6%",
				"5%"
		);
		$synctable->no_sorting("number");
		$synctable->no_sorting("status");
		$synctable->no_sorting("edit");
		$synctable->no_sorting("manual");
		$synctable->no_sorting("self");
		$synctable->no_sorting("delete");
		$status = 0;
	}
	
	$synctable->sortable(true, "timecreated", SORT_DESC);
	$synctable->setup();
	if ($synctable->get_sql_sort()) {
		$sort = "ORDER BY ". $synctable->get_sql_sort();
	} else {
		$sort = "";
	}
	
	list($where, $params) = $synctable->get_sql_where();
	array_push($params, $status);	
	if ($where) {
		$where = "WHERE ". $where;
	}

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
	$datos = $DB->get_records_sql(
			$query,
			$params,
			$page * $perpage,
			($page + 1) * $perpage
	);

	$querycount = "SELECT count(*) 
			FROM {sync_data} AS s
			WHERE status = ?";
	$countenable = $DB->count_records_sql($querycount, array(1));
	$countdisable = $DB->count_records_sql($querycount, array(0));;
	
	foreach($datos as $dato){	 
		if ($dato->status == 1){
			$activateicon_sync = new pix_icon("e/preview", get_string("deactivate", "local_sync"));
			$actionsent = "deactivate";
			$pop = new confirm_action(get_string("activesync", "local_sync"));
		}
		else if ($dato->status == 0){
			$activateicon_sync = new pix_icon("e/accessibility_checker", get_string("activate","local_sync"));
			$actionsent = "activate";
			$pop = new confirm_action(get_string("desactivatesync", "local_sync"));
		}
		
		$activateurl_sync= new moodle_url("/local/sync/record.php", array(
				"action" => $actionsent,
				"syncid" => $dato->id,
				"sesskey" => $USER->sesskey
		));
		
		$activatection_sync = $OUTPUT->action_icon(
				$activateurl_sync,
				$activateicon_sync,
				$pop
		);
		
		//Define manual_unsub icon and url
		$manualurl_sync= new moodle_url("/local/sync/record.php", array(
				"action" => "manual",
				"syncid" => $dato->id,
				"sesskey" => $USER->sesskey,
				"view" => "inactive"
		));
		
		$manualicon_sync = new pix_icon("t/delete", get_string("unenrol","local_sync"));
		$manualaction_sync = $OUTPUT->action_icon(
				$manualurl_sync,
				$manualicon_sync,
				new confirm_action(get_string("deletemanual", "local_sync"))
		);
		
		//Define icon and url to eliminate self enrolled
		$selfurl_sync= new moodle_url("/local/sync/record.php", array(
				"action" => "self",
				"syncid" => $dato->id,
				"sesskey" => $USER->sesskey,
				"view" => "inactive"
		));
		
		$selficon_sync = new pix_icon("t/delete", get_string("unenrol","local_sync"));
		$selfaction_sync = $OUTPUT->action_icon(
				$selfurl_sync,
				$selficon_sync,
				new confirm_action(get_string("deleteself", "local_sync"))
		);
		
		$editurl_sync = new moodle_url("/local/sync/record.php", array(
				"action" => "edit",
				"syncid" => $dato->id,
				"sesskey" => $USER->sesskey
		));
		
		$editicon_sync = new pix_icon("i/edit", "Editar");
		$editaction_sync = $OUTPUT->action_icon(
				$editurl_sync,
				$editicon_sync,
				new confirm_action(get_string("editform", "local_sync"))
		);
		
		$deleteicon = new pix_icon("t/delete", get_string("delete_detail", "local_sync"));
		$deleteurl = new moodle_url("/local/sync/record.php", array(
				"action" => "delete",
				"syncid" => $dato->id,
				"sesskey" => $USER->sesskey,
				"view" => "inactive"
		));
		$deleteaction = $OUTPUT->action_icon(
				$deleteurl,
				$deleteicon,
				new confirm_action(get_string("delete_prompt", "local_sync"))
		);
		
		$extra = array();
		$extra[] = $tablecount+1;
		$extra[] = date("Y-m-d", $dato->timecreated);
		$extra[] = $dato->academicperiodname;
		$extra[] = $dato->academicperiodid;
		$extra[] = $OUTPUT->action_link(
				new moodle_url($CFG->wwwroot."/course/index.php", array("categoryid" => $dato->categoryid)),
				$dato->category
		);
		$extra[] = $OUTPUT->action_link(
				new moodle_url($CFG->wwwroot."/course/index.php", array("categoryid" => $dato->categoryid)),
				$dato->categoryid
		);
		$extra[] = $dato->campus;
		$extra[] = $dato->responsible;
		$extra[] = $activatection_sync;
		$extra[] = $editaction_sync;
		
		if($view == "inactive") {
			$extra[] = $manualaction_sync;
			$extra[] = $selfaction_sync;
			$extra[] = $deleteaction;
		}
		
		$synctable->add_data($extra);
		$tablecount++;
	}
	if($insert == "success") {
		$datasql = "SELECT d.id,
				d.academicperiodname,
				c.name,
				d.status
				FROM {course_categories} AS c INNER JOIN {sync_data} AS d 
				ON (c.id = d.categoryid AND d.id = ?)";
		$data = $DB->get_record_sql($datasql, array($dataid));
		$datastatus = ($data->status == 0) ? "desactivada" : "activada";
		$successtext = ". ";
		$successtext .= get_string("status", "local_sync");
		$successtext .= html_writer::nonempty_tag(
				"b",
				" ".$datastatus
		);
		$successtext .= ". ";
		$successtext .= get_string("category", "local_sync");
		$successtext .= html_writer::nonempty_tag(
				"b",
				" ".$data->name
		);
		$successtext .= " - ";
		$successtext .= get_string("omega", "local_sync");
		$successtext .= html_writer::nonempty_tag(
				"b",
				" ".$data->academicperiodname."."
		);				
		echo $OUTPUT->notification(get_string("sync_success", "local_sync").$successtext, "notifysuccess");
	}
	
	$synctable->finish_html();
	
	if($view == "active") {
		echo $OUTPUT->paging_bar($countenable, $page, $perpage,
			$CFG->wwwroot . '/local/sync/record.php?view=active');
	} else if($view == "inactive") {
		echo $OUTPUT->paging_bar($countdisable, $page, $perpage,
			$CFG->wwwroot . '/local/sync/record.php?view=inactive');
	}
}

echo $OUTPUT->footer();