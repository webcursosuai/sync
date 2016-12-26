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

//Configuraciones globales
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once ($CFG->dirroot . '/local/sync/locallib.php');
global $CFG, $DB, $OUTPUT,$COURSE, $USER, $PAGE;           


// User must be logged in.
require_login();
if (isguestuser()) {
    //die();
}

//Pagina moodle basico
$context = context_system::instance();

$url = new moodle_url('/local/sync/record.php');

$PAGE->navbar->add(get_string('sync_title', 'local_sync'));
$PAGE->navbar->add(get_string('sync_record_title', 'local_sync'),$url);
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string("sync_page", "local_sync"));
$PAGE->set_heading(get_string("sync_heading", "local_sync"));
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string("sync_table", "local_sync"));
echo $OUTPUT->tabtree(sync_tabs(), "record");

/*
        // Edits an existent record
           if($action == "edit"){
	          if($syncid == null){
		         print_error(get_string("sync_notselected", "local_sync"));
		         $action = "view";
	       }else{
		    if($sync = $DB->get_record("pluginboletas_boletas", array("id" => $idboleta))){
			 $editform = new editboleta_form(null, array(
					"idboleta" => $idboleta
			));
			
			     $defaultdata = new stdClass();
			     $defaultdata->usuarios_id = $boleta->usuarios_id;
			     $defaultdata->sedes_id = $boleta->sedes_id;
			     $defaultdata->monto = $boleta->monto;
			     $editform->set_data($defaultdata);
			
			 if($editform->is_cancelled()){
				$action = "view";
			    }else if($editform->get_data()){
				$record = new stdClass();
				$record->id = $idboleta;
				$record->usuarios_id = $editform->get_data()->usuarios_id;
				$record->sedes_id = $editform->get_data()->sedes_id;
				$record->monto = $editform->get_data()->monto;
				
				$DB->update_record("pluginboletas_boletas", $record);
				$action = "view";
			}
		}else{
			print_error(get_string("receipt_doesntexist", "local_pluginboletas"));
			$action = "view";
		}
	}
}
           // Delete the selected record
              if ($action == "delete"){
	              if ($idboleta == null){
		      print_error(get_string("receipt_notselected", "local_pluginboletas"));
		$action = "view";
	}else{
		if ($boleta = $DB->get_record("pluginboletas_boletas", array("id" => $idboleta))){
			$DB->delete_records("pluginboletas_boletas", array("id" => $boleta->id));
			$action = "view";
		}else{
			print_error(get_string("receipt_doesntexist", "local_pluginboletas"));
			$action = "view";
		}
	}
}
			
			
			
			
			// Define deletion icon and url
			$deleteurl_sync= new moodle_url("/local/sync/record.php", array(
					"action" => "delete",
					"syncid" => $sync->id,
			));
			$deleteicon_sync = new pix_icon("t/delete", "Borrar");
			$deleteaction_sync = $OUTPUT->action_icon(
					$deleteurl_sync,
					$deleteicon_sync,
					new confirm_action(get_string("delete_sync", "local_sync"))
			);
			
			// Define edition icon and url
			$editurl_sync = new moodle_url("/local/sync/create.php", array(
					"action" => "edit",
					"syncid" => $sync->id
			));
			$editicon_sync = new pix_icon("i/edit", "Editar");
			$editaction_sync = $OUTPUT->action_icon(
					$editurl_sync,
					$editicon_sync,
					new confirm_action(get_string("edit", "local_sync"))
			);
 

*/

            $query = "SELECT s.id, s.academicperiodid , s.categoryid, s.campus, c.name  
                      FROM mdl_sync_data as s
                      INNER JOIN mdl_course_categories c ON (c.id = s.categoryid )
                      ORDER BY s.id desc
                      LIMIT 10            
                      ";
            
            $datos = $DB->get_records_sql($query);
            $data_table=array();
            
            foreach($datos as $dato){
            $extra = array();
            
            $extra[]=$dato->academicperiodid;
            $extra[]=$dato->name;
            $extra[]=$dato->categoryid;
            $extra[]=$dato->campus;
            
            $data_table[] = $extra;
            
            }
            
            
			$synctable = new html_table();
			$synctable->head = array(
					get_string("acad_unid", "local_sync"),        //??
					get_string("academic_period", "local_sync"),  //??
					get_string("period_id","local_sync"),        //sync_data
					get_string("category","local_sync"),        //course_category
					get_string("category_id","local_sync"),    //sync_data  
					get_string("sede","local_sync"),          // sync_data
					get_string("Activation","local_sync"),    //herramientas
					get_string("manual_unsub","local_sync"), //herramientas
					get_string("edit","local_sync"),        //herramientas
							);
							
			$synctable->data = $data_table;					
			
		
				
			echo html_writer::table($synctable);

			
//fin de la pagina	
echo $OUTPUT->footer();