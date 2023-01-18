// This file contains definitions which help to reduce the amount
// of redunant values in the main file, especially those that could
// change in the foreseeable future.

export const dom = {
	tbl_communities: () => document.getElementById("tbl_communities"),
	td_last_checked: () => document.getElementById("td_last_checked"),
	qr_modal: (communityID) => document.getElementById(`modal_${communityID}`),
	join_urls: () => document.getElementsByClassName("td_join_url"),
	td_summary: () => document.getElementById("td_summary"),
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
		COLUMN_LITERAL: 'sorted-by'
	}
};

export function columnAscendingByDefault(column) { 
	return column != COLUMN.USERS; 
}

export function columnIsSortable(column) { return column != COLUMN.QR_CODE; }

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

