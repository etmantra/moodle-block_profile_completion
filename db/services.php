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
 * External service definitions for the Profile completion block.
 *
 * Every function here acts on the calling user only, so none needs a
 * capability. They are ajax-callable because the block's AMD module invokes
 * them from the dashboard through core/ajax.
 *
 * @package    block_profile_completion
 * @copyright  2026 LearnByWatch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [

    'block_profile_completion_save_field' => [
        'classname'   => 'block_profile_completion\external\save_field',
        'description' => 'Save one profile field for the current user.',
        'type'        => 'write',
        'ajax'        => true,
    ],

    'block_profile_completion_add_interest' => [
        'classname'   => 'block_profile_completion\external\add_interest',
        'description' => 'Add an interest tag to the current user.',
        'type'        => 'write',
        'ajax'        => true,
    ],

    'block_profile_completion_remove_interest' => [
        'classname'   => 'block_profile_completion\external\remove_interest',
        'description' => 'Remove an interest tag from the current user.',
        'type'        => 'write',
        'ajax'        => true,
    ],
];
