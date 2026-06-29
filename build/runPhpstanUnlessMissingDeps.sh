#!/bin/bash
SCRIPT_DIR=$(dirname "$0")
LEXEME_DIR="$SCRIPT_DIR/.."
CORE_DIR="$LEXEME_DIR/../.."
CORE_VENDOR_DIR="$CORE_DIR/vendor"

if [[ -d $CORE_VENDOR_DIR && -n "$(ls -A $CORE_VENDOR_DIR)" ]]; then
    $LEXEME_DIR/vendor/bin/phpstan analyze -a $SCRIPT_DIR/phpstan-bootstrap.php
else
    echo "Cannot run PHPStan because MediaWiki dependencies are not installed."
fi