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
 * Lang strings
 *
 * @package    report
 * @subpackage simultaneous
 * @copyright  2023 Juan Pablo de Castro  <juan.pablo.de.castro@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['eventreportviewed'] = 'simultaneous usage report viewed';
$string['nologreaderenabled'] = 'No log reader enabled';
$string['simultaneous:view'] = 'View course simultaneous report';
$string['page-report-simultaneous-x'] = 'Any simultaneous report';
$string['page-report-simultaneous-index'] = 'Simultaneous usage report';
$string['pluginname'] = 'Simultaneous activities';
$string['refmodules'] = 'Reference activities';
$string['reportfor'] = 'Report of simultaneous activities for {$a}';
$string['reportfor_help'] = 'This report will show the users that have simultaneous activities with the selected activities. If you suspect that some users are cheating, you can check the logs of the selected activities to see if they are doing something wrong or ask support for a deeper analysis.';
$string['showokusers'] = 'Show users without simultaneous activities';
$string['simultaneousreport'] = 'Simultaneous activities';
$string['status_column'] = 'Status';
$string['status_column_help'] = 'The combination of the checkings for this user. If the user has simultaneous activities, the status will be "WARNING". If the user has no simultaneous activities, the status will be "OK".';
$string['incourse_column'] = 'In course';
$string['incourse_column_help'] = 'The number of views of activities for this user in the course. This number is the sum of the views of the activities other than the selected in the "Reference activities" field.';
$string['insite_column'] = 'In site';
$string['insite_column_help'] = 'The number of views of activities for this user in the site. This number is the sum of the views of the activities other than the selected in the "Reference activities" field.';
$string['messagesent_column'] = 'Messages sent';
$string['messagesent_column_help'] = 'The number of instant messages sent by this user. This includes messages sent to other users and messages sent to groups. This number includes messages sent to the user himself.';
$string['messageactions_column'] = 'Message actions';
$string['messageactions_column_help'] = 'The number of instant message actions performed by this user. This includes reading, deleting, etc. This number includes actions performed by the user on his own messages.';
$string['privacy:metadata'] = 'The Course simultaneous plugin does not store any personal data.';
