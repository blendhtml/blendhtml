#!/usr/bin/env bash

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

php "$SCRIPT_DIR/syncRoles.sh" --force
php "$SCRIPT_DIR/syncUsersRoles.sh" --force
php "$SCRIPT_DIR/syncWhitelist.sh" --force