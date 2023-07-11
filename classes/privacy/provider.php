<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy Subsystem implementing null_provider.
 *
 * @package     local_obf
 * @category    privacy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_obf\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\null_provider;

/**
 * Privacy Subsystem implementing null_provider.
 *
 * @package     local_obf
 * @category    privacy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\provider {

    /**
     * Returns meta-data for a given userid.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection) : collection {
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

        // Add additional fields for the remote database.
        $collection->add_database_table(
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
}
