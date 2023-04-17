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


if (!$course = $DB->get_record('course', array('id' => $id))) {
    throw new moodle_exception('invalidcourse');
}

require_login($course);
$context = context_course::instance($course->id);
require_capability('report/simultaneous:view', $context);

$strsimultaneous = get_string('simultaneousreport', 'report_simultaneous');
$url = new moodle_url('/report/simultaneous/index.php', array('id' => $id));

$PAGE->set_pagelayout('report');
$PAGE->set_url($url);
$PAGE->set_title(format_string($course->shortname, true, array('context' => $context)) . ': ' . $strsimultaneous);
$PAGE->set_heading(format_string($course->fullname, true, array('context' => $context)));

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

$downloading = optional_param('download', '', PARAM_ALPHA);

// Start report page.
if (!$downloading) {
    // Print the selector dropdown.
    $pluginname = get_string('pluginname', 'report_simultaneous');
    echo $OUTPUT->header();
    // If class report_helper exists, save the selected report.
    if ($CFG->branch < 4) { // Deprecated Moodle < 4
        core\report_helper::save_selected_report($id, $url);
    }
    core\report_helper::print_report_selector($pluginname);
    // Print heading with help icon.
    echo $OUTPUT->heading_with_help(
        get_string('reportfor', 'report_simultaneous', format_text($course->fullname)),
        'reportfor',
        'report_simultaneous'
    );
}

// Handle form to filter access logs by date and activity.
$filterform = new \report_simultaneous\filter_form(null, $course, "get");

// Form processing and displaying is done here.
if ($filterform->is_cancelled()) {
    $redir = $PAGE->url;
    $redir->remove_params(['startdate', 'enddate', 'refmodules']);
    redirect($redir);
} else if ($filter = $filterform->get_data()) {
    if (!$downloading) {
        // When the form is submitted, and the data is successfully validated
        $filterform->display();
    }

    $refmodules = $filter->refmodules;
    $startdate = $filter->startdate;
    $enddate = $filter->enddate;
    $ssort = $filter->ssort;
    $tdir = $filter->tdir;
    $showokusers = $filter->showokusers;

    // Complement the base url.
    if (is_int($enddate)) {
        // Extract date parts.
        $enddateobj = new DateTime();
        $enddateobj->setTimestamp($enddate);
        $url->param('enddate[month]', (int) $enddateobj->format('m'));
        $url->param('enddate[day]', (int) $enddateobj->format('d'));
        $url->param('enddate[year]', (int) $enddateobj->format('Y'));
        $url->param('enddate[hour]', (int) $enddateobj->format('H'));
        $url->param('enddate[minute]', (int) $enddateobj->format('i'));
    }
    if (is_int($startdate)) {
        // Extract date parts.
        $startdateobj = new DateTime();
        $startdateobj->setTimestamp($startdate);
        $url->param('startdate[month]', (int) $startdateobj->format('m'));
        $url->param('startdate[day]', (int) $startdateobj->format('d'));
        $url->param('startdate[year]', (int) $startdateobj->format('Y'));
        $url->param('startdate[hour]', (int) $startdateobj->format('H'));
        $url->param('startdate[minute]', (int) $startdateobj->format('i'));
    }
    $url->param('ssort', $ssort);
    $url->param('tdir', $tdir);
    $url->param('showokusers', $showokusers);
    // Add sesskey.
    $url->param('sesskey', sesskey());
    $url->param('_qf__report_simultaneous_filter_form', 1);

    foreach ($refmodules as $mod) {
        $url->param('refmodules[]', $mod);
    }
    $PAGE->set_url($url);

    $table = report_simultaneous_define_table($url, $course, $downloading);
    $perpage = SHOW_ALL_PAGE_SIZE;

    $modinfo = get_fast_modinfo($course);
    // Check modules selected.
    foreach ($refmodules as $modid) {
        if (!array_key_exists($modid, $modinfo->cms)) {
            throw new moodle_exception('invalidadtivity');
        }
    }

    // Trigger a report viewed event.
    $event = \report_simultaneous\event\report_viewed::create(
        array(
            'context' => $context,
            'other' => array(
                'refmodules' => $refmodules,
                'startdate' => $startdate,
                'enddate' => $enddate,
                'courseid' => $course->id
            )
        )
    );

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

    $checkboxes = empty($CFG->messaging);
    $htmlouput = $table->is_downloading() == 'html' || $table->is_downloading() == false;

    // Generate the report data.
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
    }

} else {
    // This branch is executed if the form is submitted but the data doesn't
    // validate and the form should be redisplayed or on the first display of the form.

    // Set anydefault data (if any).
    //$filterform->set_data($toform);

    // Display the form.
    $filterform->display();
}

echo $OUTPUT->footer();