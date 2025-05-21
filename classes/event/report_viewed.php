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
 * The report_simultaneous report viewed event.
 *
 * @package    report_simultaneous
 * @copyright  2023 Juan Pablo de Castro <juan.pablo.de.castro@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_simultaneous\event;

/**
 * The report_simultaneous report viewed event class.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - int instanceid: Id of instance.
 *      - int roleid: Role id for whom report is viewed.
 *      - int groupid: (optional) group id.
 *      - int timefrom: (optional) time from which report is viewed.
 *      - string action: (optional) action viewed.
 * }
 *
 * @package    report_simultaneous
 * @since      Moodle 2.7
 * @copyright  2023 Juan Pablo de Castro <juan.pablo.de.castro@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_viewed extends \core\event\base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventreportviewed', 'report_simultaneous');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' viewed the course simultaneous report for the course with id '$this->courseid'.";
    }


    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/report/simultaneous/index.php',
             array('id' => $this->courseid,
                   'refmodules' => join(',', $this->other['refmodules']),
                   'startdate' => $this->other['startdate'],
                   'enddate' => $this->other['enddate'])
                );
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();
        if (empty($this->other['courseid'])) {
            throw new \coding_exception('The \'courseid\' value must be set in other.');
        }

        if (empty($this->other['startdate'])) {
            throw new \coding_exception('The \'startdate\' value must be set in other.');
        }

        if (!isset($this->other['enddate'])) {
            throw new \coding_exception('The \'enddate\' value must be set in other.');
        }

        if (!isset($this->other['refmodules'])) {
            throw new \coding_exception('The \'refmodules\' value must be set in other.');
        }
    }

    public static function get_other_mapping() {
        $othermapped = array();

        return $othermapped;

    }
}

