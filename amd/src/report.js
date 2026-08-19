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
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Controls record selection on the report page.
 *
 * @module     block_custom_register/report
 * @copyright  2026 NodoLab
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    var SELECTORS = {
        bulkForm: '[data-region="block_custom_register/bulk-form"]',
        deleteSelected: '[data-action="block_custom_register/delete-selected"]',
        selectAll: '[data-action="block_custom_register/select-all"]',
        selectRegister: '[data-action="block_custom_register/select-register"]'
    };

    /**
     * Initialise selection controls for one bulk deletion form.
     *
     * @param {HTMLFormElement} form The bulk deletion form.
     * @returns {void}
     */
    var initForm = function(form) {
        var selectAll = form.querySelector(SELECTORS.selectAll);
        var checkboxes = Array.prototype.slice.call(form.querySelectorAll(SELECTORS.selectRegister));
        var deleteButton = form.querySelector(SELECTORS.deleteSelected);
        if (!selectAll || !deleteButton) {
            return;
        }

        var updateControls = function() {
            var selected = checkboxes.filter(function(checkbox) {
                return checkbox.checked;
            }).length;

            deleteButton.disabled = selected === 0;
            selectAll.checked = checkboxes.length > 0 && selected === checkboxes.length;
            selectAll.indeterminate = selected > 0 && selected < checkboxes.length;
        };

        selectAll.addEventListener('change', function() {
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = selectAll.checked;
            });
            updateControls();
        });

        checkboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', updateControls);
        });

        updateControls();
    };

    /**
     * Initialise the report selection controls.
     *
     * @returns {void}
     */
    var init = function() {
        Array.prototype.forEach.call(document.querySelectorAll(SELECTORS.bulkForm), function(form) {
            initForm(form);
        });
    };

    return {
        init: init
    };
});
