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
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . "/config.php");
require_once ($CFG->dirroot . "/repository/lib.php");
require_once($CFG->dirroot . "/local/sync/forms/sync_form.php");
global $CFG, $DB, $OUTPUT, $PAGE;

$url = new moodle_url("/local/sync/create.php");

$context = context_system::instance();

$PAGE->navbar->add(get_string("sync_title", "local_sync"));
$PAGE->navbar->add(get_string("sync_subtitle", "local_sync"),$url);
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout("standard");
$PAGE->set_title(get_string("sync_page", "local_sync"));
$PAGE->set_heading(get_string("sync_heading", "local_sync"));

$periods = $DB->get_records("sync_data", array("status" => 1));

$ids = array();
foreach($periods as $period) {
	$ids[] = $period->academicperiodid;
}

$curl = curl_init();
$url = "http://webapitest.uai.cl/webcursos/GetCursos";
$token = "webisis54521kJusm32ADDddiiIsdksndQoQ01";

$fields = array(
		"token" => $token,
		"PeriodosAcademicos" => $ids
);
	
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curl, CURLOPT_POST, TRUE);
curl_setopt($curl, CURLOPT_POSTFIELDS,json_encode($fields));
curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));

$result = json_decode(curl_exec($curl));
curl_close($curl);

var_dump($result);