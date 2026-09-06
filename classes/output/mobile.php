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

namespace block_profile_completion\output;

use block_profile_completion\profile_manager;

/**
 * Moodle App output for block_profile_completion.
 *
 * A read-only summary: completion percentage, the missing-field labels, and
 * interests. Editing happens in the app's own profile screen, reached by the
 * button this renders — see db/mobile.php for why the inline editor is not
 * reproduced here.
 *
 * @package    block_profile_completion
 * @copyright  2026 LearnByWatch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {

    /** @var int Missing-field chips shown before the "+N more" summary. */
    private const MAX_CHIPS = 5;

    /**
     * Block content for the app.
     *
     * @param array $args cmid/courseid/contextlevel from the app.
     * @return array templates, javascript, otherdata, files
     */
    public static function mobile_block_view(array $args): array {
        global $USER, $OUTPUT, $CFG;

        if (!isloggedin() || isguestuser()) {
            return self::empty_response();
        }

        require_once($CFG->dirroot . '/user/profile/lib.php');

        $userid = (int) $USER->id;

        $fields    = profile_manager::get_fields($userid);
        $stats     = profile_manager::calculate($fields);
        $interests = profile_manager::get_interests($userid);

        // Missing field labels only — the app view does not edit them, so it
        // needs the names, not the input metadata the web modal uses.
        $missing = [];
        foreach ($stats['missing'] as $field) {
            $missing[] = ['label' => $field['label']];
        }
        $shown    = array_slice($missing, 0, self::MAX_CHIPS);
        $overflow = count($missing) - count($shown);

        $complete = $stats['pct'] >= 100;

        $context = [
            'pct'          => $stats['pct'],
            'complete'     => $complete,
            'fullname'     => fullname($USER),
            'prompt'       => $complete
                ? get_string('completioncomplete', 'block_profile_completion')
                : get_string('completionprompt', 'block_profile_completion'),
            'missing'      => $shown,
            'hasmissing'   => !empty($shown),
            'hasoverflow'  => $overflow > 0,
            'overflow'     => $overflow,
            'interests'    => array_map(static function ($tag) {
                return ['name' => $tag['name']];
            }, $interests),
            'hasinterests' => !empty($interests),
            'editurl'      => (new \moodle_url('/user/edit.php', ['id' => $userid]))->out(false),
        ];

        return [
            'templates' => [
                [
                    'id'   => 'main',
                    'html' => $OUTPUT->render_from_template(
                        'block_profile_completion/mobile_view',
                        $context
                    ),
                ],
            ],
            'javascript' => '',
            'otherdata'  => [],
            'files'      => '',
        ];
    }

    /**
     * An empty-but-valid response.
     *
     * @return array
     */
    private static function empty_response(): array {
        return [
            'templates'  => [['id' => 'main', 'html' => '']],
            'javascript' => '',
            'otherdata'  => [],
            'files'      => '',
        ];
    }
}
