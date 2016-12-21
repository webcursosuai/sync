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
* @subpackage rivano
* @copyright  2016 Joaquin Rivano (jrivano@alumnos.uai.cl) 					
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

defined('MOODLE_INTERNAL') || die();
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir . "/formslib.php");


//creo el formulario de omega
class sync_form extends moodleform {

	public function definition() {
		global $DB,$CFG;
		
	   
		
		$omega_sinc = array("curso 1","curso 2","curso 3","curso 4");
		$webc_sinc = array("webc 1","webc 2");
		
		//select omega
		$mform = $this->_form;
		$mform->addElement('select', 'omega', get_string('omega','local_sync'), $omega_sinc);
		$mform->setType('omega' , PARAM_TEXT);
		
		//Link Periodos
		
		//select Webcursos
		$mform = $this->_form;
		$mform->addElement('select', 'webc', get_string('webc','local_sync'), $webc_sinc);
		$mform->setType('webc' , PARAM_TEXT);
		
		//text area encargado
		$mform = $this->_form;
		$mform->addElement('text', 'in_charge', get_string('in_charge','local_sync')); 
        $mform->setType('in_charge', PARAM_NOTAGS);                   
        $mform->setDefault('in_charge', get_string('in_charge_default','local_sync'));        
		
				$this->add_action_buttons($cancel = true, $submitlabel= get_string('buttons','local_sync'));
		
	}
}
	//validacion de uso del formulario
function validation($data, $files){
			
		$errors = array();
	
		if($data['omega_sync'] == 0){
			$errors['omega'] = get_string('error_omega','local_sync');
		}
	
		return $errors;
			
	}
