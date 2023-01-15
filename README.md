# Crawl lists of active Session Communities

## What does this site do?

This script crawls known sources of published Session Communities, queries their servers for avaialble information and displays this infomration as a static HTML page.  
The results of this can be viewed on https://sessioncommunities.online/.


## What is Session?

Session is a private messaging app that protects your metadata, encrypts your communications, and makes sure your messaging activities leave no digital trail behind.  
https://getsession.org/


## Details

### Which sources are crawled?

Currently this script crawls the following sites:

- https://github.com/GNU-Linux-libre/Awesome-Session-Group-List
- https://lokilocker.com/Mods/Session-Groups/wiki/Session-Open-Groups
- https://session.directory/

### Steps

1. Getting the HTML of the sources, `get_html_from_known_sources()`
2. Extracting Session join links (also called "invites") from the HTML, `extract_join_links_from_html()`
3. Extract all mentioned servers, `get_servers_from_join_links()`
4. Make sure servers are online, `reduce_servers()`
5. Query the servers for all available rooms and normalize active user numbers, `query_servers_for_rooms()`
6. Make sure servers are neither listed under multiple addresses, nor multiple public keys, `get_pubkeys_of_servers()` and `reduce_addresses_of_pubkeys()`
7. Pack all found information into easily digestible form and assign language flags, `generate_info_arrays()`
8. Generate static HTML content to display on https://sessioncommunities.online/, `generateHTML()`

### Legacy support

Right now we fully support legacy SOGS servers, although this support is likely going to be dropped soon, since those servers can not even be joined anymore with current Session clients.  
Dropping legacy support will also increase maintainability.

### Work around bad routing to Chinese servers

Depending on your location, it is possible for you to get really bad routing to SOGS servers behind the GFW. In this case the initial connection is still successful, but you'll never receive any actual content and the retrieval attempt will simply time out. This happens randomly. To make sure this won't affect the results, we simply check whether the server is online (the initial connection being succesful), and then retry a lot of times with a short timeout until we eventually get the content.  
The details can be seen in `curl_get_contents()`.

### Official repositories
- https://github.com/mdPlusPlus/sessioncommunities.online
- https://lokilocker.com/SomeGuy/sessioncommunities.online

If you're the owner of a Session Community, or are a member of one, that does not yet have a language flag assigned to it, you can issue a pull request here:
- https://github.com/mdPlusPlus/sessioncommunities.online-languages/


## Contact

If you want to contact me, you can add me on Session via my [ONS](https://docs.oxen.io/using-the-oxen-blockchain/using-oxen-name-system): "someguy" (without the quotes)
