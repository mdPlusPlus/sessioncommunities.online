// This file contains definitions which help to reduce the amount
// of redunant values in the main file, especially those that could
// change in the foreseeable future.

export const dom = {
	tbl_communities: () => document.getElementById("tbl_communities"),
	last_checked: () => document.getElementById("last_checked_value"),
	qr_modal: (communityID) => document.getElementById(`modal_${communityID}`),
	join_urls: () => document.getElementsByClassName("join_url_container"),
	servers_hidden: () => document.getElementById("servers_hidden"),
	snackbar: () => document.getElementById("copy-snackbar")
}

export const COLUMN = {
	IDENTIFIER:   0,  LANGUAGE:     1,  NAME:         2,
	DESCRIPTION:  3,  USERS:        4,  PREVIEW:      5,
	QR_CODE:      6,  JOIN_URL:     7
};

// Reverse enum.
// Takes original key-value pairs, flips them, and casefolds the new values.
// Should correspond to #th_{} and .td_{} elements in communities table.
export const COLUMN_LITERAL = Object.fromEntries(
	Object.entries(COLUMN).map(([name, id]) => [id, name.toLowerCase()])
);

export const COMPARISON = {
	GREATER: 1, EQUAL: 0, SMALLER: -1
};

export const ATTRIBUTES = {
	SORTING: {
		ACTIVE: 'data-sort',
		ASCENDING: 'data-sort-asc',
		COLUMN: 'data-sorted-by',
		// COLUMN_LITERAL: 'sorted-by'
	}
};

export function columnAscendingByDefault(column) {
	return column != COLUMN.USERS;
}

export function columnIsSortable(column) {
	return ![
		COLUMN.QR_CODE,
		COLUMN.PREVIEW,
		// Join URL contents are not guaranteed to have visible text.
		COLUMN.JOIN_URL
	].includes(column);
}

export function columnNeedsCasefold(column) {
	return [
		COLUMN.IDENTIFIER,
		COLUMN.NAME,
		COLUMN.DESCRIPTION
	].includes(column);
}

export function columnIsNumeric(column) {
	return [
		COLUMN.USERS
	].includes(column);
}

/**
 * Creates an element, and adds attributes and elements to it.
 * @param {string} tag - HTML Tag name.
 * @param {Object|HTMLElement} args - Array of child elements, may start with props.
 * @returns {HTMLElement}
 */
function createElement(tag, ...args) {
	const element = document.createElement(tag);
	if (args.length === 0) return element;
	const propsCandidate = args[0];
	if (typeof propsCandidate !== "string" && !(propsCandidate instanceof Element)) {
		// args[0] is not child element or text node
		// must be props object
		Object.assign(element, propsCandidate);
		args.shift();
	}
	element.append(...args);
	return element;
}

export const element = new Proxy({}, {
	get(_, key) {
		return (...args) => createElement(key, ...args)
	}
});

