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
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Add an interest tag to the current user.
 *
 * @package    block_profile_completion
 * @copyright  2026 LearnByWatch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_interest extends external_api {

    /**
     * Parameter description.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'tag' => new external_value(PARAM_TAG, 'Interest tag to add'),
        ]);
    }

    /**
     * Add the tag and return the user's full interest list.
     *
     * @param string $tag
     * @return array
     * @throws \moodle_exception If the tag is empty.
     */
    public static function execute(string $tag): array {
        global $USER;

        ['tag' => $tag] = self::validate_parameters(self::execute_parameters(), ['tag' => $tag]);

        $context = \context_user::instance($USER->id);
        self::validate_context($context);

        $tag = trim($tag);
        if ($tag === '') {
            throw new \moodle_exception('errorempty', 'block_profile_completion');
        }

        profile_manager::add_interest((int) $USER->id, $context, $tag);

        return ['tags' => profile_manager::get_interests((int) $USER->id)];
    }

    /**
     * Return description.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'tags' => new external_multiple_structure(
                new external_single_structure([
                    'rawname' => new external_value(PARAM_TAG, 'Tag name as stored'),
                    'name'    => new external_value(PARAM_TAG, 'Tag name for display'),
                ]),
                'The user\'s interest tags'
            ),
        ]);
    }
}
