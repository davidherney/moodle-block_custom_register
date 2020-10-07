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
 * External functions and service definitions.
 *
 * @package   block_custom_register
 * @copyright 2020 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = array(
    'block_custom_register_save' => array(
        'classname' => 'block_custom_register_external',
        'methodname' => 'save',
        'classpath' => 'blocks/custom_register/externallib.php',
        'description' => 'Save data',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => false
    ),
);

$services = array(
        'Custom register webservices' => array(
                'functions' => array('block_custom_register_save'),
                'restrictedusers' => 0, // if 1, the administrator must manually select which user can use this service.
                // (Administration > Plugins > Web services > Manage services > Authorised users)
                'enabled' => 0, // if 0, then token linked to this service won't work
                'shortname' => 'block_custom_register_ws'
        )
);
