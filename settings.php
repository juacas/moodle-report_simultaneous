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

// Setting for the maximum time of the analisys
$settings->add(new admin_setting_configtext('report_simultaneous/maxtime',
    get_string('maxtime', 'report_simultaneous'),
    get_string('maxtime_help', 'report_simultaneous'), 4, PARAM_INT));