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
 * Simultaneous report administration recorrds view.
 *
 * @package    report
 * @subpackage simultaneous
 * @copyright  2023 Juan Pablo de Castro  <juan.pablo.de.castro@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot . '/report/simultaneous/locallib.php');

$id = required_param('id', PARAM_INT); // Course id.

if (!$course = $DB->get_record('course', array('id' => $id))) {
    throw new moodle_exception('invalidcourse');
}

require_login($course);
$context = context_course::instance($course->id);
require_capability('report/simultaneous:adminlisting', $context);

$v = required_param('v', PARAM_ALPHANUM);
$userid = required_param('userid', PARAM_INT);
$startdate = required_param('startdate', PARAM_INT);
$enddate = required_param('enddate', PARAM_INT);
$refmodules = optional_param_array('refmodules', [], PARAM_INT);

// Get enrolled users in course.
$users = get_enrolled_users($context, '', 0, 'u.*', null, 0, 0, false);

if (empty($refmodules)) {
    $userstoanalyse = array_keys($users);
} else {
    $userstoanalyse = report_simultaneous_get_users_with_activity($course, $refmodules, $startdate, $enddate);
}
// Export as csv the records.
$data = report_simultaneous_get_indicator($v, $course, $refmodules, $userstoanalyse, [$userid], $startdate, $enddate, false);
if (count($data) == 0) {
    echo "No data";
    exit;
}
// Dump data as a html table.
echo html_writer::start_tag('table', ['class' => 'generaltable', 'border' => '1']);
echo html_writer::start_tag('thead');
$first = $data[array_key_first($data)];
foreach ($first as $key=>$value) {
    echo html_writer::tag('th', $key);
}
echo html_writer::end_tag('thead');
echo html_writer::start_tag('tbody');
foreach ($data as $row) {
    echo html_writer::start_tag('tr');
    foreach ($row as $cell) {
        echo html_writer::tag('td', $cell);
    }
    echo html_writer::end_tag('tr');
}
echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');





