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
 * This file keeps track of upgrades to the evaluaciones block
 *
 * Sometimes, changes between versions involve alterations to database structures
 * and other major things that may break installations.
 *
 * The upgrade function in this file will attempt to perform all the necessary
 * actions to upgrade your older installation to the current version.
 *
 * If there's something it cannot do itself, it will tell you what you need to do.
 *
 * The commands in here will all be database-neutral, using the methods of
 * database_manager class
 *
 * Please do not forget to use upgrade_set_timeout()
 * before any action that may take longer time to finissync_history.
 *
 * @package local
 * @subpackage sync
 * @copyright Javier Gonzalez (javiergonzalez@alumnos.uai.cl)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require "$CFG->libdir/tablelib.php";
global $PAGE, $CFG, $OUTPUT, $DB;

require_login();


$url = new moodle_url('/local/sync/history.php');
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string("h_title", "local_sync"));
$PAGE->set_heading(get_string("h_title", "local_sync"));
$url = new moodle_url('/local/sync/history.php');
$dataid = optional_param("dataid", 1, PARAM_INT);
$tsort = optional_param('tsort', '', PARAM_ALPHA);

$page = optional_param('page', 0, PARAM_INT);
$perpage = 10;
$nofpages = optional_param('page', 0, PARAM_INT);

$table = new html_table("p"); 

$table->head =array(
		get_string("h_id", "local_sync"),
		get_string("h_catid", "local_sync"),
		get_string("h_catname", "local_sync"),
		get_string("h_academicperiodid", "local_sync"),
		get_string("h_academicperiodname", "local_sync"),
		get_string("h_executiontime", "local_sync"),
		get_string("h_synccourses", "local_sync"),
		get_string("h_syncenrols", "local_sync")
);

$orderby = "ORDER BY h.executiondate DESC";

$query = "SELECT d.id, 
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

$nofpages = count($DB->get_records_sql($query, array("")));
$lastthirtysync = $DB->get_records_sql($query, array (""), $page * $perpage, $perpage);

foreach($lastthirtysync as $last){
	$table->data[] = array(
			$last->id,
			$last->categoryid,
			$last->name,
			$last->academicperiodid,
			$last->academicperiodname,
			date("Y-m-d h:i:sa",$last->executiondate),
			$last->countcourses,
			$last->countenrols
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
}


echo $OUTPUT->header();
echo $OUTPUT->heading(get_string("h_tabletitle", "local_sync"));
if ($nofpages>0){
	if ($nofpages>30){
		$nofpages = 30;
	}
	echo html_writer::table($table);
	echo $OUTPUT->paging_bar($nofpages, $page, $perpage,
			$CFG->wwwroot . '/local/sync/history.php?page=');
}
else{
	get_string("h_emptytable", "local_sync");
}
echo $OUTPUT->footer();