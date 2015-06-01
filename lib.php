<?php

defined('MOODLE_INTERNAL') || die();

/**
 * OBF_DEFAULT_ADDRESS - The URL of Open Badge Factory.
 */
define('OBF_DEFAULT_ADDRESS', 'https://openbadgefactory.com/');

/**
 * OBF_API_URL - The URL of Open Badge Factory API.
 */
define('OBF_API_URL', OBF_DEFAULT_ADDRESS . 'v1');

/**
 * OBF_API_CONSUMER_ID - The consumer id used in API requests.
 */
define('OBF_API_CONSUMER_ID', 'Moodle');

/**
 * OBF API error codes.
 */
define('OBF_API_CODE_CERT_ERROR', 495);
define('OBF_API_CODE_NO_CERT', 496);

require_once(__DIR__ . '/class/criterion/criterion.php');
require_once(__DIR__ . '/class/criterion/course.php');

/**
 * Reviews the badge criteria and issues the badges (if necessary) when
 * a course is completed.
 *
 * @global moodle_database $DB
 * @param stdClass $eventdata
 * @return boolean Returns true if everything went ok.
 */
function local_obf_course_completed(stdClass $eventdata) {
    global $DB;

    $user = $DB->get_record('user', array('id' => $eventdata->userid));
    $backpack = obf_backpack::get_instance($user);

    // If the user has configured the backpack settings, use the backpack email instead of the
    // default email.
    $recipients = array($backpack === false ? $user->email : $backpack->get_email());

    // No capability -> no badge.
    if (!has_capability('local/obf:earnbadge',
                    context_course::instance($eventdata->course),
                    $eventdata->userid)) {
        return true;
    }

    // Get all criteria related to course completion
    $criteria = obf_criterion::get_course_criterion($eventdata->course);

    foreach ($criteria as $criterionid => $criterion) {
        // User has already met this criterion
        if ($criterion->is_met_by_user($user)) {
            continue;
        }

        // Has the user completed all the required criteria (completion/grade/date)
        // in this criterion?
        $criterionmet = $criterion->review($eventdata->userid,
                $eventdata->course);

        // Criterion was met, issue the badge
        if ($criterionmet) {
            $badge = $criterion->get_badge();
            $email = is_null($badge->get_email()) ? new obf_email() : $badge->get_email();

            $badge->issue($recipients, time(), $email->get_subject(),
                    $email->get_body(), $email->get_footer());
            $criterion->set_met_by_user($user->id);
        }
    }

    return true;
}

/**
 * When the course is deleted, this function deletes also the related badge
 * issuance criteria.
 *
 * @param stdClass $course
 * @return boolean
 */
function local_obf_course_deleted(stdClass $course) {
    global $DB;

    obf_criterion_course::delete_by_course($course, $DB);
    return true;
}

/**
 * Adds the OBF-links to Moodle's navigation, Moodle 2.2 -style.
 *
 * @global type $COURSE The current course
 * @global moodle_page $PAGE
 * @param settings_navigation $navigation
 */
function obf_extends_navigation(global_navigation $navigation) {
    global $COURSE, $PAGE;

    if ($COURSE->id > 1 && $branch = $navigation->find($COURSE->id,
            navigation_node::TYPE_COURSE)) {
        local_obf_add_course_participant_badges_link($branch);
    }

    if (@$PAGE->settingsnav) {
        if (($branch = $PAGE->settingsnav->get('courseadmin'))) {
            local_obf_add_course_admin_link($branch);
        }

        if (($branch = $PAGE->settingsnav->get('usercurrentsettings'))) {
            local_obf_add_backpack_settings_link($branch);
        }
    }
}

/**
 * Adds the OBF-links to Moodle's settings navigation.
 *
 * @global type $COURSE
 * @param settings_navigation $navigation
 */
function local_obf_extends_settings_navigation(settings_navigation $navigation) {
    global $COURSE;

    if (($branch = $navigation->get('courseadmin'))) {
        local_obf_add_course_admin_link($branch);
    }

    if (($branch = $navigation->get('usercurrentsettings'))) {
        local_obf_add_backpack_settings_link($branch);
    }
}

/**
 * Adds the OBF-links to Moodle's navigation.
 *
 * @global moodle_page $PAGE
 * @global type $COURSE
 * @param global_navigation $navigation
 */
function local_obf_extends_navigation(global_navigation $navigation) {
    global $PAGE, $COURSE;

    // Course id 1 is Moodle
    if ($COURSE->id > 1 && $branch = $PAGE->navigation->find($COURSE->id,
            navigation_node::TYPE_COURSE)) {
        local_obf_add_course_participant_badges_link($branch);
    }
}

/**
 * Adds the link to course navigation to see the badges of course participants.
 *
 * @global type $COURSE
 * @param type $branch
 */
function local_obf_add_course_participant_badges_link(&$branch) {
    global $COURSE;

    if (has_capability('local/obf:seeparticipantbadges',
                    context_course::instance($COURSE->id))) {
        $node = navigation_node::create(get_string('courseuserbadges',
                                'local_obf'),
                        new moodle_url('/local/obf/courseuserbadges.php',
                        array('courseid' => $COURSE->id)));
        $branch->add_node($node);
    }
}

/**
 * Adds the OBF-links to course management navigation.
 *
 * @global type $COURSE
 * @param type $branch
 */
function local_obf_add_course_admin_link(&$branch) {
    global $COURSE;

    if (has_capability('local/obf:issuebadge',
                    context_course::instance($COURSE->id))) {
        $obfnode = navigation_node::create(get_string('obf', 'local_obf'),
                        new moodle_url('/local/obf/badge.php',
                        array('action' => 'list', 'courseid' => $COURSE->id)));
        $branch->add_node($obfnode, 'backup');
    }
}

/**
 * Adds the backpack configuration link to navigation.
 *
 * @param type $branch
 */
function local_obf_add_backpack_settings_link(&$branch) {
    $node = navigation_node::create(get_string('backpacksettings', 'local_obf'),
                    new moodle_url('/local/obf/userconfig.php'));
    $branch->add_node($node);
}

/**
 * Checks the certificate expiration of the OBF-client and sends a message to admin if the
 * certificate is expiring. This function is called periodically when Moodle's cron job is run.
 * The interval is defined in version.php.
 *
 * @global type $CFG
 * @return boolean
 */
function local_obf_cron() {
    global $CFG;

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
    $textparams->obfurl = OBF_DEFAULT_ADDRESS;

    foreach ($admins as $admin) {
        $eventdata = new object();
        $eventdata->component = 'moodle';
        $eventdata->name = $severity;
        $eventdata->userfrom = $admin;
        $eventdata->userto = $admin;
        $eventdata->subject = get_string('expiringcertificatesubject',
                'local_obf');
        $eventdata->fullmessage = get_string('expiringcertificate', 'local_obf',
                $textparams);
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml = get_string('expiringcertificate',
                'local_obf', $textparams);
        $eventdata->smallmessage = get_string('expiringcertificatesubject',
                'local_obf');

        $result = message_send($eventdata);
    }

    return true;
}

// Moodle 2.2 -support
if (!function_exists('users_order_by_sql')) {

    /**
     * This function generates the standard ORDER BY clause for use when generating
     * lists of users. If you don't have a reason to use a different order, then
     * you should use this method to generate the order when displaying lists of users.
     *
     * COPIED FROM THE CODE OF MOODLE 2.5
     */
    function users_order_by_sql($usertablealias = '', $search = null,
                                context $context = null) {
        global $DB, $PAGE;

        if ($usertablealias) {
            $tableprefix = $usertablealias . '.';
        }
        else {
            $tableprefix = '';
        }

        $sort = "{$tableprefix}lastname, {$tableprefix}firstname, {$tableprefix}id";
        $params = array();

        if (!$search) {
            return array($sort, $params);
        }

        if (!$context) {
            $context = $PAGE->context;
        }

        $exactconditions = array();
        $paramkey = 'usersortexact1';

        $exactconditions[] = $DB->sql_fullname($tableprefix . 'firstname',
                        $tableprefix . 'lastname') .
                ' = :' . $paramkey;
        $params[$paramkey] = $search;
        $paramkey++;

        $fieldstocheck = array_merge(array('firstname', 'lastname'),
                get_extra_user_fields($context));
        foreach ($fieldstocheck as $key => $field) {
            $exactconditions[] = 'LOWER(' . $tableprefix . $field . ') = LOWER(:' . $paramkey . ')';
            $params[$paramkey] = $search;
            $paramkey++;
        }

        $sort = 'CASE WHEN ' . implode(' OR ', $exactconditions) .
                ' THEN 0 ELSE 1 END, ' . $sort;

        return array($sort, $params);
    }

}