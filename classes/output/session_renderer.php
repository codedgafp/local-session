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
 * Session admin renderer
 *
 * @package    local_session
 * @copyright  2020 Edunao SAS (contact@edunao.com)
 * @author     remi <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_session\output;

use format_edadmin\output\edadmin_renderer;
use local_mentor_core\entity_api;
use local_mentor_core\session_api;
use local_mentor_core\training;
use moodle_exception;

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/local/session/lib.php');
require_once($CFG->dirroot . '/enrol/editinstance_form.php');

require_login();

class session_renderer extends \plugin_renderer_base implements edadmin_renderer {

    protected $dbinterface;

    /**
     * First enter to render
     *
     * @param $course
     * @param $section
     * @return string
     * @throws moodle_exception
     */
    public function display($course, $section) {
        // Initialize params to JS and template.
        $params = new \stdClass();
        $params->entityid = $course->category;

        $currententity = entity_api::get_entity($course->category);
        $trainingadmincourse = $currententity->get_edadmin_courses('trainings');
        $sessionscategoryid = $currententity->get_entity_session_category();
        $params->trainingadmincourselink = $trainingadmincourse['link'];
        $filterttrainingid = optional_param('trainingid', null, PARAM_INT);

        // Set filter name.
        if ($filterttrainingid) {
            $params->filtertrainingname = ($filterttrainingid) ? (new training($filterttrainingid))->courseshortname : null;
        }

        $mainentity = $currententity->get_main_entity();

        $params->mainentityshortname = $mainentity->shortname;

        // Check permission to export the catalog as PDF.
        $params->exportcatalogpdf = false;
        if (has_capability('local/entities:manageentity', $mainentity->get_context())) {
            $params->exportcatalogpdf = $mainentity->count_available_sessions_to_catalog() > 0 ? true : false;
        }

        // Check permission to export the catalog as CSV.
        $params->exportcatalogcsv = false;
        if (has_capability('local/session:create', $mainentity->get_context())) {
            $params->exportcatalogcsv = true;
        }

        $subentities = array_values($mainentity->get_sub_entities());

        // Move sessions.
        if (has_capability('local/mentor_core:movesessions', $mainentity->get_context())) {

            // Add main entity.
            if (has_capability('local/session:create', $mainentity->get_context())) {
                $ent = new \stdClass();
                $ent->id = $mainentity->id;
                $ent->name = $mainentity->shortname;
                $params->moveentities[] = $ent;

                $params->subentities = $subentities;
            }

            // Add subentities.
            foreach ($subentities as $subentity) {

                if (!has_capability('local/session:create', $subentity->get_context())) {
                    continue;
                }
                $ent = new \stdClass();
                $ent->id = $subentity->id;
                $ent->name = $subentity->name;
                $ent->issubentity = true;

                $params->moveentities[] = $ent;
            }
        }

        // Move in other entities.
        if (has_capability('local/mentor_core:movesessionsinotherentities', $mainentity->get_context())) {

            // Main admin can see hidden entities.
            $includehidden = is_siteadmin();
            $entities = entity_api::get_all_entities(true, [], false, null, $includehidden);

            foreach ($entities as $entity) {
                // Do not add current entity.
                if ($entity->id == $currententity->id) {
                    continue;
                }

                $ent = new \stdClass();
                $ent->id = $entity->id;
                $ent->name = $entity->shortname;

                $params->entities[] = $ent;

                if (has_capability('local/session:create', $entity->get_context())) {
                    $params->moveentities[] = $ent;
                }
            }
        }

        $params->subentitiesfilter = [];

        // Add subentities filter.
        foreach ($subentities as $subentity) {

            if (!$subentity->is_sessions_manager()) {
                continue;
            }
            $ent = new \stdClass();
            $ent->id = $subentity->id;
            $ent->name = $subentity->name;
            $ent->issubentity = true;

            $params->subentitiesfilter[] = $ent;
        }

        // Load language strings for JavaScript.
        $this->page->requires->strings_for_js(
            [
                'cancelsessiondialogtitle',
                'cancelsessiondialogcontent',
                'langfile',
                'wordingsession',
                'movesessiondialogtitle',
                'deletesessiondialogtitle',
                'move',
                'lifecycle',
            ],
            'local_session'
        );

        $this->page->requires->strings_for_js([
            'cancel',
            'confirm',
        ],
            'format_edadmin'
        );

        $this->page->requires->strings_for_js([
            'choose',
        ],
            'local_mentor_core'
        );

        // Get the right JS to use and call it.
        $js = session_api::get_session_javascript('local_session/local_session');
        $jsparams = new \stdClass();
        $jsparams->entityid = $params->entityid;
        $jsparams->exportcatalogpdf = $params->exportcatalogpdf;
        $jsparams->exportcatalogcsv = $params->exportcatalogcsv;
        $jsparams->mainentityshortname = $params->mainentityshortname;
        if (isset($params->filtertrainingname)) {
            $jsparams->filtertrainingname = $params->filtertrainingname;
        }
        $this->page->requires->js_call_amd($js, 'init', [$jsparams]);

        // Check if the user can view the category recycle bin.
        $sessioncategorycontext = \context_coursecat::instance($sessionscategoryid);

        // Add recyclebin link.
        if (has_capability('tool/recyclebin:viewitems', $sessioncategorycontext)) {
            $params->recyclebinlink = new \moodle_url('/local/session/pages/recyclebin_sessions.php', [
                'entityid' => $currententity->id,
            ]);
        }

        $params = \local_mentor_core\session_api::get_session_template_params($params);

        // Get the right session table template.
        $template = session_api::get_session_template('local_session/local_session');

        // Return template renderer.
        return $this->render_from_template($template, $params);
    }
}
