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
 * simultaneous report
 *
 * @package    report
 * @subpackage simultaneous
 * @copyright  2023 Juan Pablo de Castro  <juan.pablo.de.castro@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require('../../config.php');
require_once($CFG->dirroot . '/lib/tablelib.php');
require_once($CFG->dirroot . '/notes/lib.php');
require_once($CFG->dirroot . '/report/simultaneous/locallib.php');

define('DEFAULT_PAGE_SIZE', 20);
define('SHOW_ALL_PAGE_SIZE', 5000);

$id = required_param('id', PARAM_INT); // Course id.

$PAGE->set_pagelayout('admin');

if (!$course = $DB->get_record('course', array('id' => $id))) {
    throw new moodle_exception('invalidcourse');
}

require_login($course);
$context = context_course::instance($course->id);

$strsimultaneous = get_string('simultaneousreport', 'report_simultaneous');
$url = new moodle_url('/report/simultaneous/index.php', array('id' => $id));

$PAGE->set_url($url);
$PAGE->set_title(format_string($course->shortname, true, array('context' => $context)) . ': ' . $strsimultaneous);
$PAGE->set_heading(format_string($course->fullname, true, array('context' => $context)));
require_capability('report/simultaneous:view', $context);

// Release session lock.
\core\session\manager::write_close();

// Logs will not have been recorded before the course timecreated time.
$minlog = $course->timecreated;
$onlyuselegacyreader = false; // Use only legacy log table to aggregate records.

$logtable = report_simultaneous_get_log_table_name(); // Log table to use for fetaching records.

// If no log table, then use legacy records.
if (empty($logtable)) {
    $onlyuselegacyreader = true;
}

list($uselegacyreader, $useinternalreader, $minloginternalreader, $logtable) = report_simultaneous_get_common_log_variables();

// If no legacy and no internal log then don't proceed.
if (!$uselegacyreader && !$useinternalreader) {
    echo $OUTPUT->box_start('generalbox', 'notice');
    echo $OUTPUT->notification(get_string('nologreaderenabled', 'report_simultaneous'));
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    die();
}


// Handle form to filter access logs by date and activity.
$filterform = new \report_simultaneous\filter_form(null, $course);

if ($filterform->is_cancelled()) {
    $redir = $PAGE->url;
    $redir->remove_params(['startdate', 'enddate', 'refmodules']);
    redirect($redir);
}
$filter = $filterform->get_data();


if ($filter) {
    $refmodules = $filter->refmodules;
    $startdate = $filter->startdate;
    $enddate = $filter->enddate;
    $ssort = $filter->ssort;
    $tdir = $filter->tdir;
    $showokusers = $filter->showokusers;
} else {
    $refmodules = optional_param_array('refmodules', [], PARAM_INT);
    $startdate = optional_param('startdate', time() - 7200, PARAM_INT);
    $enddate = optional_param('enddate', time(), PARAM_INT);
    $ssort = optional_param('ssort', 'fullname', PARAM_ALPHANUMEXT);
    $tdir = optional_param('tdir', 4, PARAM_INT);
    $showokusers = optional_param('showokusers', 0, PARAM_BOOL);
}
// Complement the base url.
$url->param('startdate', $startdate);
$url->param('enddate', $enddate);
$url->param('ssort', $ssort);
$url->param('tdir', $tdir);
$url->param('showokusers', $showokusers);

foreach ($refmodules as $mod) {
    $url->param('refmodules[]', $mod);
}
// Set Defaults in the form.
$filterform->set_data([
    'refmodules' => $refmodules,
    'id' => $course->id,
    'startdate' => $startdate,
    'enddate' => $enddate,
    'showokusers' => $showokusers,
    'ssort' => $ssort,
    'tdir' => $tdir
]);
$PAGE->set_url($url);
$perpage = SHOW_ALL_PAGE_SIZE;

$modinfo = get_fast_modinfo($course);
// Check modules selected.
foreach ($refmodules as $modid) {
    if (!array_key_exists($modid, $modinfo->cms)) {
        throw new moodle_exception('invalidadtivity');
    }
}

// Trigger a report viewed event.
$event = \report_simultaneous\event\report_viewed::create(array(
    'context' => $context,
    'other' => array(
        'refmodules' => $refmodules,
        'startdate' => $startdate,
        'enddate' => $enddate,
        'courseid' => $course->id
    )
));

$event->trigger();

// Get enrolled users in course.
$users = get_enrolled_users($context, '', 0, 'u.*', null, 0, 0, false);
// Get users with activity in selected modules.
// If no refmodules check all enrolled users.
if (empty($refmodules)) {
    $userstolist = array_keys($users);
} else {
    $userstolist = report_simultaneous_get_users_with_activity($course, $refmodules, $startdate, $enddate);
}


$download = optional_param('download', '', PARAM_ALPHA);


// Define the table.
$statusstr = get_string('status_column', 'report_simultaneous');
$incoursestr = get_string('incourse_column', 'report_simultaneous');
$insitestr = get_string('insite_column', 'report_simultaneous');
$messagesentstr = get_string('messagesent_column', 'report_simultaneous');
$messageactionsstr = get_string('messageactions_column', 'report_simultaneous');
$ipsstr = get_string('ips_column', 'report_simultaneous');

$headers = array("", $statusstr, get_string('user'), $incoursestr, $insitestr, $messagesentstr, $messageactionsstr, $ipsstr);
$headershelp = [null,
                new \help_icon('status_column', 'report_simultaneous'),
                null,
                new \help_icon('incourse_column', 'report_simultaneous'),
                new \help_icon('insite_column', 'report_simultaneous'),
                new \help_icon('messagesent_column', 'report_simultaneous'),
                new \help_icon('messageactions_column', 'report_simultaneous'),
                new \help_icon('ips_column', 'report_simultaneous')];
$columns = array('photo', 'warning', 'fullname', 'V1', 'V2', 'V3', 'V4', 'V5');
$table = report_simultaneous_create_table($url, $course, $columns, $headers, $headershelp, $download);

// Start report page.
if (!$table->is_downloading()) {
    // Print the selector dropdown.
    $pluginname = get_string('pluginname', 'report_simultaneous');
    echo $OUTPUT->header();
    // If class report_helper exists, save the selected report.
    if (class_exists('core\report_helper')) { // Moodle >= 3.10
        core\report_helper::save_selected_report($id, $url);
        core\report_helper::print_report_selector($pluginname);
    }
    // Print heading with help icon.
    echo $OUTPUT->heading_with_help(get_string('reportfor', 'report_simultaneous', format_text($course->fullname)),
                                               'reportfor', 'report_simultaneous');
    // Print the filter form.
    $filterform->display();
}

// Generate the report data.
$checkboxes = empty($CFG->messaging);
$htmlouput = $table->is_downloading() == 'html' || $table->is_downloading() == false;

$dataset = report_simultaneous_get_data($course, $table, $refmodules, $users, $userstolist, $startdate, $enddate, $showokusers, $checkboxes, $htmlouput);
// Sort dataset by field $fieldname.
if ($ssort) {
    $columarr = array_column($dataset, $ssort);
    if (count($columarr)) {
        array_multisort(array_column($dataset, $ssort), $tdir == 4 ? SORT_ASC : SORT_DESC, $dataset);
    }
}

if (!$table->is_downloading()) {
    echo '<div id="simultaneousreport">' . "\n";
}
// Add data to table and output it.
foreach ($dataset as $data) {
    $table->add_data_keyed($data);
}
$table->finish_output();

if (!$table->is_downloading()) {
    if (!empty($CFG->messaging)) {
        echo '<div>' . "\n";
        // Bulk action form.
        echo '<form action="' . $CFG->wwwroot . '/user/action_redir.php" method="post" id="buform">' . "\n";
        echo '<input type="hidden" name="id" value="' . $id . '" />' . "\n";
        echo '<input type="hidden" name="returnto" value="' . s($PAGE->url) . '" />' . "\n";
        echo '<input type="hidden" name="sesskey" value="' . sesskey() . '" />' . "\n";
        echo '</div>' . "\n";

        echo '<div class="py-3">';
        echo html_writer::label(get_string('withselectedusers'), 'formactionid');
        $displaylist['#messageselect'] = get_string('messageselectadd');
        $withselectedparams = array(
            'id' => 'formactionid',
            'data-action' => 'toggle',
            'data-togglegroup' => 'simultaneous-table',
            'data-toggle' => 'action',
            'disabled' => true
        );
        echo html_writer::select($displaylist, 'formaction', '', array('' => 'choosedots'), $withselectedparams);
        echo '</div>' . "\n";
        echo '</form>' . "\n";

        $options = new stdClass();
        $options->courseid = $course->id;
        $options->noteStateNames = note_get_state_names();
        $options->stateHelpIcon = $OUTPUT->help_icon('publishstate', 'notes');
        $PAGE->requires->js_call_amd('report_simultaneous/simultaneous', 'init', [$options]);
    }
    echo '</div>' . "\n";

    echo $OUTPUT->footer();
}
