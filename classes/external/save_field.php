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

namespace block_profile_completion\external;

use block_profile_completion\profile_manager;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Save one profile field for the current user.
 *
 * @package    block_profile_completion
 * @copyright  2026 LearnByWatch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_field extends external_api {
    /**
     * Parameter description.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'field' => new external_value(PARAM_ALPHANUMEXT, 'Standard or custom field shortname'),
            'value' => new external_value(PARAM_NOTAGS, 'New value for the field'),
        ]);
    }

    /**
     * Save the field and return the recalculated completion figures.
     *
     * There is deliberately no userid parameter: this always acts on the
     * calling user, so no capability beyond being logged in is required.
     *
     * @param string $field
     * @param string $value
     * @return array
     */
    public static function execute(string $field, string $value): array {
        global $USER;

        [
            'field' => $field,
            'value' => $value,
        ] = self::validate_parameters(self::execute_parameters(), [
            'field' => $field,
            'value' => $value,
        ]);

        // The user's own context: the only thing this service ever writes to.
        $context = \context_user::instance($USER->id);
        self::validate_context($context);

        profile_manager::save_field((int) $USER->id, $field, $value);

        $stats = profile_manager::recalculate((int) $USER->id);

        return [
            'pct'   => $stats['pct'],
            'done'  => $stats['done'],
            'total' => $stats['total'],
        ];
    }

    /**
     * Return description.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'pct'   => new external_value(PARAM_INT, 'Completion percentage'),
            'done'  => new external_value(PARAM_INT, 'Number of completed fields'),
            'total' => new external_value(PARAM_INT, 'Total number of fields'),
        ]);
    }
}
