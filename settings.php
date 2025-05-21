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
 * Config the simultaneous report
 *
 * @package   report_simultaneous
 * @copyright 2023 Juan Pablo de Castro {@link https://www.uva.es}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/report/simultaneous/locallib.php');
// Setting for the maximum time of the analisys
$settings->add(new admin_setting_configtext('report_simultaneous/maxtime',
    get_string('maxtime', 'report_simultaneous'),
    get_string('maxtime_help', 'report_simultaneous'), 4, PARAM_INT));

// Setting for alowing free time window or only activity time window.
$settings->add(new admin_setting_configcheckbox('report_simultaneous/force_activity_time',
    get_string('force_activity_time', 'report_simultaneous'),
    get_string('force_activity_time_help', 'report_simultaneous'), 0));

// Setting for chose the indicators to show.
$ind = report_simultaneous_get_indicators(false);
$indhelp = [];
$inddef = [];
$indopts = [];
$indicators_help = '<ul>';
foreach ($ind as $key => $ind) {
    $indopts[$key] = get_string($ind->name . '_column', 'report_simultaneous');
    $inddef[$key] = true; // Default value for all indicators is 1 [checked
    $indicators_help = $indicators_help . "<li>". $indopts[$key] . ": ". get_string($ind->name . '_column_help', 'report_simultaneous') . "</li>";
}
$indicators_help = $indicators_help . '</ul>';
$settings->add(new admin_setting_configmulticheckbox('report_simultaneous/indicators',
    get_string('indicators', 'report_simultaneous'),
    $indicators_help,
    $inddef,
    $indopts));