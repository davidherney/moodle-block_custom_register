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

require_once('../../config.php');
require_once 'locallib.php';

$id = required_param('id', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$query = optional_param('q', '', PARAM_TEXT);
$spage = optional_param('spage', 0, PARAM_INT);
$format = optional_param('format', '', PARAM_ALPHA);

// Determine current course.
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

require_login($course, false);

$blockinstance = $DB->get_record('block_instances', ['id' => $id], '*', MUST_EXIST);
$context = context_block::instance($id);
require_capability('block/custom_register:viewreport', $context);

$baseurl = new moodle_url('/blocks/custom_register/report.php', ['q' => $query, 'spage' => $spage,
                                                                'id' => $id, 'courseid' => $courseid]);

// Extract configdata.
$config = unserialize(base64_decode($blockinstance->configdata));

$amount = 20;
$select = 'WHERE d.instanceid = :instanceid';
$params = ['instanceid' => $id];

if (!empty($config->joinfield)) {
    $select .= ' AND j.type = :type';
    $params['type'] = $config->type;
}

if ($format) {
    $amount = 0;
}

if (!empty($query)) {
    $q = trim($query);
    $q = str_replace(' ', '%', $q);
    $q = '%' . $q . '%';

    if (!empty($config->joinfield)) {
        $select .= " AND (d.customdata LIKE :query1 OR d.relation LIKE :query2 OR j.customdata LIKE :query3)";
    } else {
        $select .= " AND (d.customdata LIKE :query1 OR d.relation LIKE :query2)";
    }

    $params['query1'] = $q;
    $params['query2'] = $q;
    $params['query3'] = $q;
}

if (!empty($config->joinfield)) {
    $sql = "SELECT d.id, d.relation, d.customdata, d.timecreated, j.customdata AS writedata
                FROM {block_custom_register_data} AS d
                INNER JOIN {block_custom_register_join} AS j ON j.relation = d.relation " . $select .
                " ORDER BY d.relation ASC";

    $sqlcount = "SELECT COUNT(1)
                FROM {block_custom_register_data} AS d
                INNER JOIN {block_custom_register_join} AS j ON j.relation = d.relation " . $select;
} else {

    $sql = "SELECT d.id, d.relation, d.customdata, d.timecreated, NULL AS writedata
                FROM {block_custom_register_data} AS d
                " . $select .
                " ORDER BY d.timecreated DESC";

    $sqlcount = "SELECT COUNT(1)
                FROM {block_custom_register_data} AS d
                " . $select;
}

$records = $DB->get_records_sql($sql, $params, $spage * $amount, $amount);
$count = $DB->count_records_sql($sqlcount, $params);

$fields = ['timecreated' => get_string('timecreated', 'block_custom_register')];

$rows = [];
$exportrows = [];

foreach ($records as $record) {

    if ($record->customdata === null) {
        $record->customdata = '{}';
    }
    $customdata = json_decode($record->customdata);
    $customdata = (array)$customdata;

    if ($record->writedata === null) {
        $record->writedata = '{}';
    }

    $writedata = json_decode($record->writedata, true);
    $writedata = (array)$writedata;

    $row = new \stdClass();
    $row->timecreated = userdate($record->timecreated);

    foreach ($customdata as $field => $one) {
        $fields[$field] = $field;
        $row->$field = $one;
    }

    foreach ($writedata as $field => $one) {
        $fields[$field] = $field;
        $row->$field = $one;
    }

    $k = new \stdClass();
    $k->values = array_values((array)$row);
    $rows[] = $k;

    $exportrows[] = $row;
}

// Only download data.
if ($format) {

    switch ($format) {
        case 'csv' : usersgrades_download_csv($fields, $exportrows);
        case 'ods' : usersgrades_download_ods($fields, $exportrows);
        case 'xls' : usersgrades_download_xls($fields, $exportrows);

    }
    die;
}
// End download data.

$PAGE->set_context($context);
$PAGE->set_url('/blocks/custom_register/report.php', ['q' => $query, 'spage' => $spage, 'id' => $id, 'courseid' => $courseid]);
$PAGE->set_pagelayout('report');
$PAGE->set_heading(get_string('pluginname', 'block_custom_register'));
$PAGE->set_title(get_string('pluginname', 'block_custom_register'));

echo $OUTPUT->header();

$pagingbar = new paging_bar($count, $spage, $amount,
                            "/blocks/custom_register/report.php?q={$query}&amp;id={$id}&amp;courseid={$courseid}");
$pagingbar->pagevar = 'spage';

$renderable = new \block_custom_register\output\report($id, $courseid, $rows, $fields, $query, $count);
$renderer = $PAGE->get_renderer('block_custom_register');

echo $renderer->render($renderable);

echo $OUTPUT->render($pagingbar);


// Download form.
echo $OUTPUT->heading(get_string('download', 'admin'), 4);

echo $OUTPUT->box_start();
echo '<ul>';
echo '    <li><a href="' . $baseurl . '&format=csv">'.get_string('downloadtext').'</a></li>';
echo '    <li><a href="' . $baseurl . '&format=ods">'.get_string('downloadods').'</a></li>';
echo '    <li><a href="' . $baseurl . '&format=xls">'.get_string('downloadexcel').'</a></li>';
echo '</ul>';
echo $OUTPUT->box_end();

echo $OUTPUT->footer();
