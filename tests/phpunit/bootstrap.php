<?php

/**
 * PHPUnit test bootstrap file for the Wikibase Lexeme component
 * copied from the Wikibase MediaInfo extension.
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */

if ( PHP_SAPI !== 'cli' ) {
	die( 'Not an entry point' );
}

error_reporting( E_ALL | E_STRICT );
ini_set( 'display_errors', 1 );

if ( is_readable( __DIR__ . '/../../vendor/autoload.php' ) ) {
	$classLoader = require __DIR__ . '/../../vendor/autoload.php';
} elseif ( is_readable( __DIR__ . '/../../../vendor/autoload.php' ) ) {
	$classLoader = require __DIR__ . '/../../../vendor/autoload.php';
} else {
	die( 'You need to install this package with Composer before you can run the tests' );
}

$classLoader->addPsr4(
	'Wikibase\\DataModel\\Services\\Tests\\',
	__DIR__ . '/../../vendor/wikibase/data-model-services/tests/unit/'
);

unset( $classLoader );
