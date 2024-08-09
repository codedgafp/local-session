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
 * Local plugin "session" - View page
 *
 * @package    local_session
 * @copyright  2020 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Include config.php.
require_once('../../../config.php');

// Include lib.php.
require_once(__DIR__ . '/../lib.php');

// Require login.
require_login();

$sessionid = required_param('sessionid', PARAM_INT);
$returnto = optional_param('returnto', null, PARAM_LOCALURL);

// Sessionid is required.
if (!$sessionid) {
    throw new \coding_exception('The session ID must be set.');
}

// Check if the session exists.
try {
    $session = local_session\session_controller::get_session($sessionid);
} catch (Exception $e) {
    throw new \moodle_exception(get_string('errorcoursenotfound', 'local_session', $sessionid));
}

// Get entity infos.
$sessionentity = $session->get_entity();

$context = $session->get_context();

// Check capabilities.
if (!has_capability('local/session:update', $context)) {
    throw new \moodle_exception('Permission denied');
}

// Set page url.
$url = $session->get_sheet_url();

// Init javascript.
$PAGE->requires->jquery_plugin('ui-css');
$params = new \stdClass();
$PAGE->requires->strings_for_js(['reportsessionmessage', 'lifecycle'], 'local_session');
$PAGE->requires->js_call_amd('local_session/local_session', 'initform', [$params]);

// Set page url.
$PAGE->set_url($url);

// Set navbar.
$PAGE->navbar->add($sessionentity->name);

if (has_capability('local/session:manage', $sessionentity->get_context())) {
    $trainingcourse = $sessionentity->get_edadmin_courses('session');
    $PAGE->navbar->add(get_string('sessionmanagement', 'local_session'), $trainingcourse['link']);
}

$PAGE->navbar->add($sessionentity->name, $session->get_url());
$PAGE->navbar->add(get_string('updatesession', 'local_session'), $url);

// Fetch session context.
$context = $session->get_context();

// Set page context.
$PAGE->set_context($context);

// Set page title.
$PAGE->set_heading($session->fullname);
$PAGE->set_title($session->fullname . ': ' . get_string('updatesession', 'local_session'));

$logo = $sessionentity->get_logo();

$logourl = '';
if ($logo) {
    $logourl = moodle_url::make_pluginfile_url(
        $logo->get_contextid(),
        $logo->get_component(),
        $logo->get_filearea(),
        $logo->get_itemid(),
        $logo->get_filepath(),
        $logo->get_filename()
    );
}

$sharedentities = [];
$allentities = \local_mentor_core\entity_api::get_all_entities(true, [$sessionentity->id], false, null, false);
foreach ($allentities as $entity) {
    $sharedentities[$entity->id] = $entity->shortname;
}

$forminfos = new stdClass();
$forminfos->session = $session;
$forminfos->entity = $sessionentity;
$forminfos->sharedentities = $sharedentities;
$forminfos->logourl = $logourl;
$forminfos->actionurl = $url->out();
$forminfos->returnto = empty($returnto) ? $session->get_url() : $returnto;

$form = \local_mentor_core\session_api::get_session_form($url->out(), $forminfos);

// When form is submitted.
if ($data = $form->get_data()) {
    try {
        // Convert editor data.
        $data = \local_mentor_core\session_api::convert_update_session_editor_data($data);

        // Update a training.
        $session = local_session\session_controller::update_session($data, $form);

        // Redirect to the training.
        redirect($data->returnto);
    } catch (\moodle_exception $e) {
        debugging('Cannot add or update a training: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }
} else if ($form->is_cancelled()) {
    // Redirection when form is cancelled.

    redirect($forminfos->returnto);
} else {
    // Show form for adding/updating training.
    echo $OUTPUT->header();

    // Title when updating training.
    echo $OUTPUT->heading(get_string('updatesession', 'local_session'));

    echo '<p id="warningsinfo">' . get_string('warningsinfo', 'local_session') . '</p>';

    // Prepare initial form data.
    $data = $session->prepare_edit_form();

    // Prepare editor data.
    $data = \local_mentor_core\session_api::prepare_update_session_editor_data($data);

    $form->set_data($data);

    // Display form.
    echo $form->display();

    echo $OUTPUT->render_from_template('local_session/info_session', []);
}

echo $OUTPUT->footer();
