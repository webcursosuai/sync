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
* 			  2016 Mark Michaelsen (mmichaelsen678@gmail.com)
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

//Configuraciones globales
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once ($CFG->dirroot . '/local/sync/locallib.php');
require_once($CFG->dirroot . '/local/sync/forms/sync_form.php');
global $CFG, $DB, $OUTPUT, $PAGE;


// User must be logged in.
require_login();
if (isguestuser()) {
    die();
}

//Pagina moodle basico
$context = context_system::instance();

$url = new moodle_url('/local/sync/create.php');

$PAGE->navbar->add(get_string('sync_title', 'local_sync'));
$PAGE->navbar->add(get_string('sync_subtitle', 'local_sync'),$url);
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string("sync_page", "local_sync"));
$PAGE->set_heading(get_string("sync_heading", "local_sync"));
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string("sync_sub_heading", "local_sync"));
echo $OUTPUT->tabtree(sync_tabs(), "create");


//Agrego y muestro formulario
$addform = new sync_form();
$addform->display();

echo $OUTPUT->footer();