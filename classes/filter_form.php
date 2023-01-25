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
 * Form to filter the simultaneous report
 *
 * @package   report_simultaneous
 * @copyright 2023 Juan Pablo de Castro {@link https://www.uva.es}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_simultaneous;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Class filter_form form to filter the results by date
 * @package report_simultaneous
 */
class filter_form extends \moodleform {
    private $course = null;
    /**
     * Form definition.
     * @throws \HTML_QuickForm_Error
     * @throws \coding_exception
     */
    protected function definition() {
        $mform = $this->_form;
        $course = $this->_customdata;
        $moduleoptions = report_simultaneous_get_modules_options($course);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        // Sort params.
        $mform->addElement('hidden', 'ssort');
        $mform->setType('ssort', PARAM_ALPHA);
        $mform->addElement('hidden', 'tdir');
        $mform->setType('tdir', PARAM_INT);

        $mform->addElement('header', 'filterheader', get_string('filter'));
        $opts = ['optional' => false];
        $mform->addElement('date_time_selector', 'startdate', get_string('from'), $opts);
        $mform->addElement('date_time_selector', 'enddate', get_string('to'), $opts);

        $mform->setExpanded('filterheader', true);
        // Add form elements to select the role and module.
        $modules = $mform->addElement('select', 'refmodules', get_string('refmodules', 'report_simultaneous'), $moduleoptions);
        $modules->setMultiple(true);
        // Checkbox to show all users.
        $mform->addElement('advcheckbox', 'showokusers', get_string('showokusers', 'report_simultaneous'));
        $mform->setDefault('showokusers', false);
        // Add the filter/cancel buttons (without 'closeHeaderBefore', so they collapse with the filter).
        $buttonarray = [
            $mform->createElement('submit', 'submitbutton', get_string('filter')),
            $mform->createElement('cancel'),
        ];
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
    }

}
