# Profile completion block

[![Moodle Plugin CI](https://github.com/etmantra/moodle-block_profile_completion/actions/workflows/ci.yml/badge.svg)](https://github.com/etmantra/moodle-block_profile_completion/actions/workflows/ci.yml)

Shows users how complete their Moodle profile is, and lets them fill in what is
missing without leaving the page.

## What it does

- A progress ring around the user's avatar with their completion percentage.
- A list of the fields that are still empty. Selecting one opens a dialogue
  that saves it in place, and the ring updates immediately.
- Interest tags, which can be added and removed from the block.
- A read-only summary in the Moodle App, with a link into the app's own
  profile editor.

Completion counts the profile photo, the standard fields (description,
country, city, institution, department) and **every custom profile field the
site has defined**. A field added in *Site administration → Users → User
profile fields* starts counting straight away, with no code change.

## Requirements

Moodle 4.5 (LTS) or later. Tested against Moodle 4.5 and 5.0.

## Installation

Copy the plugin into `blocks/profile_completion` in your Moodle directory:

    git clone https://github.com/etmantra/moodle-block_profile_completion.git blocks/profile_completion

Then visit *Site administration → Notifications* to complete the installation.

Alternatively, download the ZIP from the Moodle plugins directory and install
it through *Site administration → Plugins → Install plugins*.

## Usage

Turn editing on, then add **Profile completion** from the block drawer. It is
most useful on the Dashboard, where it is visible after every login. The block
always shows the profile of the person viewing it, so one instance per page is
enough.

## Privacy

The block stores no data of its own. It reads the user's existing profile
fields, tags and picture to calculate a percentage, and writes changes back
through core APIs, which own that data.

## Licence

2026 LearnByWatch

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE. See the [GNU General Public License](https://www.gnu.org/licenses/)
for more details.
