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
* @copyright  2016 Hans Jeria (hansjeria@gmail.com) 					
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
defined('MOODLE_INTERNAL') || die;
if ($hassiteconfig) {

	$settings = new admin_settingpage('local_sync', 'Sync Omega');

	$ADMIN->add('localplugins', $settings);
	$settings->add(
			new admin_setting_configtext(
				"sync_token",
				get_string("token", "local_sync"),
				get_string("tokendesc", "local_sync"),
				"",
				PARAM_ALPHANUM
	));	
	$settings->add(
			new admin_setting_configtext(
				"sync_urlgetalumnos",
				get_string("urlgetalumnos", "local_sync"),
				get_string("urlgetalumnosdesc", "local_sync"),
				"",
				PARAM_URL
	));
	
	$settings->add(
			new admin_setting_configtext(
				"sync_urlgetcursos",
				get_string("urlgetcursos", "local_sync"),
				get_string("urlgetcursosdesc", "local_sync"),
				"",
				PARAM_URL
	));	
	$settings->add(
			new admin_setting_configtext(
				"sync_urlgetacademicperiods",
				get_string("urlgetacademicperiods", "local_sync"),
				get_string("urlgetacademicperiodsdesc", "local_sync"),
				"",
				PARAM_URL
	));
	$settings->add(
			new admin_setting_configcheckbox(
					"sync_emailexplode",
					get_string("emailexplode", "local_sync"),
					get_string("emailexplodedes", "local_sync"),
					0,
					PARAM_BOOL
	));
	$settings->add(
			new admin_setting_configtext(
				"sync_execcommand",
				get_string("urlexeccommand", "local_sync"),
				get_string("urlexeccommanddesc", "local_sync"),
				NULL,
				PARAM_TEXT
	));
}