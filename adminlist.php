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
 * @package    report_simultaneous
 * @subpackage simultaneous
 * @copyright  2023 Juan Pablo de Castro  <juan.pablo.de.castro@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot . '/report/simultaneous/locallib.php');
require_once($CFG->dirroot . '/lib/tablelib.php');

$id = required_param('id', PARAM_INT); // Course id.

if (!$course = $DB->get_record('course', ['id' => $id])) {
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
$filtertext = optional_param('filtertext', '', PARAM_TEXT);
$download = optional_param('download', '', PARAM_ALPHA);

$baseparams = [
    'id' => $id,
    'v' => $v,
    'userid' => $userid,
    'startdate' => $startdate,
    'enddate' => $enddate,
];

$url = new moodle_url('/report/simultaneous/adminlist.php', $baseparams);
if (!empty($filtertext)) {
    $url->param('filtertext', $filtertext);
}
foreach ($refmodules as $mod) {
    $url->param('refmodules[]', $mod);
}

$PAGE->set_pagelayout('report');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('adminlisttitle', 'report_simultaneous'));
$PAGE->set_heading(format_string($course->fullname, true, ['context' => $context]));

// Get enrolled users in course.
$users = get_enrolled_users($context, '', 0, 'u.*', null, 0, 0, false);

if (empty($refmodules)) {
    $userstoanalyse = array_keys($users);
} else {
    $userstoanalyse = report_simultaneous_get_users_with_activity($course, $refmodules, $startdate, $enddate);
}
// Export as csv the records.
$data = report_simultaneous_get_indicator($v, $course, $refmodules, $userstoanalyse, [$userid], $startdate, $enddate, false);

if (!empty($filtertext)) {
    $needle = core_text::strtolower($filtertext);
    foreach ($data as $index => $row) {
        $rowmatch = false;
        foreach ((array)$row as $value) {
            $cellvalue = core_text::strtolower((string)$value);
            if (strpos($cellvalue, $needle) !== false) {
                $rowmatch = true;
                break;
            }
        }
        if (!$rowmatch) {
            unset($data[$index]);
        }
    }
}

if (!empty($download)) {
    if (!empty($data)) {
        $first = reset($data);
        $columns = array_keys((array)$first);
        $headers = $columns;
        $rows = [];
        foreach ($data as $row) {
            $record = [];
            foreach ($columns as $column) {
                $record[] = (string)($row->{$column} ?? '');
            }
            $rows[] = $record;
        }
    } else {
        $rows = [[get_string('nothingtodisplay')]];
        $headers = ['results'];
    }

    \core\dataformat::download_data(
        'simultaneous-adminlist-' . $course->id . '-' . core_text::strtolower($v),
        $download,
        $headers,
        $rows
    );
    die();
}

$table = new flexible_table('report-simultaneous-adminlist-' . $course->id . '-' . $v . '-' . $userid);
$table->is_downloading(
    $download,
    'simultaneous-adminlist-' . $course->id . '-' . core_text::strtolower($v),
    'simultaneous-adminlist'
);
$table->show_download_buttons_at([TABLE_P_TOP, TABLE_P_BOTTOM]);
$table->define_baseurl($url);
$table->set_attribute('class', 'generaltable generalbox reporttable');
$table->sortable(false);

if (!empty($data)) {
    $first = reset($data);
    $columns = array_keys((array)$first);
    $headers = $columns;
} else {
    $columns = ['empty'];
    $headers = ['results'];
}

$table->define_columns($columns);
$table->define_headers($headers);
$table->setup();

if (!$table->is_downloading()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('adminlisttitle', 'report_simultaneous'));

    $filterformurl = new moodle_url('/report/simultaneous/adminlist.php', $baseparams);
    echo html_writer::start_tag('form', ['method' => 'get', 'action' => $filterformurl->out(false), 'class' => 'mb-3']);
    foreach ($baseparams as $param => $value) {
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $param, 'value' => $value]);
    }
    foreach ($refmodules as $mod) {
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'refmodules[]', 'value' => $mod]);
    }

    echo html_writer::label(get_string('filtertext', 'report_simultaneous'), 'id_filtertext', false, ['class' => 'mr-2']);
    echo html_writer::empty_tag('input', [
        'type' => 'text',
        'name' => 'filtertext',
        'id' => 'id_filtertext',
        'value' => $filtertext,
        'class' => 'mr-2',
    ]);
    echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('filterresults', 'report_simultaneous')]);

    $clearurl = new moodle_url('/report/simultaneous/adminlist.php', $baseparams);
    foreach ($refmodules as $mod) {
        $clearurl->param('refmodules[]', $mod);
    }
    echo html_writer::link($clearurl, get_string('clearfilterresults', 'report_simultaneous'), ['class' => 'ml-2']);
    echo html_writer::end_tag('form');
}

if (!empty($data)) {
    foreach ($data as $row) {
        $table->add_data(array_values((array)$row));
    }
} else {
    $table->add_data([get_string('nothingtodisplay')]);
}

$table->finish_output();

if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}
