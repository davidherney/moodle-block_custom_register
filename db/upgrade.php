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
 * This file keeps track of upgrades to the block
 *
 * @package block_custom_register
 * @copyright 2020 David Herney @ BambuCo
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade code for the custom_register block.
 *
 * @param int $oldversion
 */
function xmldb_block_custom_register_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($oldversion < 2020100604) {

        // Define index (not unique) to be added.
        $table = new xmldb_table('block_custom_register_data');
        $index = new xmldb_index('relation', XMLDB_INDEX_NOTUNIQUE, array('relation'));

        // Conditionally launch add index.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index (not unique) to be added.
        $table = new xmldb_table('block_custom_register_join');
        $index = new xmldb_index('relation', XMLDB_INDEX_NOTUNIQUE, array('relation'));

        // Conditionally launch add index.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Main savepoint reached.
        upgrade_block_savepoint(true, 2020100604, 'custom_register', false);
    }

    return true;
}
