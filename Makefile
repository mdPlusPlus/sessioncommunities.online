# First goal is always run with just `make`
all:
	/bin/php php/update-listing.php

data:
	/bin/php php/fetch-servers.php

html:
	/bin/php php/generate-html.php

dev:
	xdg-open http://localhost:8080
	make server &
	make watchdog

lan-dev:
	ip addr | fgrep -e ' 192.' -e ' 10.'
	xdg-open http://localhost:8080
	make lan-server &
	make watchdog

server:
	/bin/php -S localhost:8080 -t output

lan-server:
	/bin/php -S 0.0.0.0:8080 -t output

# Update html on file change
# Doesn't check for new files
watchdog:
	find . | entr -n -s "make html"

