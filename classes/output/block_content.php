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
use renderable;
use renderer_base;
use templatable;

/**
 * Prepares the block's data for templates/block_content.mustache.
 *
 * All markup lives in the template; this class only assembles values. Keep it
 * that way — the block used to concatenate HTML strings, which made the two
 * renderings (web and app) drift apart.
 *
 * @package    block_profile_completion
 * @copyright  2026 LearnByWatch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_content implements renderable, templatable {

    /** @var float Radius of the progress ring, in the SVG's own viewBox units. */
    public const RING_RADIUS = 36.5;

    /** @var int Missing-field chips shown before the "and N more" overflow. */
    private const MAX_CHIPS = 4;

    /** @var int The user this block is rendered for. */
    private int $userid;

    /**
     * Constructor.
     *
     * @param int $userid
     */
    public function __construct(int $userid) {
        $this->userid = $userid;
    }

    /**
     * Build the template context.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        global $CFG;

        require_once($CFG->dirroot . '/user/profile/lib.php');

        $dbuser    = profile_manager::get_user_record($this->userid);
        $fields    = profile_manager::get_fields($this->userid, $dbuser);
        $stats     = profile_manager::calculate($fields);
        $interests = profile_manager::get_interests($this->userid);

        $editurl   = new \moodle_url('/user/edit.php', ['id' => $this->userid]);
        $countries = get_string_manager()->get_list_of_countries(true);
        $complete  = $stats['pct'] >= 100;

        // Custom field values keyed by shortname — for the subtitle only.
        $customvals = [];
        foreach ($fields as $field) {
            $customvals[$field['key']] = $field['current'];
        }

        return [
            'pct'      => $stats['pct'],
            'complete' => $complete,
        ] + $this->ring_geometry($stats['pct'])
          + $this->identity($output, $dbuser, $customvals, $countries)
          + [
            'editurl'      => $editurl->out(false),
            'interests'    => $interests,
            'missing'      => $this->missing_chips($stats['missing']),
            'overflow'     => max(0, count($stats['missing']) - self::MAX_CHIPS),
            'hasoverflow'  => count($stats['missing']) > self::MAX_CHIPS,
            'hasmissing'   => !empty($stats['missing']),
            // The country list (~250 entries) ships as an inert JSON script
            // tag rather than through js_call_amd, which serialises every
            // argument into the page and warns above 1024 characters.
            'countriesjson' => json_encode($countries),
        ];
    }

    /**
     * Stroke geometry for the progress ring.
     *
     * Computed here so PHP and the AMD module cannot disagree about the
     * radius after an inline save redraws the arc.
     *
     * @param int $pct
     * @return array
     */
    private function ring_geometry(int $pct): array {
        $circumference = 2 * M_PI * self::RING_RADIUS;

        return [
            'ringradius'    => self::RING_RADIUS,
            'circumference' => round($circumference, 1),
            'dashoffset'    => round($circumference - ($circumference * $pct / 100), 1),
        ];
    }

    /**
     * Avatar, name, subtitle and location.
     *
     * @param renderer_base $output
     * @param \stdClass $dbuser Complete enough for user_picture and fullname().
     * @param array $customvals
     * @param array $countries
     * @return array
     */
    private function identity(renderer_base $output, \stdClass $dbuser,
                              array $customvals, array $countries): array {

        // visibletoscreenreaders=false: the user's own name sits immediately
        // beside the avatar, so an alt text of that name would be read twice.
        $picture = new \core\output\user_picture($dbuser);
        $picture->size = 100;
        $picture->class = 'pcb-avatar';
        $picture->visibletoscreenreaders = false;

        $subtitleparts = array_filter([
            $customvals['jobtitle'] ?? '',
            $dbuser->institution ?? '',
        ]);

        $locationparts = array_filter([
            $dbuser->city ?? '',
            !empty($dbuser->country) ? ($countries[$dbuser->country] ?? '') : '',
        ]);

        return [
            'avatar'      => $output->render($picture),
            'fullname'    => fullname($dbuser),
            'subtitle'    => $subtitleparts ? implode(' · ', $subtitleparts) : '',
            'hassubtitle' => !empty($subtitleparts),
            'location'    => $locationparts ? implode(', ', $locationparts) : '',
            'haslocation' => !empty($locationparts),
        ];
    }

    /**
     * The missing-field chips, capped at MAX_CHIPS.
     *
     * @param array[] $missing
     * @return array[]
     */
    private function missing_chips(array $missing): array {
        $chips = [];

        foreach (array_slice($missing, 0, self::MAX_CHIPS) as $field) {
            $chips[] = [
                'key'     => $field['key'],
                'label'   => $field['label'],
                'type'    => $field['type'],
                'current' => $field['current'],
                'options' => json_encode($field['options']),
            ];
        }

        return $chips;
    }
}
