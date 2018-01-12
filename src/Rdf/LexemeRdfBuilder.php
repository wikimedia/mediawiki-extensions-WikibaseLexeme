<?php

namespace Wikibase\Lexeme\Rdf;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Rdf\EntityRdfBuilder;
use Wikibase\Rdf\RdfVocabulary;
use Wikimedia\Purtle\RdfWriter;

/**
 * Factory to return Rdf builders for parts of lexeme
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LexemeRdfBuilder implements EntityRdfBuilder {

	/**
	 * @var RdfVocabulary
	 */
	private $vocabulary;

	/**
	 * @var RdfWriter
	 */
	private $writer;

	public function __construct( RdfVocabulary $vocabulary, RdfWriter $writer ) {
		$this->vocabulary = $vocabulary;
		$this->writer = $writer;
	}

	/**
	 * Adds the lemmas of the given entity to the RDF graph
	 *
	 * @param string $entityLName
	 * @param TermList $lemmas
	 */
	public function addLemmas( $entityLName, TermList $lemmas ) {
		foreach ( $lemmas->toTextArray() as $lemmaCode => $lemmaText ) {
			$this->writer->about( RdfVocabulary::NS_ENTITY, $entityLName )
				->say( 'rdfs', 'label' )
				->text( $lemmaText, $lemmaCode )
				->say( RdfVocabulary::NS_SKOS, 'prefLabel' )
				->text( $lemmaText, $lemmaCode )
				->say( RdfVocabulary::NS_SCHEMA_ORG, 'name' )
				->text( $lemmaText, $lemmaCode );
		}
	}

	/**
	 * Map a Lexeme to the RDF graph
	 *
	 * @param EntityDocument $entity
	 */
	public function addEntity(
		EntityDocument $entity
	) {
		if ( !$entity instanceof Lexeme ) {
			return;
		}
		$lexemeLName = $this->vocabulary->getEntityLName( $entity->getId() );

		$this->addLemmas( $lexemeLName, $entity->getLemmas() );
		// TODO: Implement other parts.
	}

	/**
	 * Map some aspect of a Lexeme to the RDF graph, as it should appear in the stub
	 * representation of the lexeme.
	 *
	 * @param EntityDocument $entity
	 */
	public function addEntityStub( EntityDocument $entity ) {
		if ( !$entity instanceof Lexeme ) {
			return;
		}
		$lexemeLName = $this->vocabulary->getEntityLName( $entity->getId() );

		$this->addLemmas( $lexemeLName, $entity->getLemmas() );
	}

}
