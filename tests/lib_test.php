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
 * Session lib tests
 *
 * @package    local_session
 * @copyright  2023 Edunao SAS (contact@edunao.com)
 * @author     rcolet <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\navigation\views\secondary;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/local/session/lib.php');

class local_session_lib_testcase extends advanced_testcase {

    /**
     * local_session_extend_settings_navigation in course category
     *
     * @covers ::local_session_extend_settings_navigation
     */
    public function test_local_session_extend_settings_navigation_not_ok_in_course_category() {
        global $PAGE;

        $this->resetAfterTest(true);

        // Create course category.
        $coursecategorie = self::getDataGenerator()->create_category();
        $context = \context_coursecat::instance($coursecategorie->id);

        // Set PAGE.
        $PAGE->set_context($context);
        self::assertFalse($PAGE->settingsnav->find('courseadmin', navigation_node::TYPE_COURSE));

        self::resetAllData();
    }

    /**
     * local_session_extend_settings_navigation in course ONE.
     *
     * @covers ::local_session_extend_settings_navigation
     */
    public function test_local_session_extend_settings_navigation_not_ok_in_course_one() {
        global $PAGE;

        $this->resetAfterTest(true);

        // Get course one context.
        $context = \context_course::instance(1);

        // Set PAGE.
        $PAGE->set_context($context);
        $PAGE->set_url('/');
        self::assertFalse($PAGE->settingsnav->find('courseadmin', navigation_node::TYPE_COURSE));

        self::resetAllData();
    }

    /**
     * local_session_extend_settings_navigation no update session capability.
     *
     * @covers ::local_session_extend_settings_navigation
     */
    public function test_local_session_extend_settings_navigation_not_ok_no_update_session_capability() {
        global $PAGE;

        $this->resetAfterTest(true);

        // Get course one context.
        $course = self::getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);

        $user = self::getDataGenerator()->create_user();
        self::getDataGenerator()->enrol_user($user->id, $course->id, 'participant');

        // Set PAGE.
        $PAGE->set_context($context);
        $PAGE->set_course($course);
        $PAGE->set_url('/course/view.php', ['id' => $course->id]);
        self::assertFalse($PAGE->settingsnav->find('courseadmin', navigation_node::TYPE_COURSE));

        self::resetAllData();
    }

    /**
     * local_session_extend_settings_navigation no session.
     *
     * @covers ::local_session_extend_settings_navigation
     */
    public function test_local_session_extend_settings_navigation_not_ok_no_session() {
        global $PAGE;

        $this->resetAfterTest(true);

        // Create category.
        $catgeory = self::getDataGenerator()->create_category();

        // Create course to category.
        $course = self::getDataGenerator()->create_course(['category' => $catgeory->id]);

        // Get contexts.
        $context = \context_course::instance($course->id);
        $contextcategory = \context_coursecat::instance($course->category);

        // Assign "admindedie" role to user in the category.
        $user = self::getDataGenerator()->create_user();
        self::getDataGenerator()->role_assign('admindedie', $user->id, $contextcategory->id);

        // Set PAGE.
        self::setUser($user->id);
        $PAGE->set_context($context);
        $PAGE->set_course($course);
        $PAGE->set_url('/course/view.php', ['id' => $course->id]);
        $listelink = $PAGE->settingsnav->find('courseadmin', navigation_node::TYPE_COURSE)->get_children_key_list();

        self::assertTrue(in_array('editsettings', $listelink));
        self::assertTrue(in_array('users', $listelink));
        self::assertTrue(in_array('filtermanagement', $listelink));
        self::assertTrue(in_array('coursereports', $listelink));
        self::assertTrue(in_array('gradebooksetup', $listelink));
        self::assertTrue(in_array('coursebadges', $listelink));
        self::assertTrue(in_array('import', $listelink));
        self::assertTrue(in_array('backup', $listelink));
        self::assertTrue(in_array('restore', $listelink));
        self::assertTrue(in_array('copy', $listelink));
        self::assertTrue(in_array('reset', $listelink));
        self::assertTrue(in_array('questionbank', $listelink));

        self::assertFalse(in_array('training', $listelink));
        self::assertFalse(in_array('content_bank', $listelink));
        self::assertFalse(in_array('enrolled_users', $listelink));
        self::assertFalse(in_array('enroll_users', $listelink));
        self::assertFalse(in_array('course_activities', $listelink));
        self::assertFalse(in_array('training_completion_report', $listelink));
        self::assertFalse(in_array('activities_completion_report', $listelink));
        self::assertFalse(in_array('group', $listelink));
        self::assertFalse(in_array('session_to_training', $listelink));

        // Set navigation node.
        $settingsnav = new \navigation_node(['text' => 'navigation test']);

        try {
            // Call function to be tested.
            self::setUser($user);
            local_session_extend_settings_navigation($settingsnav, $context);
        } catch (\Exception $e) {
            self::fail($e->getMessage());
        }

        // Navigation node has not changed.
        self::assertSame($settingsnav, $settingsnav);

        self::resetAllData();
    }

    /**
     * local_session_extend_settings_navigation ok.
     *
     * @covers ::local_session_extend_settings_navigation
     */
    public function test_local_session_extend_settings_navigation_ok() {
        global $PAGE, $DB;

        $this->resetAfterTest(true);

        // Create category.
        $catgeory = self::getDataGenerator()->create_category();

        // Create course to category.
        $course = self::getDataGenerator()->create_course(['category' => $catgeory->id]);

        // Get contexts.
        $context = \context_course::instance($course->id);
        $contextcategory = \context_coursecat::instance($course->category);

        // Assign "admindedie" role to user in the category.
        $user = self::getDataGenerator()->create_user();
        self::getDataGenerator()->role_assign('admindedie', $user->id, $contextcategory->id);

        // Create false training to database.
        $trainingid = $DB->insert_record(
            'training',
            [
                'courseshortname' => 'falsetraining',
            ]
        );

        // Create false session to database.
        $DB->insert_record(
            'session',
            [
                'courseshortname' => $course->shortname,
                'trainingid' => $trainingid,
            ]
        );

        // Set PAGE.
        self::setUser($user);
        $PAGE->set_context($context);
        $PAGE->set_course($course);
        $PAGE->set_url(new moodle_url('/course/view.php', ['id' => $course->id]));
        $listelink = $PAGE->settingsnav->find('courseadmin', navigation_node::TYPE_COURSE)->get_children_key_list();

        self::assertTrue(in_array('editsettings', $listelink));
        self::assertTrue(in_array('users', $listelink));
        self::assertTrue(in_array('filtermanagement', $listelink));
        self::assertTrue(in_array('coursereports', $listelink));
        self::assertTrue(in_array('gradebooksetup', $listelink));
        self::assertTrue(in_array('coursebadges', $listelink));
        self::assertTrue(in_array('import', $listelink));
        self::assertTrue(in_array('backup', $listelink));
        self::assertTrue(in_array('restore', $listelink));
        self::assertTrue(in_array('copy', $listelink));
        self::assertTrue(in_array('reset', $listelink));
        self::assertTrue(in_array('questionbank', $listelink));

        self::assertTrue(in_array('training', $listelink));
        self::assertTrue(in_array('content_bank', $listelink));
        self::assertTrue(in_array('enrolled_users', $listelink));
        self::assertTrue(in_array('enroll_users', $listelink));
        self::assertTrue(in_array('course_activities', $listelink));
        self::assertTrue(in_array('training_completion_report', $listelink));
        self::assertTrue(in_array('activities_completion_report', $listelink));
        self::assertTrue(in_array('group', $listelink));
        self::assertTrue(in_array('session_to_training', $listelink));

        self::resetAllData();
    }
}
