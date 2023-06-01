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
 * Page for displaying the badges of course participants.
 *
 * @package    local_obf
 * @copyright  2013-2020, Open Badge Factory Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use classes\obf_assertion;
use classes\obf_badge;
use classes\obf_client;

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/badge.php');
require_once(__DIR__ . '/classes/event.php');

$clientid = optional_param('clientid', null, PARAM_ALPHANUM);

obf_client::connect($clientid, '*');

$badgeid = optional_param('id', '', PARAM_ALPHANUM);
$courseid = optional_param('courseid', 1, PARAM_INT);
$action = optional_param('action', 'badges', PARAM_ALPHANUM);
$url = new moodle_url('/local/obf/courseuserbadges.php',
    array('courseid' => $courseid, 'action' => $action));
$currpage = optional_param('page', '0', PARAM_INT);
$context = context_course::instance($courseid);
$onlydetailstab = 1;

require_login($courseid);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');

$badge = empty($badgeid) ? null : obf_badge::get_instance($badgeid);

switch ($action) {
    // Display badge info.
    case 'show':
        require_capability('local/obf:viewdetails', $context);
        $client = obf_client::get_instance();
        $show = optional_param('show', 'details', PARAM_ALPHANUM);
        $coursebadgeurl = new moodle_url('/local/obf/courseuserbadges.php',
            array('action' => 'show', 'id' => $badgeid));

        $PAGE->navbar->add(get_string('siteadmin', 'local_obf'),
            new moodle_url('/admin/search.php'));
        $PAGE->navbar->add(get_string('obf', 'local_obf'),
            new moodle_url('/admin/category.php', array('category' => 'obf')));
        $PAGE->navbar->add(get_string('badgelist', 'local_obf'),
            new moodle_url('/local/obf/badge.php', array('action' => 'list')));

        $PAGE->navbar->add($badge->get_name(), $coursebadgeurl);

        $content = $PAGE->get_renderer('local_obf')->render_badge_heading($badge,
            $context);

        switch ($show) {
            // Badge details.
            case 'details':
                $content .= $PAGE->get_renderer('local_obf')->page_badgedetails(
                    $client, $badge, $context, $show, $page, $message, $onlydetailstab);
        }
        break;

    case 'badges':
        require_capability('local/obf:seeparticipantbadges', $context);
        $participants = get_enrolled_users($context, 'local/obf:earnbadge', 0, 'u.*', null, 0, 0, true);
        $content = $PAGE->get_renderer('local_obf')->render_course_participants($courseid, $participants);
        break;

    case 'history':
        require_capability('local/obf:viewhistory', $context);
        $client = obf_client::get_instance();

        $search = optional_param('search', null, PARAM_TEXT);
        $searchparams = array(
            'api_consumer_id' => OBF_API_CONSUMER_ID,
            'log_entry' => '"course_id":"' . $courseid . '"',
            'count_only' => 1,
            'query' => $search
        );
        $res = $client->get_assertions(null, null, $searchparams);

        $historysize = $res[0]['result_count'];

        $searchparams['count_only'] = 0;
        $searchparams['limit'] = 10;
        $searchparams['offset'] = $currpage * 10;
        $searchparams['order_by'] = 'asc';

        $history = obf_assertion::get_assertions($client, null, null, -1, false, $searchparams);

        $content = $PAGE->get_renderer('local_obf')->render_client_selector($url, $clientid);
        $content .= $PAGE->get_renderer('local_obf')->print_issuing_history($client, $context, $historysize, $currpage, $history);
        break;
}

echo $OUTPUT->header();
$content .= $OUTPUT->footer();
echo $content;
