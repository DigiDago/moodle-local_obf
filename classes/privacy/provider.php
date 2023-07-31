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
 * Privacy Subsystem implementing null_provider.
 *
 * @package     local_obf
 * @category    admin
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_obf\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\writer;

class provider implements
    // This plugin does store personal user data.
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\data_provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\user_preference_provider {

    /**
     * Returns meta-data for a given userid.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {

        $collection->add_database_table(
            'local_obf_criterion_met',
            [
                'id' => 'privacy:metadata:id',
                'obf_criterion_id' => 'privacy:metadata:obf_criterion_id',
                'user_id' => 'privacy:metadata:user_id',
                'met_at' => 'privacy:metadata:met_at',
            ],
            'privacy:metadata:criterion_met'
        );

        $collection->add_database_table(
            'local_obf_backpack_emails',
            [
                'id' => 'privacy:metadata:id',
                'user_id' => 'privacy:metadata:user_id',
                'email' => 'privacy:metadata:email',
                'backpack_id' => 'privacy:metadata:backpack_id',
                'badge_groups' => 'privacy:metadata:badge_groups',
                'backpack_provider' => 'privacy:metadata:backpack_provider',
                'backpack_data' => 'privacy:metadata:backpack_data',
            ],
            'privacy:metadata:backpack_emails'
        );

        $collection->add_database_table(
            'local_obf_user_preferences',
            [
                'id' => 'privacy:metadata:id',
                'user_id' => 'privacy:metadata:user_id',
                'name' => 'privacy:metadata:name',
                'value' => 'privacy:metadata:value',
            ],
            'privacy:metadata:user_preferences'
        );

        $collection->add_database_table(
            'local_obf_user_emails',
            [
                'id' => 'privacy:metadata:id',
                'email' => 'privacy:metadata:email',
                'token' => 'privacy:metadata:token',
                'verified' => 'privacy:metadata:verified',
                'user_id' => 'privacy:metadata:user_id',
                'timestamp' => 'privacy:metadata:timestamp',
            ],
            'privacy:metadata:user_emails'
        );

        $collection->add_database_table(
            'local_obf_badge_blacklists',
            [
                'id' => 'privacy:metadata:id',
                'user_id' => 'privacy:metadata:user_id',
                'badge_id' => 'privacy:metadata:badge_id',
            ],
            'privacy:metadata:badge_blacklists'
        );

        $collection->add_database_table(
            'local_obf_issue_events',
            [
                'id' => 'privacy:metadata:id',
                'user_id' => 'privacy:metadata:user_id',
                'event_id' => 'privacy:metadata:event_id',
                'obf_criterion_id' => 'privacy:metadata:obf_criterion_id',
            ],
            'privacy:metadata:issue_events'
        );

        $collection->add_database_table(
            'local_obf_history_emails',
            [
                'id' => 'privacy:metadata:id',
                'user_id' => 'privacy:metadata:user_id',
                'email' => 'privacy:metadata:email',
                'timestamp' => 'privacy:metadata:timestamp',
            ],
            'privacy:metadata:history_emails'
        );

        // Add additional fields for the remote database.
        $collection->add_external_location_link(
            'local_obf_remote_data',
            [
                'badge_id' => 'privacy:metadata:badge_id',
                'full_name' => 'privacy:metadata:full_name',
                'email' => 'privacy:metadata:email',
                'criteria_addendum' => 'privacy:metadata:criteria_addendum',
                'course_id' => 'privacy:metadata:course_id',
                'course_name' => 'privacy:metadata:course_name',
                'activity_name' => 'privacy:metadata:activity_name',
                'issue_date' => 'privacy:metadata:issue_date',
                'expiration_date' => 'privacy:metadata:expiration_date',
            ],
            'privacy:metadata:remote_data'
        );

        return $collection;
    }

    /**
     * Export all user data for the specified user in the current plugin.
     *
     * @param  approved_contextlist  $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist)) {
            return;
        }

        $user = $contextlist->get_user();
        $userid = $user->id;

        // Define tables and metadata fields.
        $tables = array(
            'local_obf_criterion_met' => array('id', 'obf_criterion_id', 'user_id', 'met_at'),
            'local_obf_backpack_emails' => array('id', 'user_id', 'email', 'backpack_id', 'badge_groups', 'backpack_provider'),
            'local_obf_user_emails' => array('id', 'email', 'token', 'verified', 'user_id', 'timestamp'),
            'local_obf_badge_blacklists' => array('id', 'user_id', 'badge_id'),
            'local_obf_issue_events' => array('id', 'event_id', 'user_id', 'obf_criterion_id'),
            'local_obf_history_emails' => array('id', 'user_id', 'email', 'timestamp'),
        );

        // Export data for each table.
        foreach ($tables as $table => $fields) {
            $sql = "SELECT " . implode(', ', $fields) . " FROM {" . $table . "} WHERE user_id = :userid";
            $params = array('userid' => $userid);
            $data = $DB->get_records_sql($sql, $params);

            if (!empty($data)) {
                $context = \context_system::instance();
                writer::with_context($context)->export_data([$table], (object) $data);
            }
        }

        $context = \context_system::instance();
        $externalname = 'local_obf_remote_data';
        $data = new \stdClass();
        $data->name = get_string('privacy:metadata:remote_data', 'local_obf');
        $data->description = get_string('contact_openbadgefactory', 'local_obf');
        writer::with_context($context)->export_data([$externalname], $data);
    }

    /**
     * Store all user preferences for the plugin.
     *
     * @param   int         $userid The userid of the user whose data is to be exported.
     */
    public static function export_user_preferences(int $userid) {
        global $DB;
        $user = \core_user::get_user($userid);

        $badgesonprofile = $DB->get_record('local_obf_user_preferences',
            ['user_id' => $user->id, 'name' => 'badgesonprofile'], 'value');

        if (null !== $badgesonprofile->value) {
            switch ($badgesonprofile->value) {
                case '0':
                    $badgesonprofiledescri = get_string('badgesonprofiledescri0', 'local_obf');
                    break;
                case '1':
                default:
                    $badgesonprofiledescri = get_string('badgesonprofiledescri1', 'local_obf');
                    break;
            }
            writer::export_user_preference('local_obf', 'badgesonprofile', $badgesonprofile->value, $badgesonprofiledescri);
        }
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return  contextlist     $contextlist    The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): \core_privacy\local\request\contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();

        $contextlist->add_user_context($userid);

        return $contextlist;
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context($context) {
        // We cannot delete the course or system data as it is needed by the system.
        if ($context->contextlevel != CONTEXT_USER) {
            return;
        }

        // Delete all the user data.
        static::delete_user_data($context->instanceid);
    }

    /**
     * Function to delete 'local_obf' plugin data for a specified user.
     *
     * @param approved_contextlist $contextlist The approved contextlist containing the user's data.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {

        $userid = $contextlist->get_user()->id;

        static::delete_user_data($userid);
    }

    private static function delete_user_data($userid) {
        global $DB;

        $DB->delete_records('local_obf_criterion_met', array('user_id' => $userid));
        $DB->delete_records('local_obf_backpack_emails', array('user_id' => $userid));
        $DB->delete_records('local_obf_user_preferences', array('user_id' => $userid));
        $DB->delete_records('local_obf_user_emails', array('user_id' => $userid));
        $DB->delete_records('local_obf_issue_events', array('user_id' => $userid));
        $DB->delete_records('local_obf_history_emails', array('user_id' => $userid));
        $DB->delete_records('local_obf_badge_blacklists', array('user_id' => $userid));
    }
}
