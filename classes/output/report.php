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
 * Class containing renderers for the block.
 *
 * @package   block_custom_register
 * @copyright 2020 David Herney @ BambuCo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_custom_register\output;
defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;

/**
 * Class containing data for the block.
 *
 * @copyright 2020 David Herney @ BambuCo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report implements renderable, templatable {

    /**
     * Block instance configuration.
     * @var object
     */
    public $instanceconfig;

    /**
     * Block instance context.
     * @var object
     */
    public $context;

    /**
     * Block instance information.
     * @var object
     */
    public $instance;

    /**
     * @var array Records list to show.
     */
    private $records;

    /**
     * @var array Query to filter the courses list.
     */
    private $query = null;

    /**
     * @var array fields list.
     */
    private $fields;

    /**
     * @var int The total records.
     */
    private $total = 0;

    /**
     * @var int The block id.
     */
    private $id;

    /**
     * @var int The course id.
     */
    private $courseid;

    /**
     * Constructor.
     *
     * @param int $id The block instance id
     * @param int $courseid The course id
     * @param array $fields A fields list
     * @param string $query A query to filter the records list
     * @param int $total The total records
     */
    public function __construct($id, $courseid, $records = [], $fields = [], $query = '', $total = 0) {
        global $CFG;

        $this->records = $records;
        $this->query = $query;
        $this->fields = $fields;
        $this->total = $total;
        $this->id = $id;
        $this->courseid = $courseid;

    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return array Context variables for the template
     */
    public function export_for_template(renderer_base $output) {
        global $CFG;

        $defaultvariables = [
            'records' => $this->records,
            'fields' => array_values($this->fields),
            'baseurl' => $CFG->wwwroot,
            'query' => $this->query,
            'total' => $this->total,
            'id' => $this->id,
            'courseid' => $this->courseid,
        ];

        return $defaultvariables;
    }
}
