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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * External integration API
 *
 * @package   block_custom_register
 * @copyright 2020 David Herney @ BambuCo
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir . '/externallib.php';

class block_custom_register_external extends external_api {

    /**
     * To validade input parameters
     * @return external_function_parameters
     */
    public static function save_parameters() {
        return new external_function_parameters(
            array(
                'instanceid' => new external_value(PARAM_INT, 'Block instance id'),
                'formdata' => new external_value(PARAM_TEXT, 'Form data to register'),
            )
        );
    }

    public static function save($instanceid, $formdata) {
        global $DB, $USER;

        $res = new stdClass();
        $res->message = '';
        $res->success = false;

        $bi = $DB->get_record('block_instances', array('id' => $instanceid));

        if (!$bi) {
            $res->message = get_string('instancenotexist', 'block_custom_register');
            return $res;
        }

        $formdatadecode = json_decode($formdata);

        if (!$formdatadecode) {
            $res->message = get_string('databadformat', 'block_custom_register');
            return $res;
        }

        $config = unserialize(base64_decode($bi->configdata));

        $fields = $config->fields;
        $fields = explode("\n", $fields);

        $record = new stdClass();
        $withdata = false;
        foreach ($fields as $field) {
            $field = trim($field);
            $value = '';
            if (property_exists($formdatadecode, $field)) {
                $value = trim($formdatadecode->$field);
                $withdata = true;
            }
            $record->$field = $value;
        }

        if (!$withdata) {
            $res->message = get_string('notdata', 'block_custom_register');
            return $res;
        }

        // Check if a relation is required.
        $relation = null;
        if (!empty($config->type) && !empty($config->joinfield)) {

            $joinfield = trim($config->joinfield);

            if (!property_exists($record, $joinfield) || empty($record->$joinfield)) {
                $res->message = get_string('relationedempty', 'block_custom_register', $joinfield);
                return $res;
            }

            $params = [];
            $params['type'] = $config->type;
            $params['relation'] = $record->$joinfield;
            $exists = $DB->count_records('block_custom_register_join', $params);

            if ($exists == 0) {

                if (!empty($config->joinmessage)) {
                    $message = str_replace('{value}', $record->$joinfield, $config->joinmessage);
                    $res->message = s($message);
                    return $res;
                } else {
                    $a = new stdClass();
                    $a->field = $joinfield;
                    $a->value = $record->$joinfield;
                    $res->message = get_string('notrelationed', 'block_custom_register', $a);
                    return $res;
                }
            }

            $relation = $record->$joinfield;
        }

        // Check if the uniqueness is required.
        if (!empty($config->ukfield)) {

            $ukfield = trim($config->ukfield);
            $type = trim($config->type);

            if (!property_exists($record, $ukfield) || empty($record->$ukfield)) {
                $res->message = get_string('ukfieldempty', 'block_custom_register', $ukfield);
                return $res;
            }

            $params = [];
            $params['instance'] = $instanceid;
            $params['ukfield'] = '%' . $ukfield . '":"' . $record->$ukfield . '"%';

            $query = "SELECT COUNT(1) FROM {block_custom_register_data} WHERE instanceid = :instance AND customdata LIKE :ukfield";
            $exists = $DB->count_records_sql($query, $params);

            if ($exists > 0) {
                $res->message = get_string('ukfieldexist', 'block_custom_register', $ukfield);
                return $res;
            }
        }

        $params = [];
        $params['instanceid'] = $instanceid;
        $params['userid'] = $USER ? $USER->id : null;
        $params['relation'] = $relation;
        $params['customdata'] = json_encode($record);
        $params['timecreated'] = time();

        if ($DB->insert_record('block_custom_register_data', $params)) {
            $res->message = get_string('saved', 'block_custom_register');
            $res->success = true;
        } else {
            $res->message = get_string('canbesaved', 'block_custom_register');
        }

        return $res;
    }

    /**
     * Validate the return value
     * @return external_single_structure
     */
    public static function save_returns() {
        return new external_function_parameters(
            array(
                'message' => new external_value(PARAM_TEXT, 'Message to display'),
                'success' => new external_value(PARAM_BOOL, 'True if all ok')
            )
        );
    }
}
