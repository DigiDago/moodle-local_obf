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

class provider implements
    // This plugin does store personal user data.
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\data_provider {

    /**
     * Returns meta-data for a given userid.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_obf_criterion_courses',
            [
                'userid' => 'privacy:metadata:userid',
                'courseid' => 'privacy:metadata:courseid',
                'grade' => 'privacy:metadata:grade',
                'completed_by' => 'privacy:metadata:completed_by',
                'criteria_type' => 'privacy:metadata:criteria_type',
            ],
            'privacy:metadata:criterion_courses'
        );

        $collection->add_database_table(
            'local_obf_criterion',
            [
                'id' => 'privacy:metadata:id',
                'badge_id' => 'privacy:metadata:badge_id',
                'client_id' => 'privacy:metadata:client_id',
                'completion_method' => 'privacy:metadata:completion_method',
                'use_addendum' => 'privacy:metadata:use_addendum',
                'addendum' => 'privacy:metadata:addendum',
            ],
            'privacy:metadata:criterion'
        );

        $collection->add_database_table(
            'local_obf_email_templates',
            [
                'id' => 'privacy:metadata:id',
                'badge_id' => 'privacy:metadata:badge_id',
                'subject' => 'privacy:metadata:subject',
                'body' => 'privacy:metadata:body',
                'link_text' => 'privacy:metadata:link_text',
                'footer' => 'privacy:metadata:footer',
            ],
            'privacy:metadata:email_templates'
        );

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
     * Export user data for the specified user.
     *
     * @param int $userid The ID of the user to export data for.
     * @return array An associative array containing the user's data.
     */
    public static function export_user_data($userid) {
        $userdata = [];

        // Fetch data from the 'local_obf_criterion_courses' table for the user.
        $criterionCoursesData = self::get_criterion_courses_data($userid);
        if (!empty($criterionCoursesData)) {
            $userdata['criterion_courses'] = $criterionCoursesData;
        }

        // Fetch data from the 'local_obf_criterion' table for the user.
        $criterionData = self::get_criterion_data($userid);
        if (!empty($criterionData)) {
            $userdata['criterion'] = $criterionData;
        }

        // Fetch data from the 'local_obf_email_templates' table for the user.
        $emailTemplatesData = self::get_email_templates_data($userid);
        if (!empty($emailTemplatesData)) {
            $userdata['email_templates'] = $emailTemplatesData;
        }

        // Fetch data from the 'local_obf_criterion_met' table for the user.
        $criterionMetData = self::get_criterion_met_data($userid);
        if (!empty($criterionMetData)) {
            $userdata['criterion_met'] = $criterionMetData;
        }

        // Fetch data from the 'local_obf_backpack_emails' table for the user.
        $backpackEmailsData = self::get_backpack_emails_data($userid);
        if (!empty($backpackEmailsData)) {
            $userdata['backpack_emails'] = $backpackEmailsData;
        }

        // Fetch data from the 'local_obf_user_preferences' table for the user.
        $userPreferencesData = self::get_user_preferences_data($userid);
        if (!empty($userPreferencesData)) {
            $userdata['user_preferences'] = $userPreferencesData;
        }

        // Fetch data from the 'local_obf_user_emails' table for the user.
        $userEmailsData = self::get_user_emails_data($userid);
        if (!empty($userEmailsData)) {
            $userdata['user_emails'] = $userEmailsData;
        }

        // Fetch data from an external location (if applicable) for the user.
        $remoteData = self::get_remote_data($userid);
        if (!empty($remoteData)) {
            $userdata['remote_data'] = $remoteData;
        }

        return $userdata;
    }

    /**
     * Function to fetch 'local_obf_criterion_courses' data for the specified user.
     *
     * @param int $userid The ID of the user to fetch data for.
     * @return array An array containing the user's 'local_obf_criterion_courses' data.
     */
    private static function get_criterion_courses_data($userid) {
        global $DB;

        // Define the fields you want to retrieve from the table.
        $fields = [
            'id',
            'obf_criterion_id',
            'courseid',
            'grade',
            'completed_by',
            'criteria_type',
        ];

        // Build the SQL query to fetch the data.
        $sql = "SELECT " . implode(',', $fields) . " FROM {local_obf_criterion_courses} WHERE userid = :userid";

        // Execute the SQL query with the user ID as a parameter.
        $params = ['userid' => $userid];
        $result = $DB->get_records_sql($sql, $params);

        // Return the data as an array.
        return !empty($result) ? array_values($result) : [];
    }

    /**
     * Function to fetch 'local_obf_criterion' data for the specified user.
     *
     * @param int $userid The ID of the user to fetch data for.
     * @return array An array containing the user's 'local_obf_criterion' data.
     */
    private static function get_criterion_data($userid) {
        global $DB;

        // Define the fields you want to retrieve from the table.
        $fields = [
            'id',
            'badge_id',
            'client_id',
            'completion_method',
            'use_addendum',
            'addendum',
        ];

        // Build the SQL query to fetch the data.
        $sql = "SELECT " . implode(',', $fields) . " FROM {local_obf_criterion} WHERE userid = :userid";

        // Execute the SQL query with the user ID as a parameter.
        $params = ['userid' => $userid];
        $result = $DB->get_records_sql($sql, $params);

        // Return the data as an array.
        return !empty($result) ? array_values($result) : [];
    }

    /**
     * Function to fetch 'local_obf_email_templates' data for the specified user.
     *
     * @param int $userid The ID of the user to fetch data for.
     * @return array An array containing the user's 'local_obf_email_templates' data.
     */
    private static function get_email_templates_data($userid) {
        global $DB;

        // Define the fields you want to retrieve from the table.
        $fields = [
            'id',
            'badge_id',
            'subject',
            'body',
            'link_text',
            'footer',
        ];

        // Build the SQL query to fetch the data.
        $sql = "SELECT " . implode(',', $fields) . " FROM {local_obf_email_templates} WHERE userid = :userid";

        // Execute the SQL query with the user ID as a parameter.
        $params = ['userid' => $userid];
        $result = $DB->get_records_sql($sql, $params);

        // Return the data as an array.
        return !empty($result) ? array_values($result) : [];
    }

    /**
     * Function to fetch 'local_obf_criterion_met' data for the specified user.
     *
     * @param int $userid The ID of the user to fetch data for.
     * @return array An array containing the user's 'local_obf_criterion_met' data.
     */
    private static function get_criterion_met_data($userid) {
        global $DB;

        // Define the fields you want to retrieve from the table.
        $fields = [
            'id',
            'obf_criterion_id',
            'user_id',
            'met_at',
        ];

        // Build the SQL query to fetch the data.
        $sql = "SELECT " . implode(',', $fields) . " FROM {local_obf_criterion_met} WHERE user_id = :userid";

        // Execute the SQL query with the user ID as a parameter.
        $params = ['userid' => $userid];
        $result = $DB->get_records_sql($sql, $params);

        // Return the data as an array.
        return !empty($result) ? array_values($result) : [];
    }

    /**
     * Function to fetch 'local_obf_backpack_emails' data for the specified user.
     *
     * @param int $userid The ID of the user to fetch data for.
     * @return array An array containing the user's 'local_obf_backpack_emails' data.
     */
    private static function get_backpack_emails_data($userid) {
        global $DB;

        // Define the fields you want to retrieve from the table.
        $fields = [
            'id',
            'user_id',
            'email',
            'backpack_id',
            'badge_groups',
            'backpack_provider',
            'backpack_data',
        ];

        // Build the SQL query to fetch the data.
        $sql = "SELECT " . implode(',', $fields) . " FROM {local_obf_backpack_emails} WHERE user_id = :userid";

        // Execute the SQL query with the user ID as a parameter.
        $params = ['userid' => $userid];
        $result = $DB->get_records_sql($sql, $params);

        // Return the data as an array.
        return !empty($result) ? array_values($result) : [];
    }

    /**
     * Function to fetch remote data.
     *
     * @param int $userid The ID of the user to fetch data for.
     * @return string A text message indicating users to contact https://openbadgefactory.com/ for more details.
     */
    private static function get_remote_data($userid) {
        return get_string('contact_openbadgefactory', 'local_obf');
    }

    /**
     * Function to delete 'local_obf' plugin data for a specified user.
     *
     * @param approved_contextlist $contextlist The approved contextlist containing the user's data.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        foreach ($contextlist as $context) {
            $userid = $context->get_user()->id;
            // Add additional cases for other components or plugins if applicable.
            if ($context->get_component() == 'local_obf') {
                if ($context->get_area() === 'criterion_courses') {
                    // Delete data from 'local_obf_criterion_courses' table for the user.
                    $DB->delete_records('local_obf_criterion_courses', ['userid' => $userid]);
                } else if ($context->get_area() === 'criterion') {
                    // Delete data from 'local_obf_criterion' table for the user.
                    $DB->delete_records('local_obf_criterion', ['userid' => $userid]);
                } else if ($context->get_area() === 'email_templates') {
                    // Delete data from 'local_obf_email_templates' table for the user.
                    $DB->delete_records('local_obf_email_templates', ['userid' => $userid]);
                } else if ($context->get_area() === 'criterion_met') {
                    // Delete data from 'local_obf_criterion_met' table for the user.
                    $DB->delete_records('local_obf_criterion_met', ['user_id' => $userid]);
                } else if ($context->get_area() === 'backpack_emails') {
                    // Delete data from 'local_obf_backpack_emails' table for the user.
                    $DB->delete_records('local_obf_backpack_emails', ['user_id' => $userid]);
                }
            }
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
}
