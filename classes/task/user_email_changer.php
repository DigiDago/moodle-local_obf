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
 * Description of user_email_changer
 *
 * @package    local_obf
 * @copyright  2013-2020, Open Badge Factory Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_obf\task;

use classes\obf_backpack;

class user_email_changer extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('useremailupdater', 'local_obf');
    }

    public function execute() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/local/obf/classes/backpack.php');

        $users = obf_backpack::get_user_ids_with_backpack();
        $records = $DB->get_records_list('user', 'id',
            $users, '', 'id, email');

        foreach ($users as $user) {
            $pack = obf_backpack::get_instance_by_userid($user, $DB);
            if ($pack) {
                if ($pack->get_email() != $records[$pack->get_user_id()]->email) {
                    if (!$pack->requires_email_verification()) {
                        $pack->disconnect();
                    }
                }
            }
        }
    }
}
