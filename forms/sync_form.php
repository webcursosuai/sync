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
class sync_form extends moodleform {
	public function definition() {
		global $CFG, $DB;
		
		$mform = $this->_form;
		
		// Select academic period
		$periods = array();
		
		$curl = curl_init();
		$url = "http://webapitest.uai.cl/webcursos/getperiodosacademicos";
		$token = "webisis54521kJusm32ADDddiiIsdksndQoQ01";
		
		$fields = array(
				"token" => $token
		);
		 
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_POST, TRUE);
		curl_setopt($curl, CURLOPT_POSTFIELDS,json_encode($fields));
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
		
		$result = json_decode(curl_exec($curl));
		curl_close($curl);
		
		foreach($result as $period) {
			$id = $period->periodoAcademicoId;
			$periodname = $period->periodoAcademico;
			$periodcampus = $period->sede;
			$periodtype = $period->tipo;
			
			$periods[$id."|".$periodcampus."|".$periodtype."|".$period->AnoPeriodo."|".$periodname."|".$period->NumeroPeriodo] = $id." | ".$periodname." | ".$periodcampus." | ".$periodtype;
		}
		
		krsort($periods);
		
		$beginning = array("");
		$options = $beginning + $periods;
		
		$mform->addElement("select", "period", get_string("omega","local_sync"), $options);
		$mform->setType("period" , PARAM_TEXT);
		
		//Link Periodos
		
		//select Webcursos
		$categoriessql = "SELECT cc.id AS id,
				cc.name AS name,
				cc.path AS path
				FROM {course_categories} AS cc
				WHERE visible = ?";
		
		$params = array(1);
		
		$categoriesset = $DB->get_recordset_sql($categoriessql, $params);
		
		$categories = array();
		$categories[0] = "";
		
		$unpathedcategories = array();
		$path = array();
		
		foreach($categoriesset as $category) {
			$unpathedcategories[$category->id] = $category->name;
			$path[$category->id] = explode("/", $category->path);
		}
		
		$categoriesset->close();
		
		foreach($unpathedcategories as $id => $name) {
			$finalpath = "$id";
			foreach($path[$id] as $pathid) {
				if($pathid != "") {
					$finalpath .= " | ".$unpathedcategories[$pathid];
				}
			}
			$categories[$id] = $finalpath;
		}
		
		$mform->addElement("select", "category", get_string("webc", "local_sync"), $categories);
		$mform->setType("category" , PARAM_TEXT);
		
		//text area encargado
		$mform->addElement("text", "responsible", get_string("in_charge", "local_sync")); 
        $mform->setType("responsible", PARAM_NOTAGS);
		
		$this->add_action_buttons($cancel = true, $submitlabel= get_string("buttons", "local_sync"));
		
	}
	
	public function validation($data, $files) {
		global $DB;
		$errors = array();
		
		$academicperiod = $data["period"];
		$category = $data["category"];
		$responsible = $data["responsible"];
		
		if (!isset($academicperiod) || empty($academicperiod) || $academicperiod == 0 || $academicperiod == null) {
			$errors["period"] = get_string("error_period", "local_sync");
		}
		
		if (!isset($category) || empty($category) || $category == 0 || $category == null) {
			$errors["category"] = get_string("error_omega", "local_sync");
		}
		
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