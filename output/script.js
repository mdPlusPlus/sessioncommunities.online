function onLoad(timestamp) {
	setLastChecked(timestamp);
	hideBadCommunities();
	sortTable(2); // 2 == Name
	createJoinLinkButtons();
}

function displayQRModal(communityID) {
	var modalID = "modal_" + communityID;
	modal = document.getElementById(modalID);
	modal.style.display = "block";
}

function hideQRModal(communityID) {
	var modalID = "modal_" + communityID;
	modal = document.getElementById(modalID);
	modal.style.display = "none";
}

function createJoinLinkButtons() {
	elements = document.getElementsByClassName("td_join_url");
	Array.from(elements).forEach((td_element) => {
		a_href = td_element.getElementsByTagName('a')[0]; // get first (only) <a> element
		join_link = a_href.getAttribute("href"); // get link
		button_content = join_link.substring(0, 31) + "...<button class=\"copy_button\" onclick=\"copyToClipboard('" + join_link + "')\">Copy</button>";

		td_element.innerHTML = button_content;
	});
}

function hideBadCommunities() {
	const testCommunityIDs = [
		"2e9345+c7fb", // TestRoom
		"762ba9+c7fb", // TesterRoom
		"b4d829+c7fb", // Test
		"e5853a+c7fb", // testtest
		"test+118d",   // Testing 1, 2, 3
		"test+13f6",   // Testing room
		"test+c01b",   // Testing room
		"test+fe93"    // 测试（Test)
	];

	const badCommunityIDs = [
		"60fa60+c7fb"  // "N-word" Community
	];

	testCommunityIDs.forEach(hideElementByID);
	badCommunityIDs.forEach(hideElementByID);
}

function hideElementByID(id) {
	element = document.getElementById(id);
	if(element != null) {
		element.remove();
	}
}

function copyToClipboard(text) {
	navigator.clipboard.writeText(text);
}

function setLastChecked(timestamp) {
	now = Math.floor(Date.now() / 1000); // timestamp in seconds
	time_passed_in_seconds = now - timestamp;
	time_passed_in_minutes = Math.floor(time_passed_in_seconds / 60); // time in minutes, rounded down
	td_element = document.getElementById("td_last_checked");
	td_element.innerHTML = "Last checked " + time_passed_in_minutes + " minutes ago.";
}

function sortTable(n) {
	var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
	table = document.getElementById("tbl_communities");
	switching = true;
	// Set the sorting direction to ascending:
	dir = "asc";
	/* Make a loop that will continue until
	 no switching has been don*e: */
	while (switching) {
		// Start by saying: no switching is done:
		switching = false;
		rows = table.rows;
		// Loop through all table rows (except the first, which contains table headers):
		for (i = 1; i < (rows.length - 1); i++) {
			// Start by saying there should be no switching:
			shouldSwitch = false;
			// Get the two elements you want to compare, one from current row and one from the next:
			x = rows[i].getElementsByTagName("TD")[n];
			y = rows[i + 1].getElementsByTagName("TD")[n];
			// Check if the two rows should switch place, based on the direction, asc or desc:
			if (dir == "asc") {
				// If columns is users (4), sort numerically
				if ( n == 4 ) {
					if (Number(x.innerHTML) > Number(y.innerHTML)) {
						shouldSwitch = true;
						break;
					}
				} else if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
					// If so, mark as a switch and break the loop:
					shouldSwitch = true;
					break;
				}
			}
			else if (dir == "desc") {
				if ( n == 4 ) {
					// If columns is users (4), sort numerically
					if (Number(x.innerHTML) < Number(y.innerHTML)) {
						shouldSwitch = true;
						break;
					}
				} else if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
					// If so, mark as a switch and break the loop:
					shouldSwitch = true;
					break;
				}
			}
		}
		if (shouldSwitch) {
			// If a switch has been marked, make the switch and mark that a switch has been done:
			rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
			switching = true;
			// Each time a switch is done, increase this count by 1:
			switchcount ++;
		} else {
			// If no switching has been done AND the direction is "asc", set the direction to "desc" and run the while loop again.
			if (switchcount == 0 && dir == "asc") {
				dir = "desc";
				switching = true;
			}
		}
	}
}
