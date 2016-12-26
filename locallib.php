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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
/**
 *
 * @package mod
 * @subpackage sync
 * @copyright 2016 joaquin rivano <jrivano@alumnos.uai.cl>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
global $CFG;

/**
* Navigation tabs for syncronization create and record page 
*/

function sync_tabs() {
    $tabs = array();
    // create sync
    $tabs[] = new tabobject("create", new moodle_url("/local/sync/create.php", array(
        "status" => 1
    )), get_string("create", 'local_sync'));
    //  history.
    $tabs[] = new tabobject("record", new moodle_url("/local/sync/record.php", array(
        "status" => 2
    )), get_string("record", 'local_sync'));
    
     return $tabs;
}