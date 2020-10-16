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

$id = required_param('id', PARAM_INT);
$query = optional_param('q', '', PARAM_TEXT);
$spage = optional_param('spage', 0, PARAM_INT);

require_login();

$blockinstance = $DB->get_record('block_instances', array('id' => $id), '*', MUST_EXIST);
$context = context_block::instance($id);
require_capability('block/custom_register:viewreport', $context);


$PAGE->set_context($context);
$PAGE->set_url('/blocks/custom_register/report.php');
$PAGE->set_pagelayout('report');
$PAGE->set_heading(get_string('pluginname', 'block_custom_register'));
$PAGE->set_title(get_string('pluginname', 'block_custom_register'));

echo $OUTPUT->header();

$amount = 50;
$select = '';
$params = array('instanceid' => $id);

if (!empty($query)) {
    $q = trim($query);
    $q = str_replace(' ', '%', $q);
    $q = '%' . $q . '%';
    $select = "WHERE (d.customdata LIKE :query1 OR d.relation LIKE :query2 OR j.customdata LIKE :query3)";
    $params['query1'] = $q;
    $params['query2'] = $q;
    $params['query3'] = $q;
}

$sql = "SELECT d.id, d.relation, d.customdata, d.timecreated, j.customdata AS writedata
            FROM {block_custom_register_data} AS d
            INNER JOIN {block_custom_register_join} AS j ON j.relation = d.relation AND d.instanceid = :instanceid
            " . $select .
            " ORDER BY d.relation ASC";
$records = $DB->get_records_sql($sql, $params, $spage * $amount, $amount);

$sql = "SELECT COUNT(1)
            FROM {block_custom_register_data} AS d
            INNER JOIN {block_custom_register_join} AS j ON j.relation = d.relation AND d.instanceid = :instanceid
            " . $select;
$count = $DB->count_records_sql($sql, $params);

$pagingbar = new paging_bar($count, $spage, $amount, "/blocks/custom_register/report.php?q={$query}&amp;id={$id}");
$pagingbar->pagevar = 'spage';

$renderable = new \block_custom_register\output\report($records, $query);
$renderer = $PAGE->get_renderer('block_custom_register');

echo $renderer->render($renderable);

echo $OUTPUT->render($pagingbar);

echo $OUTPUT->footer();