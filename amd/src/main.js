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
 * Javascript to initialise the block.
 *
 * @package   block_custom_register
 * @copyright 2020 David Herney @ BambuCo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/modal_factory', 'core/templates', 'core/notification', 'core/ajax'],
        function($, ModalFactory, Templates, Notification, Ajax) {

    var wwwroot = M.cfg.wwwroot;

    function get_form_data($form){
        var unindexed_array = $form.serializeArray();
        var indexed_array = {};

        $.map(unindexed_array, function(n, i){
            indexed_array[n['name']] = n['value'];
        });

        return indexed_array;
    }

    /**
     * Initialise all for the block.
     *
     */
    var init = function (id, instanceid) {

        var $block = $('#' + id);

        $block.find('input').each(function() {
            var $control = $(this);
            $control.wrap('<span class="control-wrap"></span>');
            $control.after('<span class="control-msg"></span>');
        });

        $block.find('[data-action="save"]').on('click', function(e) {

            e.preventDefault();

            $block.find('.control-msg').empty();

            var valid = true;

            // Validate required fields.
            $block.find('input[data-required]').each(function() {
                var $control = $(this);
                var value = $.trim($control.val());

                if (!value) {
                    $control.parent().find('.control-msg').text(M.str.block_custom_register.fieldrequired);
                    valid = false;
                }
            });

            // Validate email fields if exist.
            var regexemail = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
            $block.find('input[type="email"]').each(function() {
                var $control = $(this);
                var value = $.trim($control.val());

                if (value && !regexemail.test(value)) {
                    $control.parent().find('.control-msg').text(M.str.block_custom_register.bademail);
                    valid = false;
                }
            });

            if (!valid) {
                return;
            }

            var $form = $block.find('form');
            var $message = $block.find('.aftermessage');
            var formdata = JSON.stringify(get_form_data($form));

            Ajax.call([{
                methodname: 'block_custom_register_save',
                args: { 'instanceid': parseInt(instanceid), 'formdata': formdata },
                done: function (data) {

                    var aftermessage;
                    if (data.success) {
                        $form.empty();
                        if ($message.text() == '') {
                            $message.text(data.message);
                        }
                        $message.show();
                    } else {
                        Notification.alert('', data.message);
                    }
                },
                fail: function (e) {
                    Notification.exception(e);
                    console.log(e);
                }
            }]);
        });

    };

    return {
        init: init
    };
});
