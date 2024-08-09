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
 * Session recycle bin renderer
 *
 * @package    local_session
 * @copyright  2020 Edunao SAS (contact@edunao.com)
 * @author     Rémi Colet <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_session\output;

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/local/user/lib.php');
require_once($CFG->dirroot . '/local/mentor_core/api/entity.php');

// Require login.
require_login();

use local_mentor_core\entity;
use confirm_action;
use pix_icon;

class recyclebin_renderer extends \plugin_renderer_base {

    protected $dbinterface;

    /**
     * First enter to render
     *
     * @param entity $entity
     * @param array $items
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function display($entity, $items) {
        // Call entity selector to session recycle bin page template.
        $this->page->requires->string_for_js('pleaserefresh', 'format_edadmin');
        $this->page->requires->js_call_amd('format_edadmin/format_edadmin', 'selectEntity');

        $listentityselect = \local_mentor_core\session_api::entity_selector_sessions_recyclebin($entity->id);
        if (!empty($listentityselect)) {
            echo $this->output->render_from_template(
                'format_edadmin/entity_select',
                \local_mentor_core\session_api::entity_selector_sessions_recyclebin($entity->id)
            );
        }

        // Get the expiry to use later.
        $expiry = get_config('tool_recyclebin', 'categorybinexpiry');

        // Start with a description.
        if ($expiry > 0) {
            $expirydisplay = format_time($expiry);
            echo '<div class=\'alert alert-info\'>' . get_string('deleteexpirywarning', 'tool_recyclebin', $expirydisplay) .
                 '</div>';
        }

        $this->page->requires->strings_for_js(['deleteallconfirm', 'deleteconfirm'], 'tool_recyclebin');

        $table = $this->create_recyclebin_table($items, $this->check_if_sub_entity_exist($items));

        $table->finish_output();
    }

    /**
     * Create entity's recycle bin table
     *
     * @param array $items
     * @param bool $hassubentity
     * @return \flexible_table
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    private function create_recyclebin_table($items, $hassubentity) {

        // Define columns and headers.
        if ($hassubentity) {
            $columns = [
                'subentity',
                'sessionname',
                'date',
                'restore',
                'delete',
            ];

            $headers = [
                get_string('subentity', 'local_mentor_core'),
                get_string('sessionname', 'local_mentor_core'),
                get_string('datedeleted', 'tool_recyclebin'),
                get_string('restore'),
                get_string('delete'),
            ];
        } else {
            $columns = [
                'sessionname',
                'date',
                'restore',
                'delete',
            ];

            $headers = [
                get_string('sessionname', 'local_mentor_core'),
                get_string('datedeleted', 'tool_recyclebin'),
                get_string('restore'),
                get_string('delete'),
            ];
        }

        // Define a table.
        $table = new \flexible_table('recyclebin');
        $table->define_columns($columns);
        $table->column_style('restore', 'text-align', 'center');
        $table->column_style('delete', 'text-align', 'center');
        $table->define_headers($headers);
        $table->define_baseurl($this->page->url);
        $table->set_attribute('id', 'recycle-bin-table');
        $table->setup();

        foreach ($items as $item) {

            $row = [];

            if ($hassubentity) {
                $row[] = $item->entity;
            }

            // Item name row.
            $row[] = $item->name;

            // Time created item.
            $row[] = $item->timecreated;

            // Build restore link.
            $restoreurl = new \moodle_url(new \moodle_url('/local/session/pages/recyclebin_sessions.php', [
                'entityid' => $item->entityid,
            ]), [
                'contextid' => $item->contextid,
                'itemid' => $item->id,
                'action' => 'restore',
                'sesskey' => sesskey(),
            ]);

            // Add restore action to row.
            $row[] = $this->output->action_icon($restoreurl, new \pix_icon(
                    't/restore',
                    get_string('restore'),
                    '', [
                        'class' => 'iconsmall',
                    ]
                )
            );

            // Build delete link.
            $deleteurl = new \moodle_url(new \moodle_url('/local/session/pages/recyclebin_sessions.php', [
                'entityid' => $item->entityid,
            ]), [
                'contextid' => $item->contextid,
                'itemid' => $item->id,
                'action' => 'delete',
                'sesskey' => sesskey(),
            ]);
            $deleteaction = new confirm_action(get_string('deleteconfirm', 'tool_recyclebin'));

            // Add delete action to row.
            $row[] = $this->output->action_icon($deleteurl, new pix_icon('t/delete', get_string('delete')), $deleteaction);

            // Add all element row to table.
            $table->add_data($row);
        }

        return $table;
    }

    /**
     * Check if in session list, sub entity exist.
     *
     * @param object[] $items
     * @return bool
     */
    public function check_if_sub_entity_exist($items) {
        foreach ($items as $item) {
            if (strlen($item->entity) > 0) {
                return true;
            }
        }

        return false;
    }
}
