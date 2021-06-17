<?php

namespace Wikibase\Lexeme\Presentation\Rdf;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lexeme\Domain\Model\FormSet;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\Domain\Model\SenseSet;
use Wikibase\Repo\Rdf\EntityMentionListener;
use Wikibase\Repo\Rdf\EntityRdfBuilder;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikimedia\Purtle\RdfWriter;

/**
 * Rdf builder for parts of lexeme
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LexemeSpecificComponentsRdfBuilder implements EntityRdfBuilder {

	private const NS_ONTOLEX = 'ontolex';
	private const NS_DUBLIN_CORE_TERM = 'dct';

	/**
	 * @var RdfVocabulary
	 */
	private $vocabulary;

	/**
	 * @var RdfWriter
	 */
	private $writer;

	/**
	 * @var EntityMentionListener
	 */
	private $entityMentionTracker;

	public function __construct(
		RdfVocabulary $vocabulary,
		RdfWriter $writer,
		EntityMentionListener $entityMentionTracker
	) {
		$this->vocabulary = $vocabulary;
		$this->writer = $writer;
		$this->entityMentionTracker = $entityMentionTracker;
	}

	/**
	 * Adds the prefixes used by the lexeme RDF mapping to the writer
	 * It should be executed before the writer starts
	 */
	public function addPrefixes() {
		$this->writer->prefix( self::NS_ONTOLEX, 'http://www.w3.org/ns/lemon/ontolex#' );
		$this->writer->prefix( self::NS_DUBLIN_CORE_TERM, 'http://purl.org/dc/terms/' );
	}

	/**
	 * Map WikibaseLexeme entities to the RDF graph
	 *
	 * @param EntityDocument $entity
	 */
	public function addEntity( EntityDocument $entity ) {
		if ( $entity instanceof Lexeme ) {
			$this->addLexeme( $entity );
		}
		if ( $entity instanceof Form ) {
			$this->addForm( $entity );
		}
		if ( $entity instanceof Sense ) {
			$this->addSense( $entity );
		}
	}

	/**
	 * Map a Lexeme to the RDF graph
	 *
	 * @param Lexeme $lexeme
	 */
	private function addLexeme( Lexeme $lexeme ) {
		$lexemeLName = $this->vocabulary->getEntityLName( $lexeme->getId() );
		$repositoryName = $this->vocabulary->getEntityRepositoryName( $lexeme->getId() );
		$lexemePrefix = $this->vocabulary->entityNamespaceNames[$repositoryName];

		$this->addLexemeTypes( $lexemePrefix, $lexemeLName );
		$this->addLemmas( $lexemePrefix, $lexemeLName, $lexeme->getLemmas() );
		$this->addLanguage( $lexemePrefix, $lexemeLName, $lexeme->getLanguage() );
		$this->addLexicalCategory( $lexemePrefix, $lexemeLName, $lexeme->getLexicalCategory() );
		$this->addForms( $lexemePrefix, $lexemeLName, $lexeme->getForms() );
		$this->addSenses( $lexemePrefix, $lexemeLName, $lexeme->getSenses() );
	}

	/**
	 * Adds the types of the given lexeme to the RDF graph
	 *
	 * @param string $lexemePrefix
	 * @param string $lexemeLName
	 */
	private function addLexemeTypes( $lexemePrefix, $lexemeLName ) {
		$this->writer->about( $lexemePrefix, $lexemeLName )
			->a( self::NS_ONTOLEX, 'LexicalEntry' );
	}

	/**
	 * Adds the lemmas of the given lexeme to the RDF graph
	 *
	 * @param string $lexemePrefix
	 * @param string $lexemeLName
	 * @param TermList $lemmas
	 */
	private function addLemmas( $lexemePrefix, $lexemeLName, TermList $lemmas ) {
		foreach ( $lemmas->toTextArray() as $lemmaCode => $lemmaText ) {
			$this->writer->about( $lexemePrefix, $lexemeLName )
				->say( 'rdfs', 'label' )
				->text( $lemmaText, $lemmaCode )
				->say( RdfVocabulary::NS_ONTOLOGY, 'lemma' )
				->text( $lemmaText, $lemmaCode );
		}
	}

	/**
	 * Adds the language of the given lexeme to the RDF graph
	 *
	 * @param string $lexemePrefix
	 * @param string $lexemeLName
	 * @param ItemId $language
	 */
	private function addLanguage( $lexemePrefix, $lexemeLName, ItemId $language ) {
		$languageLName = $this->vocabulary->getEntityLName( $language );
		$this->entityMentionTracker->entityReferenceMentioned( $language );
		$repositoryName = $this->vocabulary->getEntityRepositoryName( $language );

		$this->writer->about( $lexemePrefix, $lexemeLName )
			->say( self::NS_DUBLIN_CORE_TERM, 'language' )
			->is( $this->vocabulary->entityNamespaceNames[$repositoryName], $languageLName );
	}

	/**
	 * Adds the lexical category of the given lexeme to the RDF graph
	 *
	 * @param string $lexemePrefix
	 * @param string $lexemeLName
	 * @param ItemId $lexicalCategory
	 */
	private function addLexicalCategory( $lexemePrefix, $lexemeLName, ItemId $lexicalCategory ) {
		$lexicalCategoryLName = $this->vocabulary->getEntityLName( $lexicalCategory );
		$this->entityMentionTracker->entityReferenceMentioned( $lexicalCategory );
		$repositoryName = $this->vocabulary->getEntityRepositoryName( $lexicalCategory );

		$this->writer->about( $lexemePrefix, $lexemeLName )
			->say( RdfVocabulary::NS_ONTOLOGY, 'lexicalCategory' )
			->is( $this->vocabulary->entityNamespaceNames[$repositoryName], $lexicalCategoryLName );
	}

	/**
	 * Adds the forms of the given lexeme to the RDF graph
	 *
	 * @param string $lexemePrefix
	 * @param string $lexemeLName
	 * @param FormSet $forms
	 */
	private function addForms( $lexemePrefix, $lexemeLName, FormSet $forms ) {
		foreach ( $forms->toArray() as $form ) {
			$this->entityMentionTracker->subEntityMentioned( $form );

			$formLName = $this->vocabulary->getEntityLName( $form->getId() );
			$this->writer->about( $lexemePrefix, $lexemeLName )
				->say( self::NS_ONTOLEX, 'lexicalForm' )
				->is( $lexemePrefix, $formLName );
		}
	}

	/**
	 * Adds the senses of the given lexeme to the RDF graph
	 *
	 * @param string $lexemePrefix
	 * @param string $lexemeLName
	 * @param SenseSet $senses
	 */
	private function addSenses( $lexemePrefix, $lexemeLName, SenseSet $senses ) {
		foreach ( $senses->toArray() as $sense ) {
			$this->entityMentionTracker->subEntityMentioned( $sense );

			$senseLName = $this->vocabulary->getEntityLName( $sense->getId() );
			$this->writer->about( $lexemePrefix, $lexemeLName )
				->say( self::NS_ONTOLEX, 'sense' )
				->is( $lexemePrefix, $senseLName );
		}
	}

	/**
	 * Map a Form to the RDF graph
	 *
	 * @param Form $form
	 */
	private function addForm( Form $form ) {
		$formLName = $this->vocabulary->getEntityLName( $form->getId() );
		$repositoryName = $this->vocabulary->getEntityRepositoryName( $form->getId() );
		$lexemePrefix = $this->vocabulary->entityNamespaceNames[$repositoryName];

		$this->addFormTypes( $lexemePrefix, $formLName );
		$this->addRepresentations( $lexemePrefix, $formLName, $form->getRepresentations() );
		$this->addGrammaticalFeatures( $lexemePrefix, $formLName, $form->getGrammaticalFeatures() );
	}

	/**
	 * Adds the types of the given form to the RDF graph
	 *
	 * @param string $lexemePrefix
	 * @param string $formLName
	 */
	private function addFormTypes( $lexemePrefix, $formLName ) {
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
	private function addRepresentations( $lexemePrefix, $formLName, TermList $representations ) {
		foreach ( $representations->toTextArray() as $representationCode => $representationText ) {
			$this->writer->about( $lexemePrefix, $formLName )
				->say( 'rdfs', 'label' )
				->text( $representationText, $representationCode )
				->say( self::NS_ONTOLEX, 'representation' )
				->text( $representationText, $representationCode );
		}
	}

	/**
	 * Adds the grammatical features of the given form to the RDF graph
	 *
	 * @param string $lexemePrefix
	 * @param string $formLName
	 * @param ItemId[] $grammaticalFeatures
	 */
	private function addGrammaticalFeatures( $lexemePrefix, $formLName, array $grammaticalFeatures ) {
		foreach ( $grammaticalFeatures as $grammaticalFeature ) {
			$grammaticalFeatureLName = $this->vocabulary->getEntityLName( $grammaticalFeature );
			$this->entityMentionTracker->entityReferenceMentioned( $grammaticalFeature );
			$repositoryName = $this->vocabulary->getEntityRepositoryName( $grammaticalFeature );

			$this->writer->about( $lexemePrefix, $formLName )
				->say( RdfVocabulary::NS_ONTOLOGY, 'grammaticalFeature' )
				->is( $this->vocabulary->entityNamespaceNames[$repositoryName], $grammaticalFeatureLName );
		}
	}

	/**
	 * Map a Sense to the RDF graph
	 *
	 * @param Sense $sense
	 */
	private function addSense( Sense $sense ) {
		$senseLName = $this->vocabulary->getEntityLName( $sense->getId() );
		$repositoryName = $this->vocabulary->getEntityRepositoryName( $sense->getId() );
		$lexemePrefix = $this->vocabulary->entityNamespaceNames[$repositoryName];

		$this->addSenseTypes( $lexemePrefix, $senseLName );
		$this->addGlosses( $lexemePrefix, $senseLName, $sense->getGlosses() );
	}

	/**
	 * Adds the types of the given sense to the RDF graph
	 *
	 * @param string $lexemePrefix
	 * @param string $senseLName
	 */
	private function addSenseTypes( $lexemePrefix, $senseLName ) {
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
	private function addGlosses( $lexemePrefix, $senseLName, TermList $glosses ) {
		foreach ( $glosses->toTextArray() as $glossCode => $glossText ) {
			$this->writer->about( $lexemePrefix, $senseLName )
				->say( 'rdfs', 'label' )
				->text( $glossText, $glossCode )
				->say( RdfVocabulary::NS_SKOS, 'definition' )
				->text( $glossText, $glossCode );
		}
	}
}
