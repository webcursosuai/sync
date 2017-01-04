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


// User must be logged in.
require_login();
if (isguestuser()) {
    //die();
}

//Pagina moodle basico
$context = context_system::instance();
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

//action delete
if ($action == "delete") {
	if ($syncid == null) {
		print_error(get_string("syncdoesnotexist", "local_sync"));
		$action = "view";
	}
	else {
		if ($module = $DB->get_record("sync_data", array("id" => $syncid))) {
			$DB->delete_records("sync_data", array("id" => $syncid));
		}
	}
	$url = new moodle_url('/local/sync/record.php', array("action"=>"view"));
	redirect($url);
}

//action view
if ($action == "view") {
	echo $OUTPUT->header();
	echo $OUTPUT->heading(get_string("synctable", "local_sync"));
	echo $OUTPUT->tabtree(sync_tabs(), "record");

	$tablecount = 10 * $page;
	$synctable = new flexible_table("sync");
	$synctable->define_baseurl(new moodle_url("/local/sync/record.php"));
	$synctable->define_columns(array(
			"number",
			"academicperiodname",
			"academicperiodid" ,
			"category",
			"categoryid",
			"campus",
			"responsible",
			"1",
			"2",
			"3" ));
	$synctable->define_headers(array(
			"",
			get_string("academicperiod", "local_sync"),
			get_string("periodid","local_sync"),
			get_string("category","local_sync"),
			get_string("categoryid","local_sync"),
			get_string("sede","local_sync"),
			get_string("in_charge","local_sync"),
			get_string("activation","local_sync"),
			get_string("manualunsub","local_sync"),
			get_string("edit","local_sync")));
	$synctable->sortable(true,"academicperiodname",SORT_DESC);
	$synctable->no_sorting("1","2","3");
	$synctable->setup();
	if ($synctable->get_sql_sort()) {
		$sort = 'ORDER BY '. $synctable->get_sql_sort();
	} else {
		$sort = '';
	}
	list($where, $params) = $synctable->get_sql_where();
	if ($where) {
		$where = 'WHERE '. $where;
	}
	
	$querycount = "SELECT count(*)
			       FROM {sync_data} AS s
			       ";

	$query = "SELECT s.id AS id, s.academicperiodid , s.academicperiodname, s.categoryid, s.campus, c.name AS category , s.responsible AS responsible
	FROM {sync_data} AS s
	INNER JOIN {course_categories} c ON (c.id = s.categoryid )
	$where
	$sort";
	
	$datos = $DB->get_records_sql($query,
			$params,
			$page * $perpage,
			($page + 1) * $perpage);

	$synccount = $DB->count_records_sql($querycount,
			$params,
			$synctable->get_page_start(),
			$synctable->get_page_size(),
			$page * $perpage,
			($page + 1) * $perpage);
	
	foreach($datos as $dato){
		 
		//Define activation icon and url
		$activateurl_sync= new moodle_url("/local/sync/record.php", array(
				"action" => "activate",
				"syncid" => $dato->id,));
		$activateicon_sync = new pix_icon("i/edit", "Borrar");
		$activatection_sync = $OUTPUT->action_icon(
				$activateurl_sync,
				$activateicon_sync,
				new confirm_action(get_string("deletesync", "local_sync"))
				);
		//Define manual_unsub icon and url
		$manualurl_sync= new moodle_url("/local/sync/record.php", array(
				"action" => "manual",
				"syncid" => $dato->id,));
		$manualicon_sync = new pix_icon("t/delete", "Borrar");
		$manualaction_sync = $OUTPUT->action_icon(
				$manualurl_sync,
				$manualicon_sync,
				new confirm_action(get_string("deletesync", "local_sync"))
				);
		// Define delete icon and url
		$deleteurl_sync= new moodle_url("/local/sync/record.php", array(
				"action" => "delete",
				"syncid" => $dato->id,));
		$deleteicon_sync = new pix_icon("t/delete", "Borrar");
		$deleteaction_sync = $OUTPUT->action_icon(
				$deleteurl_sync,
				$deleteicon_sync,
				new confirm_action(get_string("deletesync", "local_sync"))
				);
		// Define edition icon and url
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
		$extra[] = $tablecount;
		$extra[]=$dato->academicperiodname;
		$extra[]=$dato->academicperiodid;
		$extra[]=$dato->category;
		$extra[]=$dato->categoryid;
		$extra[]=$dato->campus;
		$extra[]=$dato->responsible;
		$extra[]=$activatection_sync;
		$extra[]=$manualaction_sync;
		$extra[]=$deleteaction_sync . $editaction_sync;
		$synctable->add_data($extra);
		$tablecount++;
	}

	$synctable->finish_html();
	echo $OUTPUT->paging_bar($synccount, $page, $perpage,
			$CFG->wwwroot . '/local/sync/record.php');
}

		 
		
			
//fin de la pagina	
echo $OUTPUT->footer();