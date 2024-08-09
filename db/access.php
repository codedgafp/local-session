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
 * session local caps.
 *
 * @package    local_trainings
 * @copyright  2020 Edunao SAS (contact@edunao.com)
 * @author     Nabil Hamdi <nabil.hamdi@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [

    'local/session:manage' => [
        'riskbitmask' => RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],

        'clonepermissionsfrom' => 'moodle/category:manage',
    ],

    'local/session:view' => [
        'riskbitmask' => RISK_XSS,

        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],

        'clonepermissionsfrom' => 'moodle/course:create',
    ],

    'local/session:create' => [
        'riskbitmask' => RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],

        'clonepermissionsfrom' => 'moodle/course:create',
    ],
    'local/session:createinsubentity' => [
        'riskbitmask' => RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],

        'clonepermissionsfrom' => 'moodle/course:create',
    ],

    'local/session:update' => [
        'riskbitmask' => RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
        ],

        'clonepermissionsfrom' => 'moodle/course:update',
    ],

    'local/session:delete' => [
        'riskbitmask' => RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],

        'clonepermissionsfrom' => 'moodle/course:delete',
    ],

    'local/session:changefullname' => [

        'riskbitmask' => RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'moodle/course:update',
    ],

    'local/session:changeshortname' => [

        'riskbitmask' => RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'moodle/course:update',
    ],
];
