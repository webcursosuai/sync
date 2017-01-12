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
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

//Configuraciones globales
require_once(dirname(dirname(dirname(__FILE__))) . "/config.php");
require_once ($CFG->dirroot . "/repository/lib.php");
require_once($CFG->dirroot . "/local/sync/forms/sync_form.php");
global $CFG, $DB, $OUTPUT, $PAGE;


// User must be logged in.
require_login();
if (isguestuser()) {
    die();
}

//Pagina moodle basico
$context = context_system::instance();

// Blocks access if user doesn't have capability to create synchronizations
if(!has_capability("local/sync:create", $context)) {
	print_error("ACCESS DENIED");
}

$url = new moodle_url("/local/sync/create.php");

$PAGE->navbar->add(get_string("sync_title", "local_sync"));
$PAGE->navbar->add(get_string("sync_subtitle", "local_sync"),$url);
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout("standard");
$PAGE->set_title(get_string("sync_page", "local_sync"));
$PAGE->set_heading(get_string("sync_heading", "local_sync"));

$insert = optional_param("insert", "", PARAM_TEXT);

$addform = new sync_form();

if($addform->is_cancelled()) {
	$formurl = new moodle_url("/local/sync/record.php");
	redirect($formurl);
}

else if($creationdata = $addform->get_data()) {
	$record = new stdClass();
	$perioddata = explode("|", $creationdata->period);
	
	$record->academicperiodid = $perioddata[0];
	$record->academicperiodname = $perioddata[4];
	$record->categoryid = $creationdata->category;
	$record->campus = $perioddata[1];
	$record->campusshort = explode("-", $perioddata[1])[1];
	$record->type = $perioddata[2];
	$record->year = $perioddata[3];
	$record->semester = $perioddata[5];
	$record->timecreated = time();
	$record->timemodified = $record->timecreated;
	$record->responsible = $creationdata->responsible;
	$record->status = $creationdata->status;
	
	$dataid = $DB->insert_record("sync_data", $record);
	
	$formurl = new moodle_url("/local/sync/record.php", array(
			"insert" => "success",
			"dataid" => $dataid
	));
	redirect($formurl);
}else {
	echo $OUTPUT->header();
	echo $OUTPUT->heading(get_string("sync_sub_heading", "local_sync"));
	echo $OUTPUT->tabtree(sync_tabs(), "create");
	$addform->display();
}

echo $OUTPUT->footer();