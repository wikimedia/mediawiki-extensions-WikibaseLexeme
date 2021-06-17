<?php

namespace Wikibase\Lexeme\Presentation\Rdf;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Repo\Rdf\EntityRdfBuilder;
use Wikibase\Repo\Rdf\FullStatementRdfBuilderFactory;
use Wikibase\Repo\Rdf\RdfProducer;
use Wikibase\Repo\Rdf\TruthyStatementRdfBuilderFactory;

/**
 * @license GPL-2.0-or-later
 */

class LexemeRdfBuilder implements EntityRdfBuilder {
	private $truthyStatementRdfBuilder;
	private $fullStatementRdfBuilder;
	private $lexemeSpecificComponentsRdfBuilder;

	public function __construct(
		int $flavorFlags,
		TruthyStatementRdfBuilderFactory $truthyStatementRdfBuilderFactory,
		FullStatementRdfBuilderFactory $fullStatementRdfBuilderFactory,
		LexemeSpecificComponentsRdfBuilder $lexemeSpecificComponentsRdfBuilder
	) {
		if ( $flavorFlags & RdfProducer::PRODUCE_TRUTHY_STATEMENTS ) {
			$this->truthyStatementRdfBuilder = $truthyStatementRdfBuilderFactory->getTruthyStatementRdfBuilder(
				$flavorFlags
			);
		}
		if ( $flavorFlags & RdfProducer::PRODUCE_ALL_STATEMENTS ) {
			$fullStatementRdfBuilder = $fullStatementRdfBuilderFactory->getFullStatementRdfBuilder(
				$flavorFlags
			);
			$this->fullStatementRdfBuilder = $fullStatementRdfBuilder;
		}
		$this->lexemeSpecificComponentsRdfBuilder = $lexemeSpecificComponentsRdfBuilder;
	}

	public function addEntity( EntityDocument $entity ): void {
		if ( $this->truthyStatementRdfBuilder ) {
			$this->truthyStatementRdfBuilder->addEntity( $entity );
		}
		if ( $this->fullStatementRdfBuilder ) {
			$this->fullStatementRdfBuilder->addEntity( $entity );
		}
		$this->lexemeSpecificComponentsRdfBuilder->addEntity( $entity );
	}
}
