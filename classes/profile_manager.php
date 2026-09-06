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

/**
 * Shared profile-completion field discovery and percentage calculation.
 *
 * This exists because the block and the external services both need the
 * answer, and used to compute it separately: the block counted every custom
 * field in {user_info_field}, while the save endpoint checked a hardcoded list
 * of six shortnames. Any custom field outside that list made the percentage
 * jump after an inline save and revert on reload. One source of truth now — do
 * not reintroduce a second.
 *
 * @package    block_profile_completion
 * @copyright  2026 LearnByWatch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_manager {
    /**
     * Standard user-table fields that count toward completion, in display order.
     *
     * 'picture' is handled separately: it is not free text and cannot be saved
     * through the inline modal (it needs the file picker on /user/edit.php).
     *
     * @var array
     */
    private const STANDARD_FIELDS = [
        'description'  => 'textarea',
        'country'      => 'country',
        'city'         => 'text',
        'institution'  => 'text',
        'department'   => 'text',
    ];

    /**
     * Standard fields save_field() will accept a value for.
     *
     * Deliberately excludes 'picture'. Keep in sync with STANDARD_FIELDS.
     *
     * @return string[]
     */
    public static function get_savable_standard_fields(): array {
        return array_keys(self::STANDARD_FIELDS);
    }

    /**
     * Read the user's standard field values.
     *
     * Always read from the DB rather than $USER: the session copy can be
     * stale immediately after an inline save, which would make the
     * recalculated percentage lag by one field.
     *
     * The record deliberately includes every field user_picture and fullname()
     * need. user_picture::__construct() re-queries the user AND emits a
     * DEBUG_DEVELOPER warning on every render if any of get_picture_fields()
     * is absent — one partial record here would put a warning on the dashboard
     * for every page load.
     *
     * @param int $userid
     * @return \stdClass
     */
    public static function get_user_record(int $userid): \stdClass {
        global $DB;

        $fields = array_merge(
            ['id', 'picture'],
            array_keys(self::STANDARD_FIELDS),
            \core_user\fields::get_picture_fields(),
            \core_user\fields::get_name_fields()
        );

        return $DB->get_record(
            'user',
            ['id' => $userid],
            implode(',', array_unique($fields)),
            MUST_EXIST
        );
    }

    /**
     * Build the full field list — standard plus every custom field the admin
     * has created — with this user's current value for each.
     *
     * Dynamic discovery is intentional: a field added in Site administration
     * starts counting toward completion with no code change.
     *
     * @param int $userid
     * @param \stdClass|null $dbuser Pre-fetched user record, to save a query.
     * @return array[] Each: key, label, type, filled (bool), current, options.
     */
    public static function get_fields(int $userid, ?\stdClass $dbuser = null): array {
        global $DB;

        $dbuser ??= self::get_user_record($userid);

        $fields = [];

        // Profile photo — first, and not inline-editable (see STANDARD_FIELDS).
        $fields[] = [
            'key'     => 'picture',
            'label'   => get_string('field_picture', 'block_profile_completion'),
            'type'    => 'photo',
            'filled'  => !empty($dbuser->picture),
            'current' => '',
            'options' => [],
        ];

        foreach (self::STANDARD_FIELDS as $key => $type) {
            $value = $dbuser->{$key} ?? '';
            $fields[] = [
                'key'     => $key,
                'label'   => get_string('field_' . $key, 'block_profile_completion'),
                'type'    => $type,
                'filled'  => $value !== '' && $value !== null,
                'current' => (string) $value,
                'options' => [],
            ];
        }

        // Every custom profile field, in the order the admin arranged them.
        $sql = "SELECT f.id, f.shortname, f.name, f.datatype, f.param1,
                       COALESCE(d.data, '') AS data
                  FROM {user_info_field} f
             LEFT JOIN {user_info_data} d ON d.fieldid = f.id AND d.userid = :userid
              ORDER BY f.categoryid, f.sortorder";

        foreach ($DB->get_records_sql($sql, ['userid' => $userid]) as $row) {
            $type = match ($row->datatype) {
                'textarea' => 'textarea',
                'menu'     => 'select',
                default    => 'text',
            };

            $options = [];
            if ($type === 'select' && !empty($row->param1)) {
                $options = array_values(array_filter(
                    array_map('trim', explode("\n", $row->param1))
                ));
            }

            $fields[] = [
                'key'     => $row->shortname,
                'label'   => $row->name,
                'type'    => $type,
                'filled'  => $row->data !== '',
                'current' => $row->data,
                'options' => $options,
            ];
        }

        return $fields;
    }

    /**
     * Completion percentage over the field list from get_fields().
     *
     * @param array[] $fields
     * @return array{pct:int, done:int, total:int, missing:array[]}
     */
    public static function calculate(array $fields): array {
        $missing = [];
        $done = 0;

        foreach ($fields as $field) {
            if (!empty($field['filled'])) {
                $done++;
            } else {
                $missing[] = $field;
            }
        }

        $total = count($fields);

        return [
            'pct'     => $total > 0 ? (int) round($done / $total * 100) : 0,
            'done'    => $done,
            'total'   => $total,
            'missing' => $missing,
        ];
    }

    /**
     * Convenience: recalculate from scratch for a user.
     *
     * @param int $userid
     * @return array{pct:int, done:int, total:int, missing:array[]}
     */
    public static function recalculate(int $userid): array {
        return self::calculate(self::get_fields($userid));
    }

    /**
     * The user's interest tags, shaped for JSON/templating.
     *
     * 'rawname' is what remove_item_tag() must be given — it normalises to
     * lowercase internally to match {tag}.name. 'name' is display-only.
     *
     * @param int $userid
     * @return array[] Each: rawname, name.
     */
    public static function get_interests(int $userid): array {
        $tags = \core_tag_tag::get_item_tags('core', 'user', $userid);
        $out = [];
        foreach ($tags as $tag) {
            $out[] = [
                'rawname' => $tag->rawname,
                'name'    => $tag->name,
            ];
        }
        return $out;
    }

    /**
     * Save one field for a user.
     *
     * Both branches go through a core API rather than writing to {user} or
     * {user_info_data} directly. That is what makes the user_updated event
     * fire, so caches, the enrolment/auth plugins and anything else listening
     * see the change — a direct $DB->update_record() is invisible to them.
     *
     * @param int $userid
     * @param string $fieldkey Standard field name or custom field shortname.
     * @param string $value
     * @throws \moodle_exception If the field is not one this block may write.
     */
    public static function save_field(int $userid, string $fieldkey, string $value): void {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->dirroot . '/user/profile/lib.php');

        if (in_array($fieldkey, self::get_savable_standard_fields(), true)) {
            // user_update_user() validates, cleans and triggers user_updated.
            // Passing false for $updatepassword: there is no password here.
            $update = (object) [
                'id'        => $userid,
                $fieldkey   => $value,
            ];

            // The description column is HTML with a companion format column.
            // The inline editor is a plain textarea and the value arrives as
            // PARAM_NOTAGS, so say so — leaving the format at FORMAT_HTML
            // would claim markup that is no longer there.
            if ($fieldkey === 'description') {
                $update->descriptionformat = FORMAT_PLAIN;
            }

            user_update_user($update, false, true);

            // Keep the session copy in step: the block reads from the DB, but
            // the rest of the page (the user menu, fullname()) reads $USER.
            // Read back what was stored rather than trusting $value, which
            // user_update_user() may have cleaned.
            if ($USER->id == $userid) {
                $USER->{$fieldkey} = $DB->get_field('user', $fieldkey, ['id' => $userid]);
                if ($fieldkey === 'description') {
                    $USER->descriptionformat = FORMAT_PLAIN;
                }
            }

        } else {
            // Any custom field the admin has defined. Unknown keys are
            // rejected: $fieldkey reaching the DB unvalidated would be a
            // write primitive.
            if (!$DB->record_exists('user_info_field', ['shortname' => $fieldkey])) {
                throw new \moodle_exception('errorinvalidfield', 'block_profile_completion');
            }

            // profile_save_data() expects the id plus profile_field_<shortname>
            // keys, and handles insert-vs-update and the field's own save
            // hooks. It does not fire user_updated, so do that explicitly.
            $data = (object) [
                'id' => $userid,
                'profile_field_' . $fieldkey => $value,
            ];
            profile_save_data($data);

            \core\event\user_updated::create_from_userid($userid)->trigger();

            // Keep the session copy in step when the user edits their own
            // profile, which is the only case this block supports.
            // $USER->profile is only populated once profile_load_data() has
            // run for this session, so do not create a partial one here.
            if ($USER->id == $userid && isset($USER->profile)) {
                $USER->profile[$fieldkey] = $value;
            }
        }
    }

    /**
     * Add an interest tag, unless the user already has it.
     *
     * @param int $userid
     * @param \context $context The user's own context.
     * @param string $tag
     */
    public static function add_interest(int $userid, \context $context, string $tag): void {
        // set_item_tags replaces the whole set, so send existing plus the new.
        $names = [];
        $lower = [];
        foreach (\core_tag_tag::get_item_tags('core', 'user', $userid) as $existing) {
            $names[] = $existing->rawname;
            // Case-insensitive dedupe: core normalises to lowercase when
            // matching, so 'Python' and 'python' are the same tag and adding
            // the second would silently do nothing.
            $lower[] = \core_text::strtolower($existing->rawname);
        }

        if (in_array(\core_text::strtolower($tag), $lower, true)) {
            return;
        }

        $names[] = $tag;
        \core_tag_tag::set_item_tags('core', 'user', $userid, $context, $names);
    }

    /**
     * Remove an interest tag.
     *
     * Uses the tag API rather than deleting from {tag_instance} directly: a
     * raw delete leaves {tag} reference counts stale and skips the
     * tag_removed event.
     *
     * @param int $userid
     * @param string $tag The tag's rawname.
     */
    public static function remove_interest(int $userid, string $tag): void {
        \core_tag_tag::remove_item_tag('core', 'user', $userid, $tag);
    }
}
