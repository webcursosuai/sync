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
require_once(dirname(dirname(dirname(__FILE__))) . "/config.php");
require_once ($CFG->dirroot . "/local/sync/locallib.php");
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . "/local/sync/forms/edit_form.php");
global $CFG, $DB, $OUTPUT,$COURSE, $USER, $PAGE;

$page = optional_param('page', 0, PARAM_INT);
$perpage = 10;


// User must be logged in.
require_login();
if (isguestuser()) {
	//die();
}

$insert = optional_param("insert", "", PARAM_TEXT);

//Pagina moodle basico
$context = context_system::instance();

$url = new moodle_url("/local/sync/record.php");

$PAGE->navbar->add(get_string("sync_title", "local_sync"));
$PAGE->navbar->add(get_string("sync_record_title", "local_sync"),$url);
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout("standard");
$PAGE->set_title(get_string("sync_page", "local_sync"));
$PAGE->set_heading(get_string("sync_heading", "local_sync"));
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string("sync_table", "local_sync"));
echo $OUTPUT->tabtree(sync_tabs(), "record");

// Action = { view, edit, delete}, all page options.
$action = optional_param("action", "view", PARAM_TEXT);
$syncid = optional_param("syncid", null, PARAM_INT);

if($insert == "success") {
	echo $OUTPUT->notification(get_string("sync_success", "local_sync"), "notifysuccess");
}

if ($action == "view") {
	$synctable = new flexible_table("sync");
	$synctable->define_baseurl(new moodle_url("/local/sync/record.php"));
	$synctable->define_columns(array('academicperiodname', 'academicperiodid' , 'category',"categoryid","campus","Activation","manual_unsub","edit" ));
	$synctable->define_headers(array(
	get_string("academic_period", "local_sync"),
	get_string("period_id","local_sync"),
	get_string("category","local_sync"),
	get_string("category_id","local_sync"),
	get_string("sede","local_sync"),
	get_string("Activation","local_sync"),
	get_string("manual_unsub","local_sync"),
	get_string("edit","local_sync"),
	));
	$synctable->sortable(true,"academicperiodname",SORT_DESC);
	$synctable->no_sorting("Activation","manual_unsub","edit" );
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

	$query = "SELECT s.id as id, s.academicperiodid , s.academicperiodname, s.categoryid, s.campus, c.name as category
                      FROM mdl_sync_data as s
                      INNER JOIN mdl_course_categories c ON (c.id = s.categoryid )
                      $where
                      $sort
                      ";

                      $datos = $DB->get_records_sql($query,$params,$synctable->get_page_start(),$synctable->get_page_size(),$page * $perpage, ($page + 1) * $perpage);
                      foreach($datos as $dato){

                      	//Define activation icon and url
                      	$activateurl_sync= new moodle_url("/local/sync/record.php", array(
					"action" => "activate",
					"syncid" => $syncid->id,
                      	));
                      	$activateicon_sync = new pix_icon("i/edit", "Borrar");
                      	$activatection_sync = $OUTPUT->action_icon(
                      	$activateurl_sync,
                      	$activateicon_sync,
                      	new confirm_action(get_string("delete_sync", "local_sync"))
                      	);

                      	//Define manual_unsub icon and url
                      	$manualurl_sync= new moodle_url("/local/sync/record.php", array(
					"action" => "manual",
					"syncid" => $syncid->id,
                      	));
                      	$manualicon_sync = new pix_icon("t/delete", "Borrar");
                      	$manualaction_sync = $OUTPUT->action_icon(
                      	$manualurl_sync,
                      	$manualicon_sync,
                      	new confirm_action(get_string("delete_sync", "local_sync"))
                      	);

                      	// Define delete icon and url
                      	$deleteurl_sync= new moodle_url("/local/sync/record.php", array(
					"action" => "delete",
					"syncid" => $syncid->id,
                      	));
                      	$deleteicon_sync = new pix_icon("t/delete", "Borrar");
                      	$deleteaction_sync = $OUTPUT->action_icon(
                      	$deleteurl_sync,
                      	$deleteicon_sync,
                      	new confirm_action(get_string("delete_sync", "local_sync"))
                      	);

                      	// Define edition icon and url
                      	$editurl_sync = new moodle_url("/local/sync/record.php", array(
					"action" => "edit",
					"syncid" => $syncid->id
                      	));
                      	$editicon_sync = new pix_icon("i/edit", "Editar");
                      	$editaction_sync = $OUTPUT->action_icon(
                      	$editurl_sync,
                      	$editicon_sync,
                      	new confirm_action(get_string("edit", "local_sync"))
                      	);


                      	$extra = array();

                      	$extra[]=$dato->academicperiodname;
                      	$extra[]=$dato->academicperiodid;
                      	$extra[]=$dato->category;
                      	$extra[]=$dato->categoryid;
                      	$extra[]=$dato->campus;
                      	$extra[]=$activatection_sync;
                      	$extra[]=$manualaction_sync;
                      	$extra[]=$deleteaction_sync . $editaction_sync;

                      	$synctable->add_data($extra);

                      }

                      $synctable->finish_html();
}

//action edit
if ($action == "edit") {
	if ($syncid== null) {
		print_error(get_string("syncdoesnotexist", "local_sync"));
		$action = "view";
	}
	else {
		if ($module = $DB->get_record("sync_data", array("id" => $syncid))){
			$editform = new sync_editmodule_form(null, array("idmodule" => $syncid));
			$defaultdata = new stdClass();
			$defaultdata->academicperiodname = $dato->academicperiodname;
			$defaultdata->academicperiodid = $dato->academicperiodid;
			$defaultdata->category = $dato->category;
			$defaultdata->categoryid = $dato->categoryid;
			$defaultdata->campus = $dato->campus;
			$editform->set_data($defaultdata);

			if ($editform->is_cancelled()) {
				$action = "view";

				$url = new moodle_url('/local/paperattendance/modules.php');
				redirect($url);

			}
			else if ($editform->get_data() && $sesskey == $USER->sesskey) {
				$record = new stdClass();
				$record->id = $editform->get_data()->idmodule;
				$record->name = $editform->get_data()->name;
				$record->initialtime = $editform->get_data()->initialtime;
				$record->endtime = $editform->get_data()->endtime;
				$DB->update_record("paperattendance_module", $record);
				$action = "view";

				$url = new moodle_url('/local/paperattendance/modules.php');
				redirect($url);
			}
		}
		else {
			print_error(get_string("moduledoesnotexist", "local_paperattendance"));
			$action = "view";
			$url = new moodle_url('/local/paperattendance/modules.php');
			redirect($url);
		}
	}
}
//action delete
if ($action == "delete") {
	if ($idmodule == null) {
		print_error(get_string("moduledoesnotexist", "local_paperattendance"));
		$action = "view";
	}
	else {
		if ($module = $DB->get_record("paperattendance_module", array("id" => $idmodule))) {
			if ($sesskey == $USER->sesskey) {
				$DB->delete_records("paperattendance_module", array("id" => $module->id));
				$action = "view";
			}
			else {
				print_error(get_string("usernotloggedin", "local_paperattendance"));
			}
		}
		else {
			print_error(get_string("moduledoesnotexist", "local_paperattendance"));
			$action = "view";
		}
	}
	$url = new moodle_url('/local/paperattendance/modules.php');
	redirect($url);
}


			
if($action == "activate") {}

//fin de la pagina

echo $OUTPUT->footer();