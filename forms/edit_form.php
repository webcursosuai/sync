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
		//responsible
		$mform->addElement("text", "responsible", get_string("in_charge", "local_sync"));
		$mform->setDefault("responsible",$syncdata->responsible);
		$mform->setType("responsible", PARAM_TEXT);                  
				
		$mform->addElement("hidden", "action", "edit");
		$mform->setType("action", PARAM_TEXT);
		
		$mform->addElement("hidden", "syncid", $instance['syncid']);
		$mform->setType("syncid", PARAM_INT);
		
		$this->add_action_buttons(true);
	}
	//validacion de uso del formulario
	public function validation($data, $files) {
		global $DB;
		$errors = array();
		
		$responsible = $data["responsible"];
		
		if($responsible != "") {
			if(explode("@", $responsible)[1] != "uai.cl") {
				$errors["responsible"] = get_string("error_responsible_invalid", "local_sync");
			} else if(!$DB->record_exists("user", array("email" => $responsible))) {
				$errors["responsible"] = get_string("error_responsible_nonexistent", "local_sync");
			}
		}
		
		return $errors;
	}
}