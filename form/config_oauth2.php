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
 * Config form for OAuth2 API authentication.
 *
 * @package    local_obf
 * @copyright  2013-2021, Open Badge Factory Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use classes\obf_badge;
use classes\obf_client;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Plugin config / Authentication form.
 *
 * @copyright  2013-2021, Open Badge Factory Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class obf_config_oauth2_form extends moodleform {
    protected $isadding;
    private $accesstoken = '';
    private $tokenexpires = 0;
    private $clientname = '';
    private $roles = [];

    public function __construct($actionurl, $isadding, $roles) {
        $this->isadding = $isadding;
        $this->roles = $roles;
        parent::__construct($actionurl);
    }

    public function definition() {
        global $DB, $PAGE;

        $mform = $this->_form;

        // Add header for the client block
        $mform->addElement('header', 'obfeditclientheader', get_string('client', 'local_obf'));

        if ($this->isadding) {
            // Add fields for adding a new client
            $mform->addElement('text', 'obf_url', get_string('obfurl', 'local_obf'), array('size' => 60));
            $mform->setType('obf_url', PARAM_URL);
            $mform->addRule('obf_url', null, 'required');
            $mform->addHelpButton('obf_url', 'obfurl', 'local_obf');

            $mform->addElement('text', 'client_id', get_string('clientid', 'local_obf'), array('size' => 60));
            $mform->setType('client_id', PARAM_NOTAGS);
            $mform->addRule('client_id', null, 'required');
            $mform->addHelpButton('client_id', 'clientid', 'local_obf');

            $mform->addElement('text', 'client_secret', get_string('clientsecret', 'local_obf'), array('size' => 60));
            $mform->setType('client_secret', PARAM_NOTAGS);
            $mform->addRule('client_secret', null, 'required');
            $mform->addHelpButton('client_secret', 'clientsecret', 'local_obf');
        } else {
            // Add static fields for editing an existing client
            $mform->addElement('text', 'client_name', get_string('clientname', 'local_obf'), array('size' => 60));
            $mform->setType('client_name', PARAM_NOTAGS);
            $mform->addRule('client_name', null, 'required');

            $mform->addElement('static', 'obf_url', get_string('obfurl', 'local_obf'));
            $mform->addElement('static', 'client_id', get_string('clientid', 'local_obf'));
            $mform->addElement('static', 'client_secret', get_string('clientsecret', 'local_obf'));
        }

        $canissue = $this->roles_available();
        if (!empty($canissue)) {
            // Add header for issuer roles
            $mform->addElement('header', 'obfeditclientheader', get_string('issuerroles', 'local_obf'));

            $mform->addElement('static', 'role_help', '', get_string('issuerroles_help', 'local_obf'));

            foreach ($canissue as $roleid => $rolename) {
                $mform->addElement('advcheckbox', 'role_' . $roleid, null, $rolename, array('group' => 1));
                $checked = $this->isadding || in_array($roleid, $this->roles) ? 1 : 0;
                $mform->setDefault('role_' . $roleid, $checked);
            }
        }

        // Set options for autocomplete field.
        $options = array(
            'multiple' => true,
            'noselectionstring' => get_string('allareas', 'search'),
            'data-updatebutton-field' => 'autocomplete',
        );

        // Get course categories from the database.
        $categories = $DB->get_records('course_categories', null, 'sortorder ASC', 'id, name');

        // Create an array of categories for autocomplete.
        $categoryoptions = array(
            0 => 'All'
        );
        foreach ($categories as $category) {
            $categoryoptions[$category->id] = $category->name;
        }

        // Generate an array of badge categories.
        $client = obf_client::get_instance();
        $badgecategories = array(
            0 => 'All'
        );

        if($client->client_id() && $client->oauth2_access_token()) {
            $clientcateg = $client->get_categories();
            if ($clientcateg) {
                foreach ($clientcateg as $category) {
                    $badgecategories[$category] = $category;
                }
            }
        }

        $rules = $DB->get_records_sql('SELECT ruleid FROM {local_obf_rulescateg} WHERE oauth2_id = ? GROUP BY ruleid',
            ['oauth2_id' => optional_param('id', null, PARAM_INT)]);

        // Vérifier si $rules est null ou vide.
        $ruleid = 0;
        if (empty($rules)) {
            // Aucune règle n'est associée, créer un seul ensemble de blocs
            // Add header for Moodle categories
            $mform->addElement('header', 'chooseurmoodlecategories', get_string('rules', 'local_obf'));
            $mform->setExpanded('chooseurmoodlecategories');

            // Add autocomplete field for Moodle course categories
            $mform->addElement('autocomplete', 'coursecategorieid', get_string('choosecategories', 'local_obf'),
                $categoryoptions, $options);
            $mform->setType('coursecategorieid', PARAM_INT);

            // Add autocomplete field for badge categories
            $mform->addElement('autocomplete', 'badgecategoriename', get_string('chooseurbadgecategories', 'local_obf'),
                $badgecategories, $options);
            $mform->setType('badgecategoriename', PARAM_TEXT);
        } else {
            $rulecount = 1;

            $mform->addElement('hidden', 'delete_rule', false);
            $mform->setType('delete_rule', PARAM_BOOL);
            $mform->addElement('hidden', 'delete_rule_id', null);
            $mform->setType('delete_rule_id', PARAM_INT);

            foreach ($rules as $rule) {
                // Créer un nouveau bloc 'chooseurmoodlecategories' pour chaque règle.
                $ruledatas = $DB->get_records('local_obf_rulescateg',
                    ['ruleid' => $rule->ruleid, 'oauth2_id' => optional_param('id', null, PARAM_INT)]);

                $badgedefaultcateg = [];
                $coursedefaultcateg = [];

                if (isset($ruledatas)) {
                    foreach($ruledatas as $ruledata) {
                        if ($ruledata->badgecategoriename != null) {
                            $badgedefaultcateg[] = $ruledata->badgecategoriename;
                        }
                        if ($ruledata->coursecategorieid != null) {
                            $coursedefaultcateg[] = $ruledata->coursecategorieid;
                        }
                    }
                }

                $headerName = 'chooseurmoodlecategories_'. $rule->ruleid;
                $mform->addElement('header', $headerName, get_string('rules', 'local_obf') . " $rulecount");
                $mform->setExpanded($headerName);

                // Add autocomplete field for Moodle course categories.
                $mform->addElement('autocomplete', 'coursecategorieid_' . $rule->ruleid,
                    get_string('choosecategories', 'local_obf'), $categoryoptions, $options);
                $mform->setType('coursecategorieid_' . $rule->ruleid, PARAM_INT);
                $mform->setDefaults(['coursecategorieid_' . $rule->ruleid => $coursedefaultcateg]);

                // Add autocomplete field for badge categories.
                $mform->addElement('autocomplete', 'badgecategoriename_' . $rule->ruleid,
                    get_string('chooseurbadgecategories', 'local_obf'), $badgecategories, $options);
                $mform->setType('badgecategoriename_' . $rule->ruleid, PARAM_TEXT);
                $mform->setDefaults(['badgecategoriename_' . $rule->ruleid => $badgedefaultcateg]);

                // Create the delete button element.
                $deleteButton = $mform->addElement('button', 'delete_rule_button',
                    get_string('delete_rule', 'local_obf'));

                // Set the label for the delete button.
                $deleteButton->setAttributes([
                    'value' => get_string('delete_rule_button', 'local_obf'),
                    'class' => 'delete-button',
                    'ruleid' => $rule->ruleid
                ]);

                // Count number of rules.
                $numberofrule = $rule->ruleid;
                $rulecount++;
            }
        }

        $submitlabel = null; // Default.
        if ($this->isadding) {
            $submitlabel = get_string('addnewclient', 'local_obf');
        }

        $mform->addElement('button', 'add_rules_button', get_string('addrules', 'local_obf'));
        $mform->addElement('hidden', 'add_rules_value', false);
        $mform->setType('add_rules_value', PARAM_BOOL);
        $mform->addElement('hidden', 'numberofrule', $numberofrule);
        $mform->setType('numberofrule', PARAM_INT);
        $mform->closeHeaderBefore('add_rules_button');

        // Load the separate JavaScript file and call the event handler.
        $PAGE->requires->js_call_amd('local_obf/obf_config_oauth2_form', 'init');

        $this->add_action_buttons(true, $submitlabel);
    }

    public function validation($data, $files) {

        $errors = parent::validation($data, $files);

        if ($this->isadding && empty($errors)) {
            try {
                $client = obf_client::get_instance();
                $input = (object) $data;
                $client->set_oauth2($input);
                $res = $client->oauth2_access_token();
                $this->access_token = $res['access_token'];
                $this->token_expires = $res['token_expires'];

                $issuer = $client->get_issuer();
                $this->client_name = $issuer['name'];
            } catch (Exception $e) {
                $errors['client_secret'] = get_string('invalidclientsecret', 'local_obf');
            }
        }

        return $errors;
    }

    public function get_data() {
        $data = parent::get_data();

        if ($data && $this->isadding) {
            $data->access_token = $this->access_token;
            $data->token_expires = $this->token_expires;
            $data->client_name = $this->client_name;
        }
        return $data;
    }

    private function roles_available() {
        global $DB;

        $sql = "SELECT r.id, COALESCE(NULLIF(r.name, ''), r.shortname) FROM {role} r
                INNER JOIN {role_capabilities} rc ON r.id = rc.roleid
                WHERE rc.capability = ? AND rc.permission = 1
                ORDER BY r.id";

        $canissue = $DB->get_records_sql_menu($sql, array('local/obf:issuebadge'));

        return $canissue;
    }
}
