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
 * @copyright  2016 Javier Gonzalez (javiergonzalez@alumnos.uai.cl)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require "$CFG->libdir/tablelib.php";
global $PAGE, $CFG, $OUTPUT, $DB;


$action = optional_param("action", "view", PARAM_TEXT);
$status = optional_param("status", null, PARAM_TEXT);
$categoryid = optional_param("deleteid", null, PARAM_INT);

require_login();

$url = new moodle_url('/local/sync/deletemanualenrolment.php');
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string("h_title", "local_sync"));    //Cambiar titulo
$PAGE->set_heading(get_string("h_title", "local_sync")); //Cambiar titulo
$url = new moodle_url('/local/sync/deletemanualenrolment.php');

$categoryid = 1;
$action = "delete";
//"Delete"
if ($action == "delete"){
	if ($categoryid == null){
		$status = "No hay nada seleccionado para borrar";
		$action = "view";
	}
	else{
		$sql = "SELECT ue.id
				FROM {user_enrolments} as ue 
				INNER JOIN {enrol} AS e ON e.id = ue.enrolid
				INNER JOIN {course} as c ON c.id = e.courseid 
				INNER JOIN {course_categories} as cc ON cc.id = c.category
				WHERE e.enrol = 'manual'

				AND cc.id =?";
		//var_dump($sqlin);
		$todelete = $DB->get_records_sql($sql, array("cc.id" => $categoryid));
		var_dump($todelete);
		
		$arr = array();
		foreach($todelete as $idtodelete){
			$arr[]=$idtodelete->id;
		}
		var_dump($arr);
		list($sqlin, $param) = $DB->get_in_or_equal($arr);
		$query = "DELETE 
				FROM {user_enrolments} 
				WHERE {user_enrolments}.id $sqlin";
		var_dump($sqlin);
		var_dump($param);
		
		$deleter= $DB->execute($query, $param);
		}
}