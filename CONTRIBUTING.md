# Contribution guidelines

## Development environment

### Prerequisites

- PHP (version TBD)
- `make`
- `entr` to watch for file changes
- `xdg-open` link handler to invoke browser
- patience

### Cloning or updating the repository

Ensure the consistency of the `languages` submodule by using the following options:

- `git clone --recurse-submodules <repository-url>`
- `git pull --recurse-submodules`

### Development

Run at least once: `make fetch` to query servers. This can take around 5 minutes.

Run when developing: `make dev` to watch for changes & serve HTML locally in browser.
Does not respond to new files.

See [`Makefile`](Makefile) for more details.

### Running your own copy

- point your webserver at the `output` folder
- install systemd services from the `systemd` folder or an equivalent timer
- `session_sudoers`: TBD

## Code style guidelines

### General

**Indentation**: Tabs (4-wide)

**Filename seperator**: Hyphen (`-`)

### PHP

**Identifier casing**: `snake_case` and `CONSTANT_CASE`

**Comments and documentation**: TBD

### HTML & CSS

**Identifier casing**: `kebab-case`, occasional `snake_case`

**Comments and documentation**: TBD

### JavaScript

**Identifier casing**: `camelCase` and `CONSTANT_CASE`, occasional `snake_case`

**Comments and documentation**: [JSDoc](https://jsdoc.app/)

## Contact

- Web Development Session Community on [caliban.org](https://sog.caliban.org/)
- Project lead, querying logic, deployment, community filtering: `someguy` on Session
- Documentation, code quality, HTML generation, CSS, JS: `gravel` on Session
