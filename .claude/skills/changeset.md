---
description: Create a changeset for the current changes. Use when the user asks to create a changeset, release, or version bump, or after completing a feature/fix that needs a release.
user_invocable: true
---

# Create a Changeset

Create a changeset file for the current changes using `pnpm changeset`. This skill determines the correct semver bump level and writes the changeset non-interactively.

## Steps

### 1. Analyze the changes

Review what has changed by reading modified files, git diffs, and understanding the intent of the work. Categorize the changes to determine the correct semver bump.

### 2. Determine the semver bump level

This package follows **strict semver** (https://semver.org). Use the rules below to pick the bump level.

#### Major (breaking change)

A change is **major** if it can cause existing consumers of this package to break when they upgrade. Examples:

- Removing or renaming a public method, class, trait, or config key
- Changing the signature of a public method (removing params, changing types, changing return types)
- Changing default behavior that consumers rely on (e.g. slug separator changes from `-` to `_`)
- Dropping support for a PHP version (e.g. removing `^8.2` from `composer.json` `require.php`)
- **Any change to `laravel/framework` major version support** — this includes adding a new major version (e.g. adding `^12.0`), dropping an old one, or both. This is **always major** regardless of whether old versions are retained, because it signals a new compatibility baseline for consumers.

#### Minor (new functionality, backwards-compatible)

A change is **minor** if it adds new capability without breaking existing usage. Examples:

- Adding a new public method or config option
- Adding a new event, attribute, or optional feature
- Adding support for a new PHP version without dropping old ones

#### Patch (bug fix, backwards-compatible)

A change is **patch** if it fixes incorrect behavior without adding new features or breaking anything. Examples:

- Fixing a bug in slug generation
- Fixing an edge case in unique slug resolution
- Correcting type hints that were wrong
- Internal refactoring that does not change public API or behavior
- Updating dev dependencies (test tools, linters)
- Documentation fixes

### 3. Write the changeset

Run the following command, replacing `<bump>` with `major`, `minor`, or `patch`, and `<summary>` with a concise description of what changed and why:

```bash
pnpm changeset add --message "<summary>" -- --package actengage-sluggable --type <bump>
```

If the above non-interactive approach does not work, create the changeset file manually:

1. Generate a random lowercase kebab-case name (e.g. `bright-dogs-smile`)
2. Write the file to `.changeset/<name>.md`:

```markdown
---
"actengage-sluggable": <bump>
---

<summary>
```

### 4. Verify

Run `pnpm changeset status` to confirm the changeset was created and the bump level is correct.

### 5. Tell the user

Report the bump level chosen and why, along with the changeset file path.
