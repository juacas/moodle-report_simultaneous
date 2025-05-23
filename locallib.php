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
 * This file contains functions used by the simultaneous reports
 *
 * @package   report_simultaneous
 * @copyright 2023 Juan Pablo de Castro <juan.pablo.de.castro@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Returns log table name of preferred reader, if leagcy then return empty string.
 *
 * @return string table name
 */
function report_simultaneous_get_log_table_name() {
    // Get prefered sql_internal_table_reader reader (if enabled).
    $logmanager = get_log_manager();
    $readers = $logmanager->get_readers();
    $logtable = '';

    // Get preferred reader.
    if (!empty($readers)) {
        foreach ($readers as $readerpluginname => $reader) {
            // If legacy reader is preferred reader.
            if ($readerpluginname == 'logstore_legacy') {
                break;
            }

            // If sql_internal_table_reader is preferred reader.
            if ($reader instanceof \core\log\sql_internal_table_reader) {
                $logtable = $reader->get_internal_log_table_name();
                break;
            }
        }
    }
    return $logtable;
}


/**
 * Get modules..
 *
 * @param stdClass $course course object.
 */
function report_simultaneous_get_modules_options($course) {
    global $DB;

    $modinfo = get_fast_modinfo($course);

    $instances = array();
    foreach ($modinfo->cms as $cm) {
        // Skip modules such as label which do not actually have links;
        // this means there's nothing to participate in.
        if (!$cm->has_view()) {
            continue;
        }
        $instances[$cm->id] = format_string($cm->name);
    }
    return $instances;
}

/**
 * Returns an array of the commonly used log variables by the simultaneous report.
 *
 * @return array the array of variables used
 */
function report_simultaneous_get_common_log_variables() {
    global $DB;

    static $uselegacyreader;
    static $useinternalreader;
    static $minloginternalreader;
    static $logtable = null;

    if (isset($uselegacyreader) && isset($useinternalreader) && isset($minloginternalreader)) {
        return array($uselegacyreader, $useinternalreader, $minloginternalreader, $logtable);
    }

    $uselegacyreader = false; // Flag to determine if we should use the legacy reader.
    $useinternalreader = false; // Flag to determine if we should use the internal reader.
    $minloginternalreader = 0; // Set this to 0 for now.

    // Get list of readers.
    $logmanager = get_log_manager();
    $readers = $logmanager->get_readers();

    // Get preferred reader.
    if (!empty($readers)) {
        foreach ($readers as $readerpluginname => $reader) {
            // If legacy reader is preferred reader.
            if ($readerpluginname == 'logstore_legacy') {
                $uselegacyreader = true;
            }

            // If sql_internal_table_reader is preferred reader.
            if ($reader instanceof \core\log\sql_internal_table_reader) {
                $useinternalreader = true;
                $logtable = $reader->get_internal_log_table_name();
                $minloginternalreader = $DB->get_field_sql('SELECT min(timecreated) FROM {' . $logtable . '}');
            }
        }
    }

    return array($uselegacyreader, $useinternalreader, $minloginternalreader, $logtable);
}
/**
 * Get the sql for selecting the list of coursemodules.
 * @param array $mods
 * @param boolean $equal whether to negate the query
 * @return array
 */
function report_simultaneous_get_mod_sql($mods, $equal = false) {
    global $DB;
    if (count($mods) > 0) {
        list($modinsql, $modparams) = $DB->get_in_or_equal($mods, SQL_PARAMS_NAMED, 'safemodules', $equal);
    } else {
        $modinsql = "IS NOT NULL";
        $modparams = [];
    }
    return [$modinsql, $modparams];
}
/**
 * Returns a list of users who have viewed any safemodules in a time windwow.
 */
function report_simultaneous_get_users_with_activity($course, $safemodules, $startdate, $enddate) {
    global $DB;

    list($uselegacyreader, $useinternalreader, $minloginternalreader, $logtable) = report_simultaneous_get_common_log_variables();

    $params = array();
    $params['courseid'] = $course->id;
    $params['startdate'] = $startdate;
    $params['enddate'] = $enddate;
    // Params for query the users that visitted any module in the list.
    list($sqlin, $inparams) = report_simultaneous_get_mod_sql($safemodules, true);
    $params = array_merge($params, $inparams);
    $limittime = '';
    if ($startdate) {
        $limittime .= ' AND timecreated >= :startdate ';
        $params['startdate'] = $startdate;
    }
    if ($enddate) {
        $limittime .= ' AND timecreated < :enddate ';
        $params['enddate'] = $enddate;
    }
    $sql = "SELECT DISTINCT userid
              FROM {" . $logtable . "}
             WHERE contextinstanceid $sqlin
             $limittime";

    $users = $DB->get_records_sql($sql, $params);

    return array_keys($users);
}
/**
 * Returns a list of users that have events with more than an ip.
 */
function report_simultaneous_get_users_with_multiple_ips($userswithactivity, $startdate, $enddate, $aggregated = true) {
    global $DB;

    if (count($userswithactivity) == 0) {
        return array();
    }

    list($uselegacyreader, $useinternalreader, $minloginternalreader, $logtable) = report_simultaneous_get_common_log_variables();

    $params = array();
    $limittime = '';
    if ($startdate) {
        $limittime .= ' AND timecreated >= :startdate ';
        $params['startdate'] = $startdate;
    }
    if ($enddate) {
        $limittime .= ' AND timecreated < :enddate ';
        $params['enddate'] = $enddate;
    }
    // Param for user query.
    list($usersinsql, $inparams) = $DB->get_in_or_equal($userswithactivity, SQL_PARAMS_NAMED, 'users', true);
    $params = array_merge($params, $inparams);
    $select = $aggregated ? "DISTINCT userid, COUNT(DISTINCT ip) as count": "DISTINCT(ip)";
    $aggregation = $aggregated ? "GROUP BY userid
            HAVING COUNT(DISTINCT ip) > 1" : "";
    $sql = "SELECT $select
            FROM {" . $logtable . "}
            WHERE userid $usersinsql
             $limittime
            $aggregation";

    $users = $DB->get_records_sql($sql, $params);
    return $users;
}
/**
 * Returns a list of users who have viewed any module other than safemodules in a time window.
 * @param stdClass $course course object. If null, then all courses are considered.
 */
function report_simultaneous_get_users_with_activity_other($course, $safemodules, $userswithactivity, $startdate, $enddate, $aggregated = true) {
    global $DB;
    if (count($userswithactivity) == 0) {
        return array();
    }
    list($uselegacyreader, $useinternalreader, $minloginternalreader, $logtable) = report_simultaneous_get_common_log_variables();
    $params = array();
    // Param for safemodules query.
    list($safemodulesinsql, $inparamssafemodules) = report_simultaneous_get_mod_sql($safemodules);
    // Param for user query.
    list($usersinsql, $inparams) = $DB->get_in_or_equal($userswithactivity, SQL_PARAMS_NAMED, 'users', true);
    $params = array_merge($params, $inparamssafemodules, $inparams);

    $incourse = '';
    if ($course != null) {
        $params['courseid'] = $course->id;
        $incourse = 'AND courseid = :courseid ';
    }
    $limittime = '';
    if ($startdate) {
        $limittime .= ' AND timecreated >= :startdate ';
        $params['startdate'] = $startdate;
    }
    if ($enddate) {
        $limittime .= ' AND timecreated < :enddate ';
        $params['enddate'] = $enddate;
    }
    $select = $aggregated ? "userid, COUNT('x') AS count" : '*';
    $aggregation = $aggregated ? "GROUP BY userid" : '';
    $sql = "SELECT $select FROM {" . $logtable . "} l
            WHERE anonymous = 0
               $incourse
               AND crud = 'r'
               AND contextinstanceid $safemodulesinsql
               AND userid $usersinsql
               AND component <> 'core'
               AND target <> 'report'
               $limittime
            $aggregation";
    $v1 = $DB->get_records_sql($sql, $params);
    return $v1;
}
/**
 * Returns a list of users who had messaging activity in a time window.
 * Does not use message_user_actions to exclude deleted messages.
 */
function report_simultaneous_get_users_with_messaging($course, $userswithactivity, $startdate, $enddate, $aggregated = true) {
    global $DB;
    if (count($userswithactivity) == 0) {
        return array();
    }
    $params = array('courseid' => $course->id);
    // Param for users query.
    list($usersin, $inparams) = $DB->get_in_or_equal($userswithactivity, SQL_PARAMS_NAMED, 'users', true);
    $params = array_merge($params, $inparams);
    
    $limittime = '';
    if ($startdate) {
        $limittime .= ' AND timecreated >= :startdate ';
        $params['startdate'] = $startdate;
    }
    if ($enddate) {
        $limittime .= ' AND timecreated < :enddate ';
        $params['enddate'] = $enddate;
    }
    $logtable = 'messages';
    $select = $aggregated ? "useridfrom as userid, COUNT('x') AS count" : '*';
    $aggregation = $aggregated ? 'GROUP BY userid' : '';
    $sql = "SELECT $select FROM {" . $logtable . "} l
            WHERE useridfrom $usersin
            $limittime
            $aggregation";
    $res = $DB->get_records_sql($sql, $params);
    return $res;
}
/**
 * Returns a list of users who had read, deleted or sent messages in a time window.
 */
function report_simultaneous_get_users_with_message_actions($course, $userswithactivity, $startdate, $enddate, $aggregated = true) {
    global $DB;
    if (count($userswithactivity) == 0) {
        return array();
    }
    $logtable = 'message_user_actions';
    $params = array('courseid' => $course->id);
    // Param for users' query.
    list($usersin, $inparams) = $DB->get_in_or_equal($userswithactivity, SQL_PARAMS_NAMED, 'users', true);
    $params = array_merge($params, $inparams);

    $limittime = '';
    if ($startdate) {
        $limittime .= ' AND timecreated >= :startdate ';
        $params['startdate'] = $startdate;
    }
    if ($enddate) {
        $limittime .= ' AND timecreated < :enddate ';
        $params['enddate'] = $enddate;
    }
    $select = $aggregated ? "userid as userid, COUNT('x') AS count" : '*';
    $aggregation = $aggregated ? 'GROUP BY userid' : '';
    $sql = "SELECT $select FROM {" . $logtable . "} l
            WHERE userid $usersin
            $limittime
            $aggregation";
    $res = $DB->get_records_sql($sql, $params);
    return $res;
}
/**
 * Returns a list of messages sent messages to conversations that include target users, in a time window.
 */
function report_simultaneous_get_users_sent_message_to_conversations($course, $userswithactivity, $startdate, $enddate, $aggregated = true) {
    global $DB;
    if (count($userswithactivity) == 0) {
        return array();
    }
    
    $params = array('courseid' => $course->id);
    // Param for users' query.
    list($usersin, $inparams) = $DB->get_in_or_equal($userswithactivity, SQL_PARAMS_NAMED, 'users', true);
    $params = array_merge($params, $inparams);

    $limittime = '';
    if ($startdate) {
        $limittime .= ' AND m.timecreated >= :startdate ';
        $params['startdate'] = $startdate;
    }
    if ($enddate) {
        $limittime .= ' AND m.timecreated < :enddate ';
        $params['enddate'] = $enddate;
    }
    $select = $aggregated ? "useridfrom as userid, COUNT('x') AS count" : '*';
    $aggregation = $aggregated ? "GROUP BY m.useridfrom" : "";
    $sql = "SELECT $select
            FROM {messages} m
            LEFT JOIN {message_conversation_members} c ON m.conversationid = c.conversationid AND  m.useridfrom <> c.userid
            WHERE c.userid $usersin
            $limittime
            $aggregation";
    $res = $DB->get_records_sql($sql, $params);
    return $res;
}
function report_simultaneous_define_table($url, $course, $download) {
    // Define the table.
    $statusstr = get_string('status_column', 'report_simultaneous');

    $headers = array("", $statusstr, get_string('user'));
    $indicators = report_simultaneous_get_indicators();
    $headershelp = [null,
                    new \help_icon('status_column', 'report_simultaneous'),
                    null];
    $columns = array('photo', 'warning', 'fullname');//, 'V1', 'V2', 'V3', 'V4', 'V5', 'V6');
    // Add help:icons for each indicator.
    foreach ($indicators as $key=>$indicator) {
        $headershelp[] = new \help_icon($indicator->name . '_column', 'report_simultaneous');
        $headers[] = get_string($indicator->name . '_column', 'report_simultaneous');
        $columns[] = $key;
    }
    
    $table = report_simultaneous_create_table($url, $course, $columns, $headers, $headershelp, $download);
    return $table;
}
function report_simultaneous_create_table($url, $course, $columns, $headers, $headershelp, $download) {
    global $OUTPUT, $CFG;
    $table = new flexible_table('course-simultaneous-' . $course->id);
    $table->is_downloading($download, 'simultaneous', 'simultaneous');
    if (!empty($CFG->messaging) && $table->is_downloading() == false) {
        $columns[] = 'select';
        $mastercheckbox = new \core\output\checkbox_toggleall('simultaneous-table', true, [
            'id' => 'select-all-simultaneous',
            'name' => 'select-all-simultaneous',
            'label' => get_string('select'),
            // Consistent labels to prevent select column from resizing.
            'selectall' => get_string('select'),
            'deselectall' => get_string('select'),
        ]);
        $headers[] = $OUTPUT->render($mastercheckbox);
    }
    $table->show_download_buttons_at(array(TABLE_P_BOTTOM));
    $table->define_columns($columns);
    $table->define_headers($headers);
    $table->define_help_for_headers($headershelp);
    $table->define_baseurl($url);

    $table->set_attribute('class', 'generaltable generalbox reporttable');

    $table->sortable(true, 'lastname', 'ASC');
    $table->no_sorting('select');
    $table->no_sorting('photo');
    $table->pageable(false);

    $table->set_control_variables(array(
        TABLE_VAR_SORT    => 'ssort',
        TABLE_VAR_HIDE    => 'shide',
        TABLE_VAR_SHOW    => 'sshow',
        TABLE_VAR_IFIRST  => 'sifirst',
        TABLE_VAR_ILAST   => 'silast',
        TABLE_VAR_PAGE    => 'spage'
    ));
    $table->setup();
    return $table;
}
/**
 * Get the data for a given indicator.
 * V1 Get users with activity in other modules in this course.
 * V2 Get users with activity in other modules in any course.
 * V3 Get users with messaging activities.
 * V4 Get users with message actions.
 * V5 Get users with multiple ip addresses.
 * V6 Get users that sent messages to conversations that include target users.
 * @param string $v   name of the indicator
 * @param stdClass $course course object
 * @param array $refmodules array of module cmid
 * @param array $userstoanalyse array of userids
 * @param int $startdate timestamp
 * @param int $enddate timestamp
 * @param bool $aggregated true if aggregated results are requested.
 * @return array of data for the indicator
 */
function report_simultaneous_get_indicator($v, $course, $refmodules, $userstoanalyse, $userstolist, $startdate, $enddate, $aggregated = true) {
    switch($v) {
        case 'V1':
            // Get users with activity in other modules in this course.
            $data = report_simultaneous_get_users_with_activity_other($course, $refmodules, $userstoanalyse, $startdate, $enddate, $aggregated);
            break;
        case 'V2':
            // Get users with activity in other modules in any course.
            $data = report_simultaneous_get_users_with_activity_other(null, $refmodules, $userstoanalyse, $startdate, $enddate, $aggregated);
            break;
        case 'V3':
            // Get users with messaging activities.
            $data = report_simultaneous_get_users_with_messaging($course, $userstoanalyse, $startdate, $enddate, $aggregated);
            break;
        case 'V4':
            // Get users with message actions.
            $data = report_simultaneous_get_users_with_message_actions($course, $userstoanalyse, $startdate, $enddate, $aggregated);
            break;
        case 'V5':
            // Get users with multiple ip addresses.
            $data = report_simultaneous_get_users_with_multiple_ips($userstoanalyse, $startdate, $enddate, $aggregated);
            break;
        case 'V6':
            // Get users that sent messages to conversations that include target users.
            $data = report_simultaneous_get_users_sent_message_to_conversations($course, $userstoanalyse, $startdate, $enddate, $aggregated);
            break;
    }
    return $data;
}
/**
 * Return an array with the available indicators.
 * @return array key => string
 */
function report_simultaneous_get_indicators($onlyenabled = true) {
    $config = get_config('report_simultaneous', 'indicators');
    // Split config into keys.
    $keys = explode(',', $config);
    
    $indicators = array(
        'V1' => (object)[ 'name'=> 'incourse' ],
        'V2' => (object)[ 'name'=> 'insite'],
        'V3' => (object)[ 'name'=> 'messagesent'],
        'V4' => (object)[ 'name'=> 'messageactions'],
        'V5' => (object)[ 'name'=> 'ips'],
        'V6' => (object)[ 'name'=> 'messagesentconversation'],
    );
    if ($onlyenabled) {
        $indicators = array_intersect_key($indicators, array_combine($keys, $keys));
    }
    return $indicators;
}
/**
 * Returns a list of users who had activity in other modules in a time window.
 */
function report_simultaneous_get_data($course, $table, $refmodules, $users, $userstolist, $startdate, $enddate, $showokusers, $checkboxes = true, $htmlouput = true) {
    global $OUTPUT;
    $indicators = array_keys(report_simultaneous_get_indicators());
    $ind = [];
    // Collect the data for each indicator.
    foreach ($indicators as $v) {
        $ind[$v] = report_simultaneous_get_indicator($v, $course, $refmodules, $userstolist, $userstolist, $startdate, $enddate);
    }
    
    // Create the listing with all the indicators for each user. Fill the gaps with 0..
    $dataset = array();
    foreach ($users as $u) {
        // Search userid in indicators.
        $hasdata = false;
        $warning = false;
        foreach ($indicators as $v) {
            $userind = $ind[$v];
            if (isset($userind[$u->id])) {
                $data[$v] = (int)$ind[$v][$u->id]->count;
                $hasdata = true;
            } else {
                $data[$v] = 0;
            }
            if ($data[$v] > 0) {
                $warning = true;
            }
        }

        if ($showokusers || $hasdata) {
            // Add user photo.
            if ( $htmlouput ) {
                $data['photo'] = $OUTPUT->user_picture($u, array('courseid' => $course->id));
                $data['fullname'] = html_writer::link(
                    new moodle_url('/user/view.php', array('id' => $u->id, 'course' => $course->id)),
                    fullname($u, true)
                );
            } else {
                $data['photo'] = "";
                $data['fullname'] = fullname($u, true);
            }
           

            // If has capabilitiy simultaneous:adminlisting add links.
            if (has_capability('report/simultaneous:adminlisting', context_course::instance($course->id))) {
                foreach ($indicators as $key) {
                    if ($data[$key] == 0) {
                        continue;
                    }
                    $url = new moodle_url('/report/simultaneous/adminlist.php', array('id' => $course->id, 
                                            'userid' => $u->id,
                                            'v' => $key,
                                            'startdate' => $startdate,
                                            'enddate' => $enddate));
                    foreach ($refmodules as $mod) {
                        $url->param('refmodules[]', $mod);
                    }
                    $data[$key] = html_writer::link($url, $data[$key]);
                }
            }
            if ($htmlouput) {
                // Output a icon of a moodle warning or OK sign.
                if ($warning) {
                    $data['warning'] = $OUTPUT->pix_icon('i/warning', get_string('warning')) . get_string('warning');
                } else {
                    $data['warning'] = $OUTPUT->pix_icon('i/valid', get_string('ok')) . get_string('ok');
                }
            } else {
                $data['warning'] = $warning ? get_string('warning') : get_string('ok');
            }

            if (!$checkboxes && $table->is_downloading() == false) {
                $togglegroup = 'simultaneous-table';
                if (empty($u->count)) {
                    $togglegroup .= ' no';
                }
                $checkbox = new \core\output\checkbox_toggleall($togglegroup, false, [
                    'classes' => 'usercheckbox',
                    'name' => 'user' . $u->id,
                    'value' => 1,
                ]);
                $data['select'] = $OUTPUT->render($checkbox);
            }
            $dataset[] = $data;
        }
    }
    return $dataset;
}
