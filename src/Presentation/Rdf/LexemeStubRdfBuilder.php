<?php

declare( strict_types=1 );

namespace Wikibase\Lexeme\Presentation\Rdf;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Repo\Rdf\EntityStubRdfBuilder;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikimedia\Purtle\RdfWriter;

/**
 * @license GPL-2.0-or-later
 */
class LexemeStubRdfBuilder implements EntityStubRdfBuilder {

	private const NS_ONTOLEX = 'ontolex';

	/**
	 * @var RdfVocabulary
	 */
	private $vocabulary;

	/**
	 * @var RdfWriter
	 */
	private $writer;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	public function __construct(
		RdfVocabulary $vocabulary,
		RdfWriter $writer,
		EntityLookup $entityLookup
	) {
		$this->vocabulary = $vocabulary;
		$this->writer = $writer;
		$this->entityLookup = $entityLookup;
	}

	/**
	 * Adds the prefixes used by the lexeme RDF mapping to the writer
	 * It should be executed before the writer starts
	 */
	public function addPrefixes(): void {
		$this->writer->prefix( self::NS_ONTOLEX, 'http://www.w3.org/ns/lemon/ontolex#' );
	}

	/**
	 * Map some aspect of an entity to the RDF graph, as it should appear in the stub
	 * representation of the entity.
	 *
	 * @param EntityId $entityId
	 */
	public function addEntityStub( EntityId $entityId ): void {

		$entity = $this->entityLookup->getEntity( $entityId );

		if ( $entity instanceof Lexeme ) {
			$this->addLexemeStub( $entity );
		}
		if ( $entity instanceof Form ) {
			$this->addFormStub( $entity );
		}
		if ( $entity instanceof Sense ) {
			$this->addSenseStub( $entity );
		}
	}

	/**
	 * Map some aspect of a Lexeme to the RDF graph, as it should appear in the stub
	 * representation of the lexeme.
	 *
	 * @param Lexeme $lexeme
	 */
	private function addLexemeStub( Lexeme $lexeme ): void {
		$lexemeId = $lexeme->getId();
		$lexemeLName = $this->vocabulary->getEntityLName( $lexemeId );
		$repositoryName = $this->vocabulary->getEntityRepositoryName( $lexemeId );
		$lexemePrefix = $this->vocabulary->entityNamespaceNames[ $repositoryName ];

		$this->addLexemeTypes( $lexemePrefix, $lexemeLName );

		$this->addLemmas( $lexemePrefix, $lexemeLName, $lexeme->getLemmas() );
	}

	/**
	 * Map some aspect of a Form to the RDF graph, as it should appear in the stub
	 * representation of the form.
	 *
	 * @param Form $form
	 */
	private function addFormStub( Form $form ): void {
		$formLName = $this->vocabulary->getEntityLName( $form->getId() );
		$repositoryName = $this->vocabulary->getEntityRepositoryName( $form->getId() );
		$lexemePrefix = $this->vocabulary->entityNamespaceNames[$repositoryName];

		$this->addFormTypes( $lexemePrefix, $formLName );
		$this->addRepresentations( $lexemePrefix, $formLName, $form->getRepresentations() );
	}

	/**
	 * Map some aspect of a Sense to the RDF graph, as it should appear in the stub
	 * representation of the sense.
	 *
	 * @param Sense $sense
	 */
	private function addSenseStub( Sense $sense ): void {
		$senseLName = $this->vocabulary->getEntityLName( $sense->getId() );
		$repositoryName = $this->vocabulary->getEntityRepositoryName( $sense->getId() );
		$lexemePrefix = $this->vocabulary->entityNamespaceNames[$repositoryName];

		$this->addSenseTypes( $lexemePrefix, $senseLName );
		$this->addGlosses( $lexemePrefix, $senseLName, $sense->getGlosses() );
	}

	/**
	 * Adds the types of the given form to the RDF graph
	 *
	 * @param string $lexemePrefix
	 * @param string $formLName
	 */
	private function addFormTypes( string $lexemePrefix, string $formLName ): void {
		$this->writer->about( $lexemePrefix, $formLName )
			->a( self::NS_ONTOLEX, 'Form' );
	}

	/**
	 * Adds the representations of the given form to the RDF graph
	 *
	 * @param string $lexemePrefix
	 * @param string $formLName
	 * @param TermList $representations
	 */
	private function addRepresentations( string $lexemePrefix, string $formLName, TermList $representations ): void {
		foreach ( $representations->toTextArray() as $representationCode => $representationText ) {
			$this->writer->about( $lexemePrefix, $formLName )
				->say( 'rdfs', 'label' )
				->text( $representationText, $representationCode )
				->say( self::NS_ONTOLEX, 'representation' )
				->text( $representationText, $representationCode );
		}
	}

	/**
	 * Adds the types of the given lexeme to the RDF graph
	 *
	 * @param string $lexemePrefix
	 * @param string $lexemeLName
	 */
	private function addLexemeTypes( string $lexemePrefix, string $lexemeLName ): void {
		$this->writer->about( $lexemePrefix, $lexemeLName )
			->a( self::NS_ONTOLEX, 'LexicalEntry' );
	}

	/**
	 * Adds the types of the given sense to the RDF graph
	 *
	 * @param string $lexemePrefix
	 * @param string $senseLName
	 */
	private function addSenseTypes( string $lexemePrefix, string $senseLName ): void {
		$this->writer->about( $lexemePrefix, $senseLName )
			->a( self::NS_ONTOLEX, 'LexicalSense' );
	}

	/**
	 * Adds the glosses of the given sense to the RDF graph
	 *
	 * @param string $lexemePrefix
	 * @param string $senseLName
	 * @param TermList $glosses
	 */
	private function addGlosses( string $lexemePrefix, string $senseLName, TermList $glosses ): void {
		foreach ( $glosses->toTextArray() as $glossCode => $glossText ) {
			$this->writer->about( $lexemePrefix, $senseLName )
				->say( 'rdfs', 'label' )
				->text( $glossText, $glossCode )
				->say( RdfVocabulary::NS_SKOS, 'definition' )
				->text( $glossText, $glossCode );
		}
	}

	/**
	 * Adds the lemmas of the given lexeme to the RDF graph
	 *
	 * @param string $lexemePrefix
	 * @param string $lexemeLName
	 * @param TermList $lemmas
	 */
	private function addLemmas( string $lexemePrefix, string $lexemeLName, TermList $lemmas ): void {
		foreach ( $lemmas->toTextArray() as $lemmaCode => $lemmaText ) {
			$this->writer->about( $lexemePrefix, $lexemeLName )
				->say( 'rdfs', 'label' )
				->text( $lemmaText, $lemmaCode )
				->say( RdfVocabulary::NS_ONTOLOGY, 'lemma' )
				->text( $lemmaText, $lemmaCode );
		}
	}

}
