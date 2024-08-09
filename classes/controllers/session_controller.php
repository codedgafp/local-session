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
 * Entity controller
 *
 * @package    local_session
 * @copyright  2020 Edunao SAS (contact@edunao.com)
 * @author     remi <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_session;

use coding_exception;
use dml_exception;
use Exception;
use local_mentor_core\session;
use local_mentor_core\session_api;
use local_mentor_core\session_form;
use moodle_exception;
use stdClass;
use local_mentor_core\controller_base;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/local/session/lib.php');
require_once($CFG->dirroot . '/local/mentor_core/api/session.php');
require_once($CFG->dirroot . '/local/mentor_core/classes/controllers/controller_base.php');

class session_controller extends controller_base {

    /**
     * Execute action
     *
     * @return mixed
     */
    public function execute() {
        try {
            $action = $this->get_param('action');

            switch ($action) {

                case 'get_sessions_by_entity':
                    // Get count all session record by entity.
                    $data = new stdClass();
                    $data->entityid = $this->get_param('entityid', PARAM_INT);
                    $data->start = 0;
                    $data->length = 0;
                    $data->status = false;
                    $data->datefrom = false;
                    $data->dateto = false;
                    $data->search = false;
                    $data->order = false;
                    $data->recordsTotal = self::count_session_record($data);

                    // Get count all session record by entity with filter.
                    $data->status = $this->get_param('status', PARAM_TEXT, null);
                    $data->opento = $this->get_param('opento', PARAM_TEXT, null);
                    $data->dateto = $this->get_param('dateto', PARAM_INT, null);
                    $data->datefrom = $this->get_param('datefrom', PARAM_INT, null);
                    $data->draw = $this->get_param('draw', PARAM_INT, null);
                    $data->order = $this->get_param('order', PARAM_RAW, null);
                    $data->order = is_null($data->order) ? $data->order : $data->order[0];
                    $data->search = $this->get_param('search', PARAM_RAW, null);
                    $data->filters = $this->get_param('filter', PARAM_RAW, []);
                    $data->recordsFiltered = self::count_session_record($data);

                    // Get session record by entity with filter, lentgh and start.
                    $data->length = $this->get_param('length', PARAM_INT, null);
                    $data->start = $this->get_param('start', PARAM_INT, null);
                    $data->data = self::get_sessions_by_entity($data);
                    return $data;
                case 'create_session':
                    $trainingid = $this->get_param('trainingid', PARAM_INT);
                    $sessionname = $this->get_param('sessionname', PARAM_TEXT);
                    $entityid = $this->get_param('entityid', PARAM_INT);
                    return $this->success(self::create_session($trainingid, $sessionname, $entityid));

                case 'move_session':
                    $sessionid = $this->get_param('sessionid', PARAM_INT);
                    $destinationentity = $this->get_param('destinationentity', PARAM_INT);
                    return $this->success(self::move_session($sessionid, $destinationentity));

                case 'cancel_session':
                    $sessionid = $this->get_param('sessionid', PARAM_INT);
                    return $this->success(self::cancel_session($sessionid));

                case 'get_next_training_session_index':
                    $trainingid = $this->get_param('trainingname', PARAM_INT);
                    return $this->success(self::get_next_training_session_index($trainingid));

                case 'get_available_sessions_csv_data':
                    $entityid = $this->get_param('entityid', PARAM_INT);
                    return $this->success(self::get_available_sessions_csv_data($entityid));

                default:
                    $sessionid = $this->get_param('sessionid', PARAM_INT);
                    return $this->success($this->$action($sessionid));

            }
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Get a session by id
     *
     * @param int $sessionid
     * @return session
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function get_session($sessionid) {
        return session_api::get_session($sessionid);
    }

    /**
     * Delete a session
     *
     * @param int $sessionid
     * @return bool
     * @throws \required_capability_exception
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function delete_session($sessionid) {
        $session = session_api::get_session($sessionid);
        return $session->delete();
    }

    /**
     * Move a session into an other entity
     *
     * @param int $sessionid
     * @param int $destinationentity
     * @return bool
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function move_session($sessionid, $destinationentity) {
        return session_api::move_session($sessionid, $destinationentity);
    }

    /**
     * Cancel a session
     *
     * @param int $sessionid
     * @return bool
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function cancel_session($sessionid) {
        return session_api::cancel_session($sessionid);
    }

    /**
     * Create a session
     *
     * @param int $trainingid
     * @param string $sessionname
     * @param int $entityid
     * @return bool|session
     * @throws Exception
     */
    public static function create_session($trainingid, $sessionname, $entityid = 0) {
        return session_api::create_session($trainingid, $sessionname, false, $entityid);
    }

    /**
     * Update a session
     *
     * @param stdClass $data
     * @param session_form $mform
     * @return mixed
     * @throws Exception
     */
    public static function update_session($data, $mform) {
        return session_api::update_session($data, $mform);
    }

    /**
     * Get all entity sessions
     *
     * @param stdClass $data
     * @return array|mixed
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function get_sessions_by_entity($data) {
        return session_api::get_sessions_by_entity($data);
    }

    /**
     * Count session record by entity id
     *
     * @param $data
     * @return int
     * @throws dml_exception
     */
    public static function count_sessions_by_entity_id($data) {
        return session_api::count_sessions_by_entity_id($data);
    }

    /**
     * Count all session record
     *
     * @param \stdClass $data
     * @return int
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function count_session_record($data) {
        return session_api::count_session_record($data);
    }

    /**
     * Count session record link with id entity
     *
     * @param int $trainingid
     * @return string
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function get_next_training_session_index($trainingid) {
        return session_api::get_next_training_session_index($trainingid);
    }

    /**
     * Get all available sessions catalog by entity for a csv file
     *
     * @param $entityid
     * @return array
     */
    public static function get_available_sessions_csv_data($entityid) {
        return local_mentor_core_get_available_sessions_csv_data($entityid);
    }
}
