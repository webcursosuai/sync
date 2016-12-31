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

defined("MOODLE_INTERNAL") || die();
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . "/config.php");
require_once($CFG->libdir . "/formslib.php");

// Form definition for synchronization creation
class sync_editmodule_form extends moodleform {
	public function definition() {
		global $CFG, $DB;

		$mform = $this->_form;
		$instance = $this->_customdata;
		$syncdata = $instance["datossync"];
	
		//edit form
		//academic_period
		$mform->addElement('text', 'academicperiodname', get_string('academic_period', 'local_sync'));
		$mform->setDefault('academicperiodname',$syncdata->academicperiodname);
		$mform->setType('academicperiodname', PARAM_TEXT);                  
		//academicperiodid
		$mform->addElement('text', 'academicperiodid', get_string('period_id', 'local_sync'));
		$mform->setDefault('academicperiodid',$syncdata->academicperiodid);
		$mform->setType('academicperiodid', PARAM_TEXT);
		//category
		$mform->addElement('text', 'category', get_string('category', 'local_sync'));
		$mform->setDefault('category',$syncdata->category);
		$mform->setType('category', PARAM_TEXT);
		//categoryid
		$mform->addElement('text', 'categoryid', get_string('category_id', 'local_sync'));
		$mform->setDefault('categoryid',$syncdata->categoryid);
		$mform->setType('categoryid', PARAM_TEXT);
		//campus
		$mform->addElement('text', 'campus', get_string('sede', 'local_sync'));
		$mform->setDefault('campus',$syncdata->campus);
		$mform->setType('campus', PARAM_TEXT);
		
		$mform->addElement("hidden", "action", "edit");
		$mform->setType("action", PARAM_TEXT);
		$mform->addElement("hidden", "syncid", $instance['syncid']);
		$mform->setType("syncid", PARAM_RAW);
	
		
		$this->add_action_buttons(true);
	}
	//validacion de uso del formulario
	public function validation($data, $files) {
		$errors = array();
		return $errors;
	}
}