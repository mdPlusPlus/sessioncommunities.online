port = 8081
output = output

# First goal is the default with `make`.

# List make scripts.
list:
	grep "^[^[:space:]]*:" Makefile --before-context=1 --group-separator=""

## Using make dependencies is duplicating behaviour but reads better.
# /bin/php php/update-listing.php
# Refresh listing and generate HTML.
all: fetch html

# Fetch room listing.
fetch:
	/bin/php php/fetch-servers.php

# Generate HTML from data.
html:
	/bin/php php/generate-html.php

# Last item run in foreground to receive interrupts.

# Serve a local copy which responds to file changes.
dev: open
	make server &
	make watchdog

# Serve a local copy on LAN which responds to file changes.
lan-dev: open
	ip addr | fgrep -e ' 192.' -e ' 10.'
	make lan-server &
	make watchdog

# Serve a local copy.
server:
	/bin/php -S localhost:$(port) -t $(output)

# Serve a local copy on all interfaces.
lan-server:
	/bin/php -S 0.0.0.0:$(port) -t $(output)

# Open locally served page in browser.
open:
	xdg-open http://localhost:$(port) >/dev/null 2>/dev/null & disown

# Update HTML on file change. Doesn't check for new files.
watchdog:
	find . | entr -n -s "make html"

# Remove artefacts
clean:
	rm -r cache
	rm -r output/*.html

# Build everything from scratch and test functionality.
test: clean all open server

# Build everything from scratch and test functionality on LAN.
test-lan: clean all open lan-server

# -- Aliases --
serve: server

lan-serve: lan-server

data: fetch

watch: watchdog

