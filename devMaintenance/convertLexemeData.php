<?php

namespace Wikibase\Lexeme\DevelopmentMaintenance;

use Maintenance;
use Wikibase\Lib\Reporting\ObservableMessageReporter;
use Wikibase\Repo\WikibaseRepo;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false
	? getenv( 'MW_INSTALL_PATH' )
	: __DIR__ . '/../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * @license GPL-2.0-or-later
 */
class ConvertLexemeData extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Updates serialization of lexeme data in the outdated format. ' .
			'NEVER RUN IN PRODUCTION ENVIRONMENT!' );

		$this->requireExtension( 'WikibaseLexeme' );
	}

	public function execute() {
		if ( !defined( 'WB_VERSION' ) ) {
			$this->error( "You need to have Wikibase enabled in order to use this script!\n\n", 1 );
		}

		$this->runLexemeSerializationUpdater();
		$this->runPropertySerializationUpdater();

		$this->output( "Done.\n" );
	}

	private function getReporter() {
		$reporter = new ObservableMessageReporter();
		$reporter->registerReporterCallback( function( $message ) {
			$this->output( "$message\n" );
		} );

		return $reporter;
	}

	public function runLexemeSerializationUpdater() {
		$namespaceLookup = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup();
		$lexemeNamespaceId = $namespaceLookup->getEntityNamespace( 'lexeme' );

		$updater = new LexemeSerializationUpdater( wfGetDB( DB_MASTER ), $lexemeNamespaceId );
		$updater->setMessageReporter( $this->getReporter() );

		$updater->update();
	}

	private function runPropertySerializationUpdater() {
		$namespaceLookup = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup();
		$propertyNamespaceId = $namespaceLookup->getEntityNamespace( 'property' );

		$updater = new PropertySerializationUpdater( wfGetDB( DB_MASTER ), $propertyNamespaceId );
		$updater->setMessageReporter( $this->getReporter() );

		$updater->update();
	}

}

$maintClass = ConvertLexemeData::class;
require_once RUN_MAINTENANCE_IF_MAIN;
