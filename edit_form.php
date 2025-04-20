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
 * Form for editing block instances.
 *
 * @package   block_custom_register
 * @copyright 2020 David Herney @ BambuCo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Form for editing block instances.
 *
 * @copyright 2020 David Herney @ BambuCo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_custom_register_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
        global $CFG;

        // Fields for editing HTML block title and contents.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_title', get_string('customtitle', 'block_custom_register'));
        $mform->setType('config_title', PARAM_TEXT);

        $mform->addElement('textarea', 'config_fields', get_string('fields', 'block_custom_register'));
        $mform->setType('config_fields', PARAM_TEXT);
        $mform->addHelpButton('config_fields', 'fields', 'block_custom_register');

        $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => $this->block->context);
        $mform->addElement('editor', 'config_content', get_string('content', 'block_custom_register'), null, $editoroptions);
        $mform->addRule('config_content', null, 'required', null, 'client');
        $mform->setType('config_content', PARAM_RAW); // XSS is prevented when printing the block contents and serving files
        $mform->addHelpButton('config_content', 'content', 'block_custom_register');

        $editoroptions = array('enable_filemanagement' => false, 'noclean' => true, 'context' => $this->block->context);
        $mform->addElement('editor', 'config_aftermessage',
                            get_string('aftermessage', 'block_custom_register'), null, $editoroptions);
        $mform->setType('config_aftermessage', PARAM_RAW);
        $mform->addHelpButton('config_aftermessage', 'aftermessage', 'block_custom_register');

        $mform->addElement('text', 'config_type', get_string('configtype', 'block_custom_register'));
        $mform->setType('config_type', PARAM_TEXT);

        $mform->addElement('text', 'config_joinfield', get_string('joinfield', 'block_custom_register'));
        $mform->setType('config_joinfield', PARAM_TEXT);

        $mform->addElement('textarea', 'config_joinmessage', get_string('joinmessage', 'block_custom_register'));
        $mform->setType('config_joinmessage', PARAM_TEXT);
        $mform->addHelpButton('config_joinmessage', 'joinmessage', 'block_custom_register');

        $mform->addElement('text', 'config_ukfield', get_string('ukfield', 'block_custom_register'));
        $mform->setType('config_ukfield', PARAM_TEXT);
        $mform->addHelpButton('config_ukfield', 'ukfield', 'block_custom_register');
    }

    function set_data($defaults) {
        if (!empty($this->block->config) && is_object($this->block->config)) {
            $text = $this->block->config->content;
            $draftid_editor = file_get_submitted_draft_itemid('config_content');
            if (empty($text)) {
                $currenttext = '';
            } else {
                $currenttext = $text;
            }
            $defaults->config_content['text'] = file_prepare_draft_area($draftid_editor, $this->block->context->id, 'block_html', 'content', 0, array('subdirs'=>true), $currenttext);
            $defaults->config_content['itemid'] = $draftid_editor;
            $defaults->config_content['format'] = $this->block->config->format;
        } else {
            $text = '';
        }

        if (!$this->block->user_can_edit() && !empty($this->block->config->title)) {
            // If a title has been set but the user cannot edit it format it nicely
            $title = $this->block->config->title;
            $defaults->config_title = format_string($title, true, $this->page->context);
            // Remove the title from the config so that parent::set_data doesn't set it.
            unset($this->block->config->title);
        }

        // have to delete text here, otherwise parent::set_data will empty content
        // of editor
        unset($this->block->config->content);
        parent::set_data($defaults);
        // restore $text
        if (!isset($this->block->config)) {
            $this->block->config = new stdClass();
        }
        $this->block->config->content = $text;
        if (isset($title)) {
            // Reset the preserved title
            $this->block->config->title = $title;
        }
    }
}
