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
 * @module    block_custom_register/main
 * @copyright 2020 David Herney @ BambuCo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/modal_factory', 'core/templates', 'core/notification', 'core/ajax', 'core/str', 'core/log'],
        function($, ModalFactory, Templates, Notification, Ajax, Str, Log) {


    // Load strings.
    var strings = [
        {key: 'fieldrequired', component: 'block_custom_register'},
        {key: 'bademail', component: 'block_custom_register'},
    ];
    var s = [];

    /**
     * Load strings from server.
     */
    function loadStrings() {
        strings.forEach(one => {
            s[one.key] = one.key;
        });

        Str.get_strings(strings).then(function(results) {
            var pos = 0;
            strings.forEach(one => {
                s[one.key] = results[pos];
                pos++;
            });
            return true;
        }).fail(function(e) {
            Log.debug('Error loading strings');
            Log.debug(e);
        });
    }
    // End of Load strings.

    /**
     *
     * @param {object} $form
     * @returns
     */
    function getFormData($form) {
        var unindexedarray = $form.serializeArray();
        var indexedarray = {};

        $.map(unindexedarray, function(n) {
            indexedarray[n.name] = n.value;
        });

        return indexedarray;
    }

    /**
     * Initialise all for the block.
     *
     * @param {string} id The block id.
     * @param {string} instanceid The block instance id.
     */
    var init = function(id, instanceid) {
        var $block = $('#' + id);

        loadStrings();

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
                    $control.parent().find('.control-msg').text(s.fieldrequired);
                    valid = false;
                }
            });

            // Validate email fields if exist.
            var regexemail = /^([a-zA-Z0-9_.+-])+@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
            $block.find('input[type="email"]').each(function() {
                var $control = $(this);
                var value = $.trim($control.val());

                if (value && !regexemail.test(value)) {
                    $control.parent().find('.control-msg').text(s.bademail);
                    valid = false;
                }
            });

            if (!valid) {
                return;
            }

            var $form = $block.find('form');
            var $message = $block.find('.aftermessage');
            var formdata = JSON.stringify(getFormData($form));

            Ajax.call([{
                methodname: 'block_custom_register_save',
                args: {'instanceid': parseInt(instanceid), 'formdata': formdata},
                done: function(data) {
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
                fail: function(e) {
                    Notification.exception(e);
                }
            }]);
        });

    };

    return {
        init: init
    };
});
