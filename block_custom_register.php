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
 * Form for editing custom_register block instances.
 *
 * @package   block_custom_register
 * @copyright 2020 David Herney @ BambuCo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_custom_register extends block_base {
    /**
     * Init method.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_custom_register');
    }

    /**
     * Return true if the block has a configuration.
     *
     * @return boolean
     */
    public function has_config() {
        return false;
    }

    /**
     * Which page types this block may appear on.
     *
     * The information returned here is processed by the blocks_name_allowed_in_format() function.
     * Look there if you need to know exactly how this works.
     *
     * Default case: everything except mod and tag.
     *
     * @return array page-type prefix => true/false.
     */
    public function applicable_formats() {
        return ['all' => true];
    }

    /**
     * This function is called on your subclass right after an instance is loaded.
     *
     */
    public function specialization() {
        if (isset($this->config->title)) {
            $this->title = $this->title = format_string($this->config->title, true, ['context' => $this->context]);
        } else {
            $this->title = get_string('newblocktitle', 'block_custom_register');
        }
    }

    /**
     * This block allow multiple instances.
     *
     * @return boolean
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * Load the block content.
     *
     * @return stdClass
     */
    public function get_content() {
        global $COURSE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $config = $this->config;

        if (empty($config->fields)) {
            return $this->content;
        }

        // Load one time if multiple instances be added in the page.
        // Load templates and other general information.
        $renderable = new \block_custom_register\output\main();
        $renderable->instanceconfig = $config;
        $renderable->context = $this->context;
        $renderable->instance = $this->instance;
        $renderer = $this->page->get_renderer('block_custom_register');

        $this->content->text = $renderer->render($renderable);

        if (has_capability('block/custom_register:viewreport', $this->context)) {
            $url = new moodle_url('/blocks/custom_register/report.php', ['id' => $this->instance->id, 'courseid' => $COURSE->id]);

            $this->content->footer = html_writer::tag(
                'a',
                get_string('viewreport', 'block_custom_register'),
                [
                    'href' => $url,
                    'class' => 'btn btn-secondary',
                    'target' => '_blank',
                ]
            );
        }

        return $this->content;
    }

    /**
     * This function is called to determine if the block can be docked.
     *
     * @return bool
     */
    public function instance_can_be_docked() {
        return false;
    }

    /**
     * Serialize and store config data
     *
     * @param stdClass $data The data from the form
     * @param bool $nolongerused
     * @return void
     */
    public function instance_config_save($data, $nolongerused = false) {
        $config = clone($data);

        // Move embedded files into a proper filearea and adjust HTML links to match.
        $config->content = file_save_draft_area_files(
            $data->content['itemid'],
            $this->context->id,
            'block_custom_register',
            'content',
            0,
            ['subdirs' => true],
            $data->content['text']
        );
        $config->format = $data->content['format'];

        parent::instance_config_save($config, $nolongerused);
    }

    /**
     * Delete any block-specific data when deleting a block instance.
     *
     * @return boolean
     */
    public function instance_delete() {
        $fs = get_file_storage();
        $fs->delete_area_files($this->context->id, 'block_custom_register');
        return true;
    }

    /**
     * Copy any block-specific data when copying to a new block instance.
     *
     * @param int $fromid the id number of the block instance to copy from
     * @return boolean
     */
    public function instance_copy($fromid) {
        $fromcontext = context_block::instance($fromid);
        $fs = get_file_storage();
        // This extra check if file area is empty adds one query if it is not empty but saves several if it is.
        if (!$fs->is_area_empty($fromcontext->id, 'block_custom_register', 'content', 0, false)) {
            $draftitemid = 0;
            file_prepare_draft_area(
                $draftitemid,
                $fromcontext->id,
                'block_custom_register',
                'content',
                0,
                ['subdirs' => true]
            );
            file_save_draft_area_files(
                $draftitemid,
                $this->context->id,
                'block_custom_register',
                'content',
                0,
                ['subdirs' => true]
            );
        }
        return true;
    }
}
