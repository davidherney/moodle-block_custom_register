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
class main implements renderable, templatable {

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
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return array Context variables for the template
     */
    public function export_for_template(renderer_base $output) {
        global $OUTPUT, $PAGE;

        $id = 'block_custom_register_' . time();

        $PAGE->requires->string_for_js('bademail', 'block_custom_register');
        $PAGE->requires->string_for_js('fieldrequired', 'block_custom_register');

        $filteropt = new \stdClass;
        $filteropt->overflowdiv = true;
        $filteropt->noclean = true;

        $aftermessage = $this->instanceconfig->aftermessage;
        if (is_array($aftermessage)) {
            $aftermessage = format_text($aftermessage['text'], $aftermessage['format'], $filteropt);
        }

        $content = $this->instanceconfig->content;
        // rewrite url
        $content = file_rewrite_pluginfile_urls($content, 'pluginfile.php',
                                                $this->context->id, 'block_custom_register', 'content', NULL);
        // Default to FORMAT_HTML which is what will have been used before the
        // editor was properly implemented for the block.
        $format = FORMAT_HTML;
        // Check to see if the format has been properly set on the config
        if (isset($this->instanceconfig->format)) {
            $format = $this->instanceconfig->format;
        }
        $content = format_text($content, $format, $filteropt);

        $defaultvariables = [
            'loadingimg' => $OUTPUT->pix_icon('i/loading', get_string('loadinghelp')),
            'blockid' => $id,
            'content' => $content,
            'aftermessage' => $aftermessage
        ];

        $PAGE->requires->js_call_amd('block_custom_register/main', 'init', array($id, $this->instance->id));

        return $defaultvariables;
    }
}
