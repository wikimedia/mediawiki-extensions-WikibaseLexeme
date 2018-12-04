<?php

namespace Wikibase\Lexeme\MediaWiki\ParserOutput;

use ParserOutput;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Repo\ParserOutput\EntityParserOutputUpdater;
use Wikibase\Repo\ParserOutput\StatementDataUpdater;

/**
 * @license GPL-2.0-or-later
 */
class LexemeParserOutputUpdater implements EntityParserOutputUpdater {

	private $statementDataUpdater;

	public function __construct( StatementDataUpdater $statementDataUpdater ) {
		$this->statementDataUpdater = $statementDataUpdater;
	}

	public function updateParserOutput( ParserOutput $parserOutput, EntityDocument $entity ) {
		if ( $entity instanceof Lexeme ) {
			$this->updateParserOutputForLexeme( $parserOutput, $entity );
		}
	}

	public function updateParserOutputForLexeme( ParserOutput $parserOutput, Lexeme $lexeme ) {
		foreach ( $lexeme->getStatements() as $statement ) {
			$this->statementDataUpdater->processStatement( $statement );
		}

		foreach ( $lexeme->getForms()->toArray() as $form ) {
			foreach ( $form->getStatements() as $statement ) {
				$this->statementDataUpdater->processStatement( $statement );
			}
		}

		foreach ( $lexeme->getSenses()->toArray() as $sense ) {
			foreach ( $sense->getStatements() as $statement ) {
				$this->statementDataUpdater->processStatement( $statement );
			}
		}

		$this->statementDataUpdater->updateParserOutput( $parserOutput );
	}

}
