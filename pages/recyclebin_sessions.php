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
 * Sessions recycle bin page
 *
 * @package    local_session
 * @copyright  2021 Edunao SAS (contact@edunao.com)
 * @author     remi <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once(__DIR__ . '/../lib.php');

// Require login.
require_login();

$entityid = required_param('entityid', PARAM_INT);
$action = optional_param('action', null, PARAM_ALPHA);

if (!$entityid) {
    throw new \coding_exception('The entity ID must be set.');
}

// Get entity infos.
$entity = \local_mentor_core\entity_api::get_entity($entityid);

$mainentity = $entity->get_main_entity();

// Fetch context.
$context = $mainentity->get_context();

// Set page context.
$PAGE->set_context($context);

// Set page url.
$url = new \moodle_url('/local/session/pages/recyclebin_sessions.php', ['entityid' => $mainentity->id]);
$PAGE->set_url($url);

// Set navbar.
$PAGE->navbar->add(get_string('managespaces', 'local_session'), new moodle_url('/local/entities/index.php'));
$PAGE->navbar->add($mainentity->name);

$sessioncourse = $mainentity->get_edadmin_courses('session');
$PAGE->navbar->add(get_string('managesessions', 'local_session'), $sessioncourse['link']);

$PAGE->navbar->add(get_string('recyclebin', 'local_session'), $url);

// If we are doing anything, we need a sesskey!
if (!empty($action)) {
    raise_memory_limit(MEMORY_EXTRA);
    require_sesskey();

    switch ($action) {
        case 'restore':
            // Restore it.
            $itemid = required_param('itemid', PARAM_INT);
            \local_mentor_core\session_api::restore_session($entityid, $itemid, $PAGE->url);
            break;

        case 'delete':
            // Delete it.
            $itemid = required_param('itemid', PARAM_INT);
            \local_mentor_core\session_api::remove_session_item($entityid, $itemid, $PAGE->url);
            break;

        case 'deleteall':
            // Delete all.
            \local_mentor_core\entity_api::cleanup_session_recyblebin($entityid, $PAGE->url);
            break;

        default:
            break;
    }
}

// Set page title.
$PAGE->set_heading(get_string('recyclebintitlepage', 'local_session'));
$PAGE->set_title(get_string('recyclebintitlepage', 'local_session'));

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('local_session', 'recyclebin');

// Get table items.
$items = $entity->get_sessions_recyclebin_items();

// Display the table.
$renderer->display($entity, $items);

// Delete all link.
if (count($items) > 0) {
    $deleteaction = new confirm_action(get_string('deleteallpopin', 'local_session'));
    echo $OUTPUT->action_link(
        $url . '&action=deleteall&sesskey=' . sesskey(), get_string('deleteall', 'local_session'), $deleteaction
    );
}

echo $OUTPUT->footer();
