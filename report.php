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
 * Report page for custom_register block.
 *
 * @package    block_custom_register
 * @copyright  2020 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('locallib.php');

$id = required_param('id', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$query = optional_param('q', '', PARAM_TEXT);
$spage = optional_param('spage', 0, PARAM_INT);
$format = optional_param('format', '', PARAM_ALPHA);
$action = optional_param('action', '', PARAM_ALPHA);
$recordid = optional_param('recordid', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$selectedids = optional_param_array('selectedids', [], PARAM_INT);
$selectedidslist = optional_param('selectedidslist', '', PARAM_SEQUENCE);

// Determine current course.
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

require_login($course, false);

$blockinstance = $DB->get_record('block_instances', ['id' => $id], '*', MUST_EXIST);
$context = context_block::instance($id);
require_capability('block/custom_register:viewreport', $context);

$baseurl = new moodle_url(
    '/blocks/custom_register/report.php',
    ['q' => $query, 'spage' => $spage, 'id' => $id, 'courseid' => $courseid]
);

// Delete one, several, or every record belonging to this block instance.
if ($action === 'delete' || $action === 'deleteselected' || $action === 'deleteall') {
    require_capability('block/custom_register:deleteregisters', $context);

    $returnurl = new moodle_url('/blocks/custom_register/report.php', [
        'q' => $query,
        'spage' => $spage,
        'id' => $id,
        'courseid' => $courseid,
    ]);

    if ($action === 'delete') {
        $record = $DB->get_record('block_custom_register_data', [
            'id' => $recordid,
            'instanceid' => $id,
        ], 'id', MUST_EXIST);
        $confirmmessage = get_string('confirmdeleteregister', 'block_custom_register');
    } else if ($action === 'deleteselected') {
        require_sesskey();

        if ($confirm && $selectedidslist !== '') {
            $selectedids = array_map('intval', explode(',', $selectedidslist));
        }
        $selectedids = array_values(array_unique(array_filter($selectedids)));

        if (empty($selectedids)) {
            redirect(
                $returnurl,
                get_string('noselectedregisters', 'block_custom_register'),
                null,
                \core\output\notification::NOTIFY_WARNING
            );
        }

        [$insql, $inparams] = $DB->get_in_or_equal($selectedids, SQL_PARAMS_NAMED, 'selected');
        $selectionparams = ['selectedinstanceid' => $id] + $inparams;
        $validrecords = $DB->get_records_select(
            'block_custom_register_data',
            "instanceid = :selectedinstanceid AND id {$insql}",
            $selectionparams,
            '',
            'id'
        );
        $selectedids = array_keys($validrecords);

        if (empty($selectedids)) {
            redirect(
                $returnurl,
                get_string('noselectedregisters', 'block_custom_register'),
                null,
                \core\output\notification::NOTIFY_WARNING
            );
        }

        $confirmmessage = get_string('confirmdeleteselectedregisters', 'block_custom_register', count($selectedids));
    } else {
        $confirmmessage = get_string('confirmdeleteallregisters', 'block_custom_register');
    }

    if ($confirm) {
        require_sesskey();

        if ($action === 'delete') {
            $DB->delete_records('block_custom_register_data', ['id' => $record->id, 'instanceid' => $id]);
            $message = get_string('registerdeleted', 'block_custom_register');
        } else if ($action === 'deleteselected') {
            [$insql, $inparams] = $DB->get_in_or_equal($selectedids, SQL_PARAMS_NAMED, 'selecteddelete');
            $deleteparams = ['selectedinstanceid' => $id] + $inparams;
            $DB->delete_records_select(
                'block_custom_register_data',
                "instanceid = :selectedinstanceid AND id {$insql}",
                $deleteparams
            );
            $message = get_string('selectedregistersdeleted', 'block_custom_register', count($selectedids));
        } else {
            $DB->delete_records('block_custom_register_data', ['instanceid' => $id]);
            $message = get_string('allregistersdeleted', 'block_custom_register');
        }

        redirect($returnurl, $message, null, \core\output\notification::NOTIFY_SUCCESS);
    }

    $confirmurl = new moodle_url('/blocks/custom_register/report.php', [
        'action' => $action,
        'recordid' => $recordid,
        'selectedidslist' => implode(',', $selectedids),
        'confirm' => 1,
        'sesskey' => sesskey(),
        'q' => $query,
        'spage' => $spage,
        'id' => $id,
        'courseid' => $courseid,
    ]);

    $PAGE->set_context($context);
    $PAGE->set_url($returnurl);
    $PAGE->set_pagelayout('report');
    $PAGE->set_heading(get_string('pluginname', 'block_custom_register'));
    $PAGE->set_title(get_string('pluginname', 'block_custom_register'));

    echo $OUTPUT->header();
    echo $OUTPUT->confirm($confirmmessage, $confirmurl, $returnurl);
    echo $OUTPUT->footer();
    exit;
}

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
                FROM {block_custom_register_data} d
                INNER JOIN {block_custom_register_join} j ON j.relation = d.relation " . $select .
                " ORDER BY d.relation ASC";

    $sqlcount = "SELECT COUNT(1)
                FROM {block_custom_register_data} d
                INNER JOIN {block_custom_register_join} j ON j.relation = d.relation " . $select;
} else {
    $sql = "SELECT d.id, d.relation, d.customdata, d.timecreated, NULL AS writedata FROM {block_custom_register_data} d " .
        $select .
        " ORDER BY d.timecreated DESC";

    $sqlcount = "SELECT COUNT(1) FROM {block_custom_register_data} d " . $select;
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
    $k->id = $record->id;
    $k->deleteurl = (new moodle_url('/blocks/custom_register/report.php', [
        'action' => 'delete',
        'recordid' => $record->id,
        'q' => $query,
        'spage' => $spage,
        'id' => $id,
        'courseid' => $courseid,
    ]))->out(false);
    $k->values = array_values((array)$row);
    $rows[] = $k;

    $exportrows[] = $row;
}

// Only download data.
if ($format) {
    switch ($format) {
        case 'ods':
            usersgrades_download_ods($fields, $exportrows);
            break;
        case 'xls':
            usersgrades_download_xls($fields, $exportrows);
            break;
        default:
            usersgrades_download_csv($fields, $exportrows);
            break;
    }
    die;
}
// End download data.

$PAGE->set_context($context);
$PAGE->set_url('/blocks/custom_register/report.php', ['q' => $query, 'spage' => $spage, 'id' => $id, 'courseid' => $courseid]);
$PAGE->set_pagelayout('report');
$PAGE->set_heading(get_string('pluginname', 'block_custom_register'));
$PAGE->set_title(get_string('pluginname', 'block_custom_register'));
$PAGE->requires->js_call_amd('block_custom_register/report', 'init');

echo $OUTPUT->header();

$pagingurl = new moodle_url('/blocks/custom_register/report.php', [
    'q' => $query,
    'id' => $id,
    'courseid' => $courseid,
]);
$pagingbar = new paging_bar($count, $spage, $amount, $pagingurl);
$pagingbar->pagevar = 'spage';

$candelete = has_capability('block/custom_register:deleteregisters', $context);
$pagingbarhtml = $OUTPUT->render($pagingbar);
$renderable = new \block_custom_register\output\report(
    $id,
    $courseid,
    $rows,
    $fields,
    $query,
    $count,
    $candelete,
    $pagingbarhtml
);
$renderer = $PAGE->get_renderer('block_custom_register');

echo $renderer->render($renderable);

// Download form.
echo $OUTPUT->heading(get_string('download', 'admin'), 4);

echo $OUTPUT->box_start();
echo '<ul>';
echo '    <li><a href="' . $baseurl . '&format=csv">' . get_string('downloadtext') . '</a></li>';
echo '    <li><a href="' . $baseurl . '&format=ods">' . get_string('downloadods') . '</a></li>';
echo '    <li><a href="' . $baseurl . '&format=xls">' . get_string('downloadexcel') . '</a></li>';
echo '</ul>';
echo $OUTPUT->box_end();

echo $OUTPUT->footer();
