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
 * Inline profile editing and interest tags.
 *
 * @module     block_profile_completion/profile
 * @copyright  2026 LearnByWatch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {call as fetchMany} from 'core/ajax';
import {get_string as getString} from 'core/str';
import ModalSaveCancel from 'core/modal_save_cancel';
import ModalEvents from 'core/modal_events';
import Notification from 'core/notification';
import Templates from 'core/templates';

/** @type {number} Ring radius, in the SVG's own viewBox units. */
let ringRadius = 36.5;

/** @type {Object} Country code => name. Read once from the JSON script tag. */
let countries = {};

const SELECTORS = {
    ROOT: '[data-region="profile-completion"]',
    RING: '[data-pcb-ring]',
    RING_FILL: '.pcb-ring-fill',
    BADGE: '[data-pcb-badge]',
    PROMPT: '[data-pcb-prompt]',
    MISSING: '[data-pcb-missing]',
    MORE: '[data-pcb-more]',
    CHIP: '[data-pcb-chip]',
    EDIT_LINK: '.pcb-editlink',
    PILLS: '[data-pcb-pills]',
    PILL: '[data-pcb-pill]',
    ADD_INTEREST: '[data-pcb-add-interest]',
    INTEREST_ROW: '[data-pcb-interest-row]',
    INTEREST_INPUT: '[data-pcb-interest-input]',
    INTEREST_SAVE: '[data-pcb-interest-save]',
    INTEREST_CANCEL: '[data-pcb-interest-cancel]',
    INTEREST_ERROR: '[data-pcb-interest-error]',
    REMOVE: '[data-pcb-remove]',
    COUNTRIES: '[data-pcb-countries]',
    MODAL_INPUT: '[data-pcb-modal-input]',
};

/**
 * Save one profile field.
 *
 * @param {string} field
 * @param {string} value
 * @returns {Promise<Object>}
 */
const saveFieldRequest = (field, value) => fetchMany([{
    methodname: 'block_profile_completion_save_field',
    args: {field, value},
}])[0];

/**
 * Add an interest tag.
 *
 * @param {string} tag
 * @returns {Promise<Object>}
 */
const addInterestRequest = (tag) => fetchMany([{
    methodname: 'block_profile_completion_add_interest',
    args: {tag},
}])[0];

/**
 * Remove an interest tag.
 *
 * @param {string} tag
 * @returns {Promise<Object>}
 */
const removeInterestRequest = (tag) => fetchMany([{
    methodname: 'block_profile_completion_remove_interest',
    args: {tag},
}])[0];

/**
 * Show a message in an inline error slot.
 *
 * @param {Element} el
 * @param {string} message
 */
const showError = (el, message) => {
    el.textContent = message;
    el.hidden = false;
};

/**
 * Report a failed request in an inline error slot.
 *
 * A rejection from core/ajax carries the exception the external service threw,
 * whose message is already localised and safe to show. Anything else — a
 * transport failure, say — falls back to the generic string.
 *
 * @param {Element} el
 * @param {Object} error
 */
const showRequestError = async(el, error) => {
    const fallback = await getString('errorgeneric', 'block_profile_completion');
    showError(el, (error && error.message) ? error.message : fallback);
};

/**
 * Update the ring, badge and prompt after a successful save.
 *
 * @param {Element} root
 * @param {number} pct
 */
const updateProgress = async(root, pct) => {
    const ringwrap = root.querySelector(SELECTORS.RING);
    const fill = root.querySelector(SELECTORS.RING_FILL);
    const badge = root.querySelector(SELECTORS.BADGE);
    const complete = pct >= 100;

    if (fill) {
        // Recompute from the radius the block rendered with, so the arc can
        // never drift from the server's idea of the geometry.
        const circumference = 2 * Math.PI * ringRadius;
        fill.style.strokeDashoffset = circumference - (circumference * pct / 100);
    }
    if (badge) {
        badge.textContent = await getString('pctshort', 'block_profile_completion', pct);
    }
    if (ringwrap) {
        ringwrap.classList.toggle('pcb-is-complete', complete);
    }

    if (!complete) {
        return;
    }

    const missing = root.querySelector(SELECTORS.MISSING);
    if (missing) {
        missing.remove();
    }

    const prompt = root.querySelector(SELECTORS.PROMPT);
    if (prompt) {
        prompt.classList.add('pcb-complete');
        prompt.textContent = await getString('completioncomplete', 'block_profile_completion');
    }
};

/**
 * Remove a saved field's chip and decrement the overflow counter.
 *
 * Once no chip is left, either reload (if fields are still hidden behind "and
 * N more") or drop the section entirely.
 *
 * @param {Element} root
 * @param {string} fieldkey
 */
const consumeChip = async(root, fieldkey) => {
    const chip = root.querySelector('[data-pcb-chip="' + CSS.escape(fieldkey) + '"]');
    if (chip) {
        chip.remove();
    }

    const more = root.querySelector(SELECTORS.MORE);
    let hidden = more ? parseInt(more.dataset.overflow, 10) : 0;

    if (more) {
        hidden -= 1;
        if (hidden <= 0) {
            hidden = 0;
            more.remove();
        } else {
            more.dataset.overflow = hidden;
            more.textContent = await getString('andmore', 'block_profile_completion', hidden);
        }
    }

    if (root.querySelectorAll(SELECTORS.CHIP).length > 0) {
        return;
    }

    // No chip is left on screen. If fields are still hidden behind "and N
    // more", reload so the block can choose which to show next — that choice
    // belongs to the server, and duplicating it here would let the two drift.
    if (hidden > 0) {
        window.location.reload();
        return;
    }

    // Nothing is missing any more, so drop the whole section rather than
    // leaving an empty heading behind.
    const missing = root.querySelector(SELECTORS.MISSING);
    if (missing) {
        missing.remove();
    }
};

/**
 * Build the modal body for a field.
 *
 * Rendered from a template rather than assembled with createElement, so the
 * markup stays in templates/ with the rest of the block's HTML.
 *
 * @param {Element} chip
 * @returns {Promise<string>}
 */
const renderModalBody = (chip) => {
    const type = chip.dataset.type;
    const current = chip.dataset.current || '';
    const isselect = type === 'select' || type === 'country';

    let options = [];
    try {
        options = JSON.parse(chip.dataset.options || '[]');
    } catch (e) {
        options = [];
    }

    // A country field takes its options from the site's country list; a menu
    // field from the options the admin defined on it.
    const source = type === 'country'
        ? Object.entries(countries)
        : options.map((option) => [option, option]);

    return Templates.render('block_profile_completion/field_input', {
        istextarea: type === 'textarea',
        isselect: isselect,
        istext: !isselect && type !== 'textarea',
        current: current,
        label: chip.dataset.label,
        choices: source.map(([value, label]) => ({
            value: value,
            label: label,
            selected: value === current,
        })),
    });
};

/**
 * Save the modal's value.
 *
 * @param {Element} root
 * @param {Object} modal
 * @param {string} fieldkey
 */
const saveField = async(root, modal, fieldkey) => {
    // Modal.create() can resolve before the body template has rendered, so
    // wait for the body before looking for the control inside it.
    await modal.getBodyPromise();

    const input = modal.getRoot()[0].querySelector(SELECTORS.MODAL_INPUT);
    const value = input ? input.value.trim() : '';

    // An empty value would not complete the field, so keep the modal open
    // rather than saving nothing and closing.
    if (!value) {
        if (input) {
            input.focus();
        }
        return;
    }

    try {
        const data = await saveFieldRequest(fieldkey, value);
        modal.destroy();
        await updateProgress(root, data.pct);
        await consumeChip(root, fieldkey);
    } catch (error) {
        Notification.exception(error);
    }
};

/**
 * Open the inline-edit modal for a chip.
 *
 * @param {Element} root
 * @param {Element} chip
 */
const openModal = async(root, chip) => {
    // The photo needs the file picker, which only exists on the edit form.
    if (chip.dataset.type === 'photo') {
        window.location.href = root.querySelector(SELECTORS.EDIT_LINK).href;
        return;
    }

    const fieldkey = chip.dataset.pcbChip;

    try {
        const modal = await ModalSaveCancel.create({
            title: getString('addlabel', 'block_profile_completion', chip.dataset.label),
            body: renderModalBody(chip),
            buttons: {save: getString('save', 'block_profile_completion')},
            removeOnClose: true,
            show: true,
        });

        modal.getRoot().on(ModalEvents.save, (e) => {
            // Hold the modal open while the request is in flight: a failed
            // save must not silently discard what the user typed.
            e.preventDefault();
            saveField(root, modal, fieldkey);
        });

        // Return focus to the chip that opened the modal.
        modal.getRoot().on(ModalEvents.hidden, () => {
            if (document.body.contains(chip)) {
                chip.focus();
            }
        });
    } catch (error) {
        Notification.exception(error);
    }
};

/**
 * Redraw the pill list from a server response.
 *
 * @param {Element} root
 * @param {Array} tags
 */
const renderPills = async(root, tags) => {
    const container = root.querySelector(SELECTORS.PILLS);
    const addbtn = container ? container.querySelector(SELECTORS.ADD_INTEREST) : null;

    // A theme may override the template; without the add button there is no
    // anchor to insert before, so leave the list as the server rendered it.
    if (!container || !addbtn) {
        return;
    }

    container.querySelectorAll(SELECTORS.PILL).forEach((pill) => pill.remove());

    const {html} = await Templates.renderForPromise(
        'block_profile_completion/interest_pills',
        {interests: tags}
    );
    addbtn.insertAdjacentHTML('beforebegin', html);
};

/**
 * Show or hide the add-interest input row.
 *
 * @param {Element} root
 * @param {boolean} show
 */
const toggleInterestInput = (root, show) => {
    const row = root.querySelector(SELECTORS.INTEREST_ROW);
    const addbtn = root.querySelector(SELECTORS.ADD_INTEREST);
    const input = root.querySelector(SELECTORS.INTEREST_INPUT);

    row.hidden = !show;
    addbtn.hidden = show;

    if (show) {
        input.focus();
    } else {
        input.value = '';
        root.querySelector(SELECTORS.INTEREST_ERROR).hidden = true;
    }
};

/**
 * Add the typed interest.
 *
 * @param {Element} root
 */
const addInterest = async(root) => {
    const input = root.querySelector(SELECTORS.INTEREST_INPUT);
    const error = root.querySelector(SELECTORS.INTEREST_ERROR);
    const tag = input.value.trim();

    if (!tag) {
        return;
    }

    try {
        const data = await addInterestRequest(tag);
        await renderPills(root, data.tags);
        toggleInterestInput(root, false);
    } catch (e) {
        await showRequestError(error, e);
    }
};

/**
 * Remove an interest.
 *
 * @param {Element} root
 * @param {Element} pill
 */
const removeInterest = async(root, pill) => {
    const error = root.querySelector(SELECTORS.INTEREST_ERROR);

    try {
        await removeInterestRequest(pill.dataset.pcbPill);
        pill.remove();
    } catch (e) {
        await showRequestError(error, e);
    }
};

/**
 * Wire up the block.
 *
 * @param {number} radius Ring radius the block rendered the SVG with.
 */
export const init = (radius) => {
    ringRadius = radius;

    const root = document.querySelector(SELECTORS.ROOT);
    if (!root || root.dataset.pcbInit) {
        return;
    }
    root.dataset.pcbInit = '1';

    // The country list ships as an inert JSON script tag rather than through
    // the AMD arguments, which warn above 1024 characters. Parse it once; an
    // absent or malformed tag just means the country selector falls back to
    // empty.
    const countriesEl = root.querySelector(SELECTORS.COUNTRIES);
    if (countriesEl) {
        try {
            countries = JSON.parse(countriesEl.textContent) || {};
        } catch (e) {
            countries = {};
        }
    }

    // One delegated listener: pills and chips are replaced at runtime, and
    // per-element binding would leave the new ones dead.
    root.addEventListener('click', (e) => {
        const chip = e.target.closest(SELECTORS.CHIP);
        if (chip) {
            openModal(root, chip);
            return;
        }
        if (e.target.closest(SELECTORS.ADD_INTEREST)) {
            toggleInterestInput(root, true);
            return;
        }
        if (e.target.closest(SELECTORS.INTEREST_CANCEL)) {
            toggleInterestInput(root, false);
            return;
        }
        if (e.target.closest(SELECTORS.INTEREST_SAVE)) {
            addInterest(root);
            return;
        }
        const remove = e.target.closest(SELECTORS.REMOVE);
        if (remove) {
            removeInterest(root, remove.closest(SELECTORS.PILL));
        }
    });

    root.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && e.target.matches(SELECTORS.INTEREST_INPUT)) {
            e.preventDefault();
            addInterest(root);
        }
    });
};
