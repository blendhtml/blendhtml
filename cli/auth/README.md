# Blendhtml CLI

Small, explicit CLI scripts for managing Blendhtml authentication.

The scripts are intentionally simple and are designed to be run directly, for example from PHPStorm.

## Structure

```text
cli/
├── helpers/
│   └── readInputFile.php
├── auth/
│   ├── setConfigs.sh
│   ├── showConfiguration.sh
│   ├── syncRoles.input
│   ├── syncRoles.sh
│   ├── syncUsersRoles.input
│   ├── syncUsersRoles.sh
│   ├── syncWhitelist.input
│   └── syncWhitelist.sh
└── ...
```

## Configuration

### `setConfigs.sh`

Contains the available `AuthAdmin` configuration operations as commented-out lines.

Uncomment the configuration you want to apply and run the script.

Examples:

```php
AuthAdmin::setOtpLength(6);
AuthAdmin::setSessionLifetime(1209600);
AuthAdmin::setEmailAccessMode('whitelist');
```

Configuration can also be removed by uncommenting the corresponding `remove...()` operation.

### `showConfiguration.sh`

Displays the effective authentication configuration and indicates the source of configurable values where applicable.

## Roles

Authentication roles come from two distinct sources.

### Blendhtml roles

Blendhtml defines its protected framework roles in `BlendhtmlRoles`:

* `admin`
* `visit_metrics`
* `content_editing`
* `ab_testing`

These roles are created or ensured by Blendhtml migrations or seeding. They are not managed as project role definitions by `syncRoles.sh`.

Project tooling cannot create, delete, redefine, or overwrite these protected roles. They remain normal assignable roles and can be used for authorization:

```php
use Blendhtml\Core\Auth\Auth;
use Blendhtml\Core\Auth\BlendhtmlRoles;

if (Auth::hasRole(BlendhtmlRoles::ADMIN)) {
    // Show framework administration controls.
}
```

### Project roles

Projects may define roles for application-specific authorization, for example:

* `marketing_manager`
* `content_editor`
* `campaign_manager`

Project roles are created and deleted through `AuthAdmin` or synchronized with `syncRoles.sh`.

`AuthAdmin::createRole()` and `AuthAdmin::deleteRole()` reject protected Blendhtml role names.

### `syncRoles.input`

Defines the desired set of project roles only.

Example:

```text
marketing_manager
content_editor
campaign_manager

# Any comment here if required
```

Blank lines and comments are ignored.

Protected Blendhtml roles do not belong in this file. If one is present, `syncRoles.sh` ignores it so the input can never create or delete a framework role.

### `syncRoles.sh`

Synchronizes project role definitions with `syncRoles.input`.

* Missing project roles are created.
* Project roles no longer present in the input are deleted.
* Existing project roles are kept.
* Protected Blendhtml roles are excluded from creation and deletion.
* The input file represents the desired project-role state, not the complete framework-role state.

## Users and Roles

### `syncUsersRoles.input`

Defines users and their desired role assignments.

Assignments may contain either Blendhtml roles or project roles.

Example:

```text
blendhtml@nikolajev.ee: admin, visit_metrics
it-consulting@nikolajev.ee: content_editor, campaign_manager

# Any comment here if required
```

Multiple roles are separated by commas.

A user may have no roles:

```text
user@example.com:
```

### `syncUsersRoles.sh`

Synchronizes users and their role assignments.

* Users are created automatically when they do not exist.
* Existing users are reused.
* Blendhtml and project roles can both be assigned.
* Existing roles are reused.
* A missing project role requires explicit confirmation before it is created.
* A missing protected Blendhtml role is not created; Blendhtml migrations or seeding must ensure it.
* User role assignments are synchronized with the input.
* Roles can be removed from a user's assignments when they are no longer listed.
* Role definitions are never deleted by this script.

Project role definition cleanup belongs to `syncRoles.sh`. Protected Blendhtml role definitions remain under Blendhtml's lifecycle.

## Whitelist

### `syncWhitelist.input`

Defines the desired email whitelist.

Example:

```text
blendhtml@nikolajev.ee
it-consulting@email.com

# Any comment here if required
```

### `syncWhitelist.sh`

Synchronizes the email whitelist with the input file.

* Missing emails are added.
* Emails no longer present in the input are removed.
* Existing emails are kept.
* Duplicate entries are ignored.
* Email addresses are normalized to lowercase.
* Invalid email addresses cause the script to fail.

The whitelist is relevant when authentication is configured with:

```php
AuthAdmin::setEmailAccessMode('whitelist');
```

## Input Files

All `.input` files use the same basic rules:

* Empty lines are ignored.
* Lines beginning with `#` are ignored.
* Leading and trailing whitespace is trimmed.

### Project input files

The project may provide its own input files under:

```text
BlendhtmlSys/
└── cli/
    └── auth/
        ├── syncRoles.input
        ├── syncUsersRoles.input
        └── syncWhitelist.input
```

The CLI scripts themselves remain vendor-controlled.

When a project input file exists, it is used automatically.

### Vendor input files

The vendor package contains default `.input` files alongside the CLI scripts.

If the corresponding project input file does not exist, the CLI reports that the project file is missing and asks for confirmation before using the vendor input file.

The vendor input files are intended as commented examples and can be copied to the project and customized.

If the user does not confirm the vendor input, the operation is cancelled.

## Design

The CLI deliberately avoids a command framework or dashboard.

Each operation is a small, explicit PHP script that uses the existing Blendhtml `AuthAdmin` API.

The `.sh` scripts are vendor-controlled and are not intended to be overridden by the project. Project-specific CLI data is supplied through the `.input` files.

The goal is simple:

> **Open `cli/`, read the input file, understand what will happen, run the script.**
