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
 * Database upgrades for the session local.
 *
 * @package   local_session
 * @copyright  2020 Edunao SAS (contact@edunao.com)
 * @author    Nabil HAMDI <nabil.hamdi@edunao.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

/**
 * Upgrade the local_session database.
 *
 * @param int $oldversion The version number of the plugin that was installed.
 * @return boolean
 */
function xmldb_local_session_upgrade($oldversion) {

    return true;
}
