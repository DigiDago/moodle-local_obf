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
 * Description of certificate_expiration_reminder
 *
 * @package    local_obf
 * @copyright  2013-2020, Open Badge Factory Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_obf\task;

use classes\obf_client;
use stdClass;

/**

 *
 * @author jsuorsa
 */
class certificate_expiration_reminder extends \core\task\scheduled_task {
    public function get_name() {
        // Shown in admin screens.
        return get_string('certificateexpirationremindertask', 'local_obf');
    }

    public function execute() {
        global $CFG;

        require_once($CFG->dirroot . '/local/obf/classes/client.php');
        require_once($CFG->libdir . '/messagelib.php');
        require_once($CFG->libdir . '/datalib.php');

        $certexpiresin = obf_client::get_instance()->get_certificate_expiration_date();
        $diff = $certexpiresin - time();
        $days = floor($diff / (60 * 60 * 24));

        // Notify only if there's certain amount of days left before the certification expires.
        $notify = in_array($days, array(30, 25, 20, 15, 10, 5, 4, 3, 2, 1));

        if (!$notify) {
            return true;
        }

        $severity = $days <= 5 ? 'errors' : 'notices';
        $admins = get_admins();
        $textparams = new stdClass();
        $textparams->days = $days;
        $textparams->obfurl = obf_client::get_site_url(); // FIXME missing client id.
        $textparams->configurl = (string) (new \moodle_url('/local/obf/config.php'));

        foreach ($admins as $admin) {
            $eventdata = new \core\message\message();
            $eventdata->component = 'moodle';
            $eventdata->name = $severity;
            $eventdata->userfrom = $admin;
            $eventdata->userto = $admin;
            $eventdata->subject = get_string('expiringcertificatesubject',
                'local_obf');
            $eventdata->fullmessage = get_string('expiringcertificate', 'local_obf',
                $textparams);
            $eventdata->fullmessageformat = FORMAT_MARKDOWN;
            $eventdata->fullmessagehtml = get_string('expiringcertificate',
                'local_obf', $textparams);
            $eventdata->smallmessage = get_string('expiringcertificatesubject',
                'local_obf');

            $result = message_send($eventdata);
        }

        return true;
    }

}

