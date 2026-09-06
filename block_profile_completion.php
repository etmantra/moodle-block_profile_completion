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
 * Profile completion block.
 *
 * @package    block_profile_completion
 * @copyright  2026 LearnByWatch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use block_profile_completion\output\block_content;

/**
 * Shows the current user how complete their profile is, and lets them fill in
 * what is missing without leaving the page.
 *
 * This class does data retrieval and access control only; all markup lives in
 * templates/block_content.mustache, rendered through the output API.
 *
 * @package    block_profile_completion
 * @copyright  2026 LearnByWatch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_profile_completion extends block_base {

    /**
     * Set the block title.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_profile_completion');
    }

    /**
     * This block is about the viewer's own profile, so it makes sense anywhere.
     *
     * @return array
     */
    public function applicable_formats() {
        return ['all' => true];
    }

    /**
     * One instance per page is enough — a second would show the same profile.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * The block body.
     *
     * @return stdClass
     */
    public function get_content() {
        global $USER, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->footer = '';
        $this->content->text = '';

        // Nothing to complete for a guest or a logged-out visitor.
        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }

        $this->page->requires->js_call_amd(
            'block_profile_completion/profile',
            'init',
            [block_content::RING_RADIUS]
        );

        $this->content->text = $OUTPUT->render_from_template(
            'block_profile_completion/block_content',
            (new block_content((int) $USER->id))->export_for_template($OUTPUT)
        );

        return $this->content;
    }
}
