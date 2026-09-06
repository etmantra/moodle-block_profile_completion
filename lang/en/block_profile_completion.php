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
 * Keys are kept in alphabetical order, as moodle-cs requires.
 *
 * The field_<shortname> keys are looked up dynamically from the DB column
 * name in profile_manager::get_fields(), so a missing one surfaces only when
 * that field is the one being rendered. The keys for custom profile fields
 * are unused by the block itself -- those take their label from the
 * admin-defined field name -- but are kept because removing a published
 * string breaks any site that has translated it.
 *
 * @package    block_profile_completion
 * @copyright  2026 LearnByWatch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['add'] = 'Add';
$string['addfield'] = 'Add';
$string['addinterest'] = '+ Add';
$string['addjob'] = 'Add your job title';
$string['addlabel'] = 'Add {$a}';
$string['andmore'] = 'and {$a} more…';
$string['cancel'] = 'Cancel';
$string['completeprofile'] = 'Complete your profile';
$string['completioncomplete'] = 'Your profile is complete!';
$string['completionpct'] = '{$a}% complete';
$string['completionprompt'] = 'Complete your profile so AI can personalise your learning experience.';
$string['editprofile'] = 'Edit profile';
$string['errorempty'] = 'Please enter a value before saving.';
$string['errorgeneric'] = 'Something went wrong. Please try again.';
$string['errorinvalidfield'] = 'That field does not exist.';
$string['errornetwork'] = 'Network error. Please try again.';
$string['field_city'] = 'City';
$string['field_country'] = 'Country';
$string['field_current_skills'] = 'Current skills';
$string['field_department'] = 'Department';
$string['field_description'] = 'Bio';
$string['field_experience_level'] = 'Experience level';
$string['field_industry'] = 'Industry';
$string['field_institution'] = 'Organisation';
$string['field_interests'] = 'Interests';
$string['field_jobtitle'] = 'Job title';
$string['field_learning_goals'] = 'Learning goals';
$string['field_picture'] = 'Profile photo';
$string['greeting'] = 'Hello {$a},';
$string['interestplaceholder'] = 'e.g. Cinematography';
$string['interests'] = 'Interests';
$string['missingfields'] = 'What\'s missing';
$string['pctshort'] = '{$a}%';
$string['pluginname'] = 'Profile completion';
$string['privacy:metadata'] = 'The Profile completion block only displays existing profile data '
    . 'and does not store any data itself.';
$string['profile_completion:addinstance'] = 'Add a Profile completion block';
$string['profile_completion:myaddinstance'] = 'Add a Profile completion block to My Dashboard';
$string['removeinterest'] = 'Remove {$a}';
$string['save'] = 'Save';
$string['saving'] = 'Saving…';
