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
 * @package local
 * @subpackage sync
 * @copyright Javier Gonzalez (javiergonzalez@alumnos.uai.cl)
 * @copyright Hans Jeria (hansjeria@gmail.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . "/tablelib.php");
require_once($CFG->dirroot . "/local/sync/locallib.php");
global $PAGE, $CFG, $OUTPUT, $DB;

// User must be logged in.
require_login();
if (isguestuser()) {
    die();
}

$dataid = optional_param("dataid", 1, PARAM_INT);
$tsort = optional_param('tsort', '', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$nofpages = optional_param('page', 0, PARAM_INT);
$perpage = 10;

$url = new moodle_url('/local/sync/history.php');
$context = context_system::instance();
if(!has_capability("local/sync:history", $context)) {
	print_error("ACCESS DENIED");
}
$PAGE->navbar->add(get_string("sync_title", "local_sync"));
$PAGE->navbar->add(get_string("h_tabletitle", "local_sync"),$url);
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string("h_title", "local_sync"));
$PAGE->set_heading(get_string("h_title", "local_sync"));

$table = new html_table("p"); 
$table->head = array(
		get_string("h_id", "local_sync"),
		get_string("h_catid", "local_sync"),
		get_string("h_catname", "local_sync"),
		get_string("h_academicperiodid", "local_sync"),
		get_string("h_academicperiodname", "local_sync"),
		get_string("h_executiontime", "local_sync"),
		get_string("h_synccourses", "local_sync"),
		get_string("h_syncenrols", "local_sync")
);
$table->size = array(
		"7%",
		"7%",
		"20%",
		"15%",
		"20%",
		"20%",
		"6%",
		"5%"
);

$orderby = "ORDER BY h.executiondate DESC";

$query = "SELECT h.id as historyid,
		d.id, 
		d.categoryid, 
		c.name, 
		d.academicperiodid,
		d.academicperiodname,
		h.executiondate, 
		h.countcourses, 
		h.countenrols
		FROM {sync_data} AS d
		INNER JOIN {sync_history} as h
		INNER JOIN {course_categories} as c
		ON d.id = h.dataid
		AND c.id = d.categoryid
		$orderby";

$nofpages = count($DB->get_records_sql($query));
$lastthirtysync = $DB->get_records_sql($query, array (""), $page * $perpage, $perpage);

foreach($lastthirtysync as $last){
	$table->data[] = array(
			$last->historyid,
			$last->categoryid,
			$last->name,
			$last->academicperiodid,
			$last->academicperiodname,
			date("Y-m-d h:i:sa",$last->executiondate),
			$last->countcourses,
			$last->countenrols
	);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string("h_tabletitle", "local_sync"));
echo $OUTPUT->tabtree(sync_tabs(), "history");
if ($nofpages>0){
	if ($nofpages>30){
		$nofpages = 30;
	}
	echo html_writer::table($table);
	echo $OUTPUT->paging_bar($nofpages, $page, $perpage,
			$CFG->wwwroot . '/local/sync/history.php?page=');
}
else{
	echo $OUTPUT->notification(get_string("h_emptytable", "local_sync"));
}
echo $OUTPUT->footer();