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
 * Language strings for the Profile completion block.
 *
 * @package    block_profile_completion
 * @copyright  2026 LearnByWatch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Profile completion';
$string['profile_completion:addinstance'] = 'Add a Profile completion block';
$string['profile_completion:myaddinstance'] = 'Add a Profile completion block to My Dashboard';

// Identity.
$string['greeting'] = 'Hello {$a},';
$string['addjob'] = 'Add your job title';
$string['editprofile'] = 'Edit profile';

// Completion.
$string['completionpct'] = '{$a}% complete';
$string['pctshort'] = '{$a}%';
$string['completioncomplete'] = 'Your profile is complete!';
$string['completionprompt'] = 'Complete your profile so AI can personalise your learning experience.';

// Missing fields.
$string['missingfields'] = 'What\'s missing';
$string['addfield'] = 'Add';
$string['addlabel'] = 'Add {$a}';
$string['andmore'] = 'and {$a} more…';
$string['completeprofile'] = 'Complete your profile';

// Interests.
$string['interests'] = 'Interests';
$string['addinterest'] = '+ Add';
$string['interestplaceholder'] = 'e.g. Cinematography';
$string['removeinterest'] = 'Remove {$a}';

// Generic actions.
$string['add'] = 'Add';
$string['save'] = 'Save';
$string['saving'] = 'Saving…';
$string['cancel'] = 'Cancel';

// Errors.
$string['errorempty'] = 'Please enter a value before saving.';
$string['errornetwork'] = 'Network error. Please try again.';
$string['errorgeneric'] = 'Something went wrong. Please try again.';
$string['errorinvalidfield'] = 'That field does not exist.';

// Field labels. The key format is field_<shortname> and is generated
// dynamically from the DB column name in profile_manager::get_fields() — a
// missing key here surfaces only when that field is the one being rendered.
$string['field_picture'] = 'Profile photo';
$string['field_description'] = 'Bio';
$string['field_country'] = 'Country';
$string['field_city'] = 'City';
$string['field_institution'] = 'Organisation';
$string['field_department'] = 'Department';

// Custom profile fields take their label from the admin-defined field name, so
// these are unused by the block. Kept because removing a published string is a
// breaking change for any site that has translated them.
$string['field_jobtitle'] = 'Job title';
$string['field_industry'] = 'Industry';
$string['field_experience_level'] = 'Experience level';
$string['field_learning_goals'] = 'Learning goals';
$string['field_current_skills'] = 'Current skills';
$string['field_interests'] = 'Interests';

$string['privacy:metadata'] = 'The Profile completion block only displays existing profile '
    . 'data and does not store any data itself.';
