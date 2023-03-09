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

- <https://github.com/GNU-Linux-libre/Awesome-Session-Group-List>
- <https://lokilocker.com/Mods/Session-Groups/wiki/Session-Open-Groups>
- <https://session.directory/>

Additionally, a few other servers are hardcoded, see [querying logic](php/fetch-servers.php).

### How does this work?

The [`update-listing.php`](php/update-listing.php) script invokes the following two PHP scripts: [`fetch-servers.php`](php/fetch-servers.php) to query available servers, and [`generate-html.php`](php/generate-html.php>) to generate the static HTML.

The querying logic consists of these steps:

1. Fetching source HTML: `get_html_from_known_sources()`
1. Extracting Session invites from the HTML:
`extract_join_links_from_html()` and `get_servers_from_join_links()`
1. Making sure servers are online: `reduce_servers()`
1. Querying the servers for all available rooms
and normalizing active user numbers: `query_servers_for_rooms()`
1. De-duplicating servers based on public keys:
`get_pubkeys_of_servers()` and `reduce_addresses_of_pubkeys()`
1. Aggregating all server info & adding language data: `generate_info_arrays()`

Static HTML is generated from the [`sites`](sites) directory to the [`output`](output) directory, which additionally contains static assets. All contents of `sites` are invoked to produce a HTML page unless they are prefixed with a `+` sign.

### Work around bad routing to Chinese servers

Depending on your location, it is possible for you to get really bad routing to
SOGS servers behind the [GFW](https://en.wikipedia.org/wiki/Great_Firewall). In this case,
the initial connection is still successful, but you'll never receive
any actual content and the retrieval attempt will simply time out.
This happens randomly. To make sure this won't affect the results, we simply
check whether the server is online (the initial connection being successful),
and then retry a lot of times with a short timeout
until we eventually get the content.
The details can be seen in `curl_get_contents()`.

### Official repositories

- GitHub: <https://github.com/mdPlusPlus/sessioncommunities.online>
- Lokinet Gitea: <https://lokilocker.com/SomeGuy/sessioncommunities.online>

If your favourite Session community is missing a language flag,
you can issue a pull request here:

- <https://github.com/mdPlusPlus/sessioncommunities.online-languages/>

## Contact

If you want to contact me, you can add me on Session via my
[ONS](https://docs.oxen.io/using-the-oxen-blockchain/using-oxen-name-system):
`someguy`.
