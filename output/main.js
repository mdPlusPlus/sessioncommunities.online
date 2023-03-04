// Hello reader!
// This project can be found at:
// https://lokilocker.com/someguy/sessioncommunities.online

/**
 * This JavaScript file uses the JSDoc commenting style.
 * Learn more: https://jsdoc.app/
 */

// Nudge TypeScript plugins to type-check using JSDoc comments.
// @ts-check

// Early prevention for bugs introduced by lazy coding.
'use strict';

// Import magic numbers and data
import {
	dom, COLUMN, COLUMN_LITERAL, COMPARISON, ATTRIBUTES,
	columnAscendingByDefault, columnIsSortable, COLUMN_TRANSFORMATION, element
} from './js/constants.js';

// Hidden communities for transparency.
const filteredCommunities = {
	tests: [
		"2e9345+c7fb",  // TestRoom
		"762ba9+c7fb",  // TesterRoom
		"appletonv2+4264", // -
		"b4d829+c7fb",  // Test
		"e5853a+c7fb",  // testtest
		"fishing+8e2e", // Example group from PySOGS documentation
		"test+118d",    // Testing 1, 2, 3
		"test+13f6",    // Testing room
		"test+c01b",    // Testing room
		"test+fe93",    // 测试（Test)
		"xyz+7908",     // XYZ Room
		"xyz+efca",     // XYZ Room
	],

	offensive: [
		"60fa60+c7fb",    // "N-word" Community
		"ab1a4d+c7fb",    // zUnsensored Group (CSAM)
		"AlexMed+e093",   //
		"gore+e5e0",      // gore
		"RU-STEROID+e093" //
	],

	// These communities should be checked regularly
	// in case they update their PySOGS version
	legacy: [
		"Ukraine+02bd"  // https://reccacon.com/view/room/Ukraine
	]
};

// This can be achieved with `text-overflow: ellipsis` instead
// and generated entirely server-side.
const transformJoinURL = (join_link) => {
	return element.button({
		textContent: "Copy",
		className: "copy_button",
		onclick: () => copyToClipboard(join_link)
	});
}

function onLoad(timestamp) {
	setLastChecked(timestamp);
	hideBadCommunities();
	sortTable(COLUMN.NAME);
	createJoinLinkButtons();
	markSortableColumns();
}

function displayQRModal(communityID) {
	dom.qr_modal(communityID).style.display = "block";
}

function hideQRModal(communityID) {
	dom.qr_modal(communityID).style.display = "none";
}

function createJoinLinkButtons() {
	const join_URLs = dom.join_urls();
	Array.from(join_URLs).forEach((td_url) => {
		// Data attributes are more idiomatic and harder to change by accident in the DOM.
		const join_link = td_url.getAttribute('data-url');
		td_url.append(transformJoinURL(join_link)); // add interactive content
	});
}

function hideBadCommunities() {
	let numberOfHiddenCommunities = 0;

	for (const category of ['tests', 'offensive', 'legacy']) {
		numberOfHiddenCommunities +=
		  filteredCommunities[category]
		    .map(hideElementByID)
		    .reduce((a, b) => a + b);
	}

	const summary = dom.servers_hidden();
	summary.innerText = `(${numberOfHiddenCommunities} hidden)`;
}

/**
 * Removes an element by its ID and returns the number of elements removed.
 */
function hideElementByID(id) {
	const element = document.getElementById(id);
	element?.remove();
	return element ? 1 : 0;
}

/**
 * Copies text to clipboard and shows an informative toast.
 * @param {string} text - Text to copy to clipboard.
 */
function copyToClipboard(text) {
	navigator.clipboard.writeText(text);

	// Find snackbar element
	const snackbar = dom.snackbar();

	snackbar.classList.add('show')

	// After 3 seconds, hide the snackbar.
	setTimeout(() => snackbar.classList.remove('show'), 3000);
}

/**
 * Sets the "last checked indicator" based on a timestamp.
 * @param {number} last_checked - Timestamp of last community list update.
 */
function setLastChecked(last_checked) {
	const seconds_now = Math.floor(Date.now() / 1000); // timestamp in seconds
	const time_passed_in_seconds = seconds_now - last_checked;
	const time_passed_in_minutes =
		Math.floor(time_passed_in_seconds / 60); // time in minutes, rounded down
	const timestamp_element = dom.last_checked();
	timestamp_element.innerText =	`${time_passed_in_minutes} minutes ago`;
}

/**
 * Function comparing two elements.
 *
 * @callback comparer
 * @param {*} fst - First value to compare.
 * @param {*} snd - Second value to compare.
 * @returns 1 if fst is to come first, -1 if snd is, 0 otherwise.
 */

/**
 * Performs a comparison on two arbitrary values. Treats "" as Infinity.
 * @param {*} fst - First value to compare.
 * @param {*} snd - Second value to compare.
 * @returns 1 if fst > snd, -1 if fst < snd, 0 otherwise.
 */
function compareAscending(fst, snd) {
	// Triple equals to avoid "" == 0.
	if (fst === "") return COMPARISON.GREATER;
	if (snd === "") return COMPARISON.SMALLER;
	return (fst > snd) - (fst < snd);
}

/**
 * Performs a comparison on two arbitrary values. Treats "" as Infinity.
 * @param {*} fst - First value to compare.
 * @param {*} snd - Second value to compare.
 * @returns -1 if fst > snd, 1 if fst < snd, 0 otherwise.
 */
function compareDescending(fst, snd) {
	return -compareAscending(fst, snd);
}

/**
 * Produces a comparer dependent on a derived property of the compared elements.
 * @param {comparer} comparer - Callback comparing derived properties.
 * @param {Function} getProp - Callback to retrieve derived property.
 * @returns {comparer} Function comparing elements based on derived property.
 */
function compareProp(comparer, getProp) {
	return (fst, snd) => comparer(getProp(fst), getProp(snd));
}

/**
 * Produces a comparer for table rows based on given sorting parameters.
 * @param {number} column - Numeric ID of column to be sorted.
 * @param {boolean} ascending - Sort ascending if true, descending otherwise.
 * @returns {comparer}
 */
function makeRowComparer(column, ascending) {
	if (!columnIsSortable(column)) {
		throw new Error(`Column ${column} is not sortable`);
	}

	// Callback to obtain sortable content from cell text.
	const columnToSortable = COLUMN_TRANSFORMATION[column] ?? ((el) => el.innerText.trim());

	// Construct comparer using derived property to determine sort order.
	const rowComparer = compareProp(
		ascending ? compareAscending : compareDescending,
		row => columnToSortable(row.children[column])
	);

	return rowComparer;
}

/**
 * @typedef {Object} SortState
 * @property {number} column - Column ID being sorted.
 * @property {boolean} ascending - Whether the column is sorted ascending.
 */

/**
 * Retrieves a table's sort settings from the DOM.
 * @param {HTMLElement} table - Table of communities being sorted.
 * @returns {?SortState}
 */
function getSortState(table) {
	if (!table.hasAttribute(ATTRIBUTES.SORTING.ACTIVE)) return null;
	const directionState = table.getAttribute(ATTRIBUTES.SORTING.ASCENDING);
	// This is not pretty, but the least annoying.
	// Checking for classes would be more idiomatic.
	const ascending = directionState.toString() === "true";
	const columnState = table.getAttribute(ATTRIBUTES.SORTING.COLUMN);
	const column = parseInt(columnState);
	if (!Number.isInteger(column)) {
		throw new Error(`Invalid column number read from table: ${columnState}`)
	}
	return { ascending, column };
}

/**
 * Sets a table's sort settings using the DOM.
 * @param {HTMLElement} table - Table of communities being sorted.
 * @param {SortState} sortState - Sorting settings being applied.
 */
function setSortState(table, { ascending, column }) {
	if (!table.hasAttribute(ATTRIBUTES.SORTING.ACTIVE)) {
		table.setAttribute(ATTRIBUTES.SORTING.ACTIVE, true);
	}
	table.setAttribute(ATTRIBUTES.SORTING.ASCENDING, ascending);
	table.setAttribute(ATTRIBUTES.SORTING.COLUMN, column);
	// This can be used to style column headers in a consistent way, i.e.
	// #tbl_communities[data-sort-asc=true][sorted-by=name]::after #th_name, ...
	table.setAttribute(ATTRIBUTES.SORTING.COLUMN_LITERAL, COLUMN_LITERAL[column]);
}

// This is best done in JS, as it would require <noscript> styles otherwise.
function markSortableColumns() {
	const table = dom.tbl_communities();
	for (const th of table.querySelectorAll('th')) {
		if (th.id.includes("qr_code")) continue;
		th.classList.add('sortable');
	}
}

/**
 * Sorts the default communities table according the given column.
 * Sort direction is determined by defaults; successive sorts
 * on the same column reverse the sort direction.
 * @param {number} column - Numeric ID of column being sorted.
 */
function sortTable(column) {
	const table = dom.tbl_communities();
	const sortState = getSortState(table);
	const sortingNewColumn = column !== sortState?.column;
	const ascending = sortingNewColumn
		? columnAscendingByDefault(column)
		: !sortState.ascending;
	const compare = makeRowComparer(column, ascending);
	const rows = Array.from(table.rows).slice(1);
	rows.sort(compare);
	rows.forEach((row) => row.remove());
	table.querySelector("tbody").append(...rows);
	setSortState(table, { ascending, column });
}

// html.js for styling purposes
window.document.documentElement.classList.add("js");

// Crude way to export from module script due to inline event handlers.
// Ideally, all handlers would be attached from JS via addEventListener.
Object.assign(window, {
	onLoad, sortTable, displayQRModal,
	hideQRModal, copyToClipboard
});

