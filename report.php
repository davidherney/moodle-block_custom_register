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

require_login();

$syscontext = context_system::instance();

$PAGE->set_context($syscontext);
$PAGE->set_url('/blocks/custom_register/report.php');
$PAGE->set_pagelayout('report');
$PAGE->set_heading(get_string('pluginname', 'block_custom_register'));
$PAGE->set_title(get_string('pluginname', 'block_custom_register'));

echo $OUTPUT->header();

$renderable = new \block_custom_register\output\report();
$renderer = $PAGE->get_renderer('block_custom_register');

echo $renderer->render($renderable);


echo $OUTPUT->footer();