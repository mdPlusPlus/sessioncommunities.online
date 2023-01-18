# Crawl lists of active Session Communities

## What does this site do?

This script crawls known sources of published Session Communities, 
queries their servers for available information and 
displays this information as a static HTML page.  
The results of this can be viewed on https://sessioncommunities.online/.


## What is Session?

Session is a private messaging app that protects your meta-data, 
encrypts your communications, and makes sure your messaging activities 
leave no digital trail behind.  
https://getsession.org/


## Details

### Which sources are crawled?

Currently this script crawls the following sites:

- https://github.com/GNU-Linux-libre/Awesome-Session-Group-List
- https://lokilocker.com/Mods/Session-Groups/wiki/Session-Open-Groups
- https://session.directory/

Additionally, the following open community servers are polled:

- https://open.getsession.org
- http://13.233.251.36:8081

### Steps

1. Fetching source HTML: `get_html_from_known_sources()`
1. Extracting Session invites from the HTML:
`extract_join_links_from_html()` and `get_servers_from_join_links()`
1. Making sure servers are online: `reduce_servers()`
1. Querying the servers for all available rooms 
and normalize active user numbers: `query_servers_for_rooms()`
1. De-duplicating servers based on public keys: 
`get_pubkeys_of_servers()` and `reduce_addresses_of_pubkeys()`
1. Aggregating all server info & adding language data: `generate_info_arrays()`
1. Generating static HTML content: `generateHTML()`

### Legacy support

Right now we fully support legacy SOGS servers, 
although this support is likely going to be dropped soon, 
since those servers can not even be joined anymore with current Session clients.  
Dropping legacy support will also increase maintainability.

### Work around bad routing to Chinese servers

Depending on your location, it is possible for you to get really bad routing to 
SOGS servers behind the GFW. In this case, 
the initial connection is still successful, but you'll never receive 
any actual content and the retrieval attempt will simply time out. 
This happens randomly. To make sure this won't affect the results, we simply
check whether the server is online (the initial connection being successful), 
and then retry a lot of times with a short timeout 
until we eventually get the content.  
The details can be seen in `curl_get_contents()`.

### Official repositories
- https://github.com/mdPlusPlus/sessioncommunities.online
- https://lokilocker.com/SomeGuy/sessioncommunities.online

If your favourite Session community is missing a language flag, 
you can issue a pull request here:
- https://github.com/mdPlusPlus/sessioncommunities.online-languages/


## Contact

If you want to contact me, you can add me on Session via my 
[ONS](https://docs.oxen.io/using-the-oxen-blockchain/using-oxen-name-system): 
"someguy" (without the quotes)
