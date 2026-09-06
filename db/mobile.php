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
 * Moodle App support for block_profile_completion.
 *
 * The web block edits fields inline through its own external services, which
 * the app's block delegate does not call. Rather than rebuild that editor for
 * the app, the mobile view is a read-only summary — ring, percentage, what is
 * missing, interests — with a button into the app's own profile editor
 * (/user/edit.php), which already exists there.
 *
 * Read at login by tool_mobile_get_plugins_supporting_mobile. Changes here
 * need a version.php bump, then Notifications, then a device re-login.
 *
 * @package    block_profile_completion
 * @copyright  2026 LearnByWatch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$addons = [
    'block_profile_completion' => [
        'handlers' => [
            'profilecompletion' => [
                'delegate' => 'CoreBlockDelegate',
                'method'   => 'mobile_block_view',
            ],
        ],
        'lang' => [
            ['pluginname', 'block_profile_completion'],
            ['completioncomplete', 'block_profile_completion'],
            ['completionprompt', 'block_profile_completion'],
            ['missingfields', 'block_profile_completion'],
            ['interests', 'block_profile_completion'],
            ['editprofile', 'block_profile_completion'],
        ],
    ],
];
