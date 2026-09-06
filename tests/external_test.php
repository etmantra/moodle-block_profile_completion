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

namespace block_profile_completion;

use block_profile_completion\external\add_interest;
use block_profile_completion\external\remove_interest;
use block_profile_completion\external\save_field;
use core_external\external_api;
use core_external\tests\externallib_testcase;

/**
 * Tests for the block's external services.
 *
 * @package    block_profile_completion
 * @copyright  2026 LearnByWatch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_profile_completion\external\save_field
 * @covers     \block_profile_completion\external\add_interest
 * @covers     \block_profile_completion\external\remove_interest
 */
final class external_test extends externallib_testcase {
    /**
     * Saving a standard field writes the value and returns the new percentage.
     */
    public function test_save_field_updates_a_standard_field(): void {
        global $DB;

        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user(['city' => '']);
        $this->setUser($user);

        $result = save_field::execute('city', 'Jaipur');
        $result = external_api::clean_returnvalue(save_field::execute_returns(), $result);

        $this->assertSame('Jaipur', $DB->get_field('user', 'city', ['id' => $user->id]));
        $this->assertGreaterThan(0, $result['pct']);
        // Standard savable fields, plus the picture, plus every custom field.
        $expected = count(profile_manager::get_savable_standard_fields())
            + 1 + $DB->count_records('user_info_field');
        $this->assertSame($expected, $result['total']);
    }

    /**
     * The point of issue #9: the write must announce itself, so that caches
     * and other plugins observing the user see the change.
     */
    public function test_save_field_triggers_user_updated(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $sink = $this->redirectEvents();
        save_field::execute('department', 'Physics');
        $events = $sink->get_events();
        $sink->close();

        $found = array_filter($events, static function ($event) {
            return $event instanceof \core\event\user_updated;
        });

        $this->assertCount(1, $found);
        $this->assertEquals($user->id, reset($found)->objectid);
    }

    /**
     * A field the block does not own must be rejected rather than written.
     */
    public function test_save_field_rejects_an_unknown_field(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());

        $this->expectException(\moodle_exception::class);
        save_field::execute('notafield', 'value');
    }

    /**
     * Adding an interest returns the user's full tag list.
     */
    public function test_add_interest(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());

        $result = add_interest::execute('Cinematography');
        $result = external_api::clean_returnvalue(add_interest::execute_returns(), $result);

        $this->assertCount(1, $result['tags']);
        $this->assertSame('Cinematography', $result['tags'][0]['rawname']);
    }

    /**
     * Tags are matched case-insensitively, so a differently-cased duplicate
     * must not create a second pill.
     */
    public function test_add_interest_is_case_insensitive(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());

        add_interest::execute('Python');
        $result = add_interest::execute('python');
        $result = external_api::clean_returnvalue(add_interest::execute_returns(), $result);

        $this->assertCount(1, $result['tags']);
    }

    /**
     * Removing an interest drops it from the list.
     */
    public function test_remove_interest(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        add_interest::execute('Editing');
        $result = remove_interest::execute('Editing');
        $result = external_api::clean_returnvalue(remove_interest::execute_returns(), $result);

        $this->assertTrue($result['status']);
        $this->assertEmpty(profile_manager::get_interests($user->id));
    }
}
