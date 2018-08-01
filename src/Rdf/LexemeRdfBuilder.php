<?php

namespace Wikibase\Lexeme\Rdf;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormSet;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\Sense;
use Wikibase\Lexeme\DataModel\SenseSet;
use Wikibase\Rdf\EntityMentionListener;
use Wikibase\Rdf\EntityRdfBuilder;
use Wikibase\Rdf\RdfVocabulary;
use Wikimedia\Purtle\RdfWriter;

/**
 * Factory to return Rdf builders for parts of lexeme
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LexemeRdfBuilder implements EntityRdfBuilder {

	const NS_ONTOLEX = 'ontolex';
	const NS_DUBLIN_CORE_TERM = 'dct';

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
	private $entityMentionTraker;

	public function __construct(
		RdfVocabulary $vocabulary,
		RdfWriter $writer,
		EntityMentionListener $entityMentionTraker
	) {
		$this->vocabulary = $vocabulary;
		$this->writer = $writer;
		$this->entityMentionTraker = $entityMentionTraker;
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

		$this->addLexemeTypes( $lexemeLName );
		$this->addLemmas( $lexemeLName, $lexeme->getLemmas() );
		$this->addLanguage( $lexemeLName, $lexeme->getLanguage() );
		$this->addLexicalCategory( $lexemeLName, $lexeme->getLexicalCategory() );
		$this->addFormsLink( $lexemeLName, $lexeme->getForms() );
		$this->addSensesLink( $lexemeLName, $lexeme->getSenses() );
		$this->addFormsContent( $lexeme->getForms() );
		$this->addSensesContent( $lexeme->getSenses() );
	}

	/**
	 * Adds the types of the given lexeme to the RDF graph
	 *
	 * @param string $lexemeLName
	 */
	private function addLexemeTypes( $lexemeLName ) {
		$this->writer->about( RdfVocabulary::NS_ENTITY, $lexemeLName )
			->a( self::NS_ONTOLEX, 'LexicalEntry' );
	}

	/**
	 * Adds the lemmas of the given lexeme to the RDF graph
	 *
	 * @param string $lexemeLName
	 * @param TermList $lemmas
	 */
	private function addLemmas( $lexemeLName, TermList $lemmas ) {
		foreach ( $lemmas->toTextArray() as $lemmaCode => $lemmaText ) {
			$this->writer->about( RdfVocabulary::NS_ENTITY, $lexemeLName )
				->say( 'rdfs', 'label' )
				->text( $lemmaText, $lemmaCode )
				->say( RdfVocabulary::NS_SKOS, 'prefLabel' )
				->text( $lemmaText, $lemmaCode )
				->say( RdfVocabulary::NS_SCHEMA_ORG, 'name' )
				->text( $lemmaText, $lemmaCode )
				->say( RdfVocabulary::NS_ONTOLOGY, 'lemma' )
				->text( $lemmaText, $lemmaCode );
		}
	}

	/**
	 * Adds the language of the given lexeme to the RDF graph
	 *
	 * @param string $lexemeLName
	 * @param ItemId $language
	 */
	private function addLanguage( $lexemeLName, ItemId $language ) {
		$languageLName = $this->vocabulary->getEntityLName( $language );
		$this->entityMentionTraker->entityReferenceMentioned( $language );

		$this->writer->about( RdfVocabulary::NS_ENTITY, $lexemeLName )
			->say( self::NS_DUBLIN_CORE_TERM, 'language' )
			->is( RdfVocabulary::NS_ENTITY, $languageLName );
	}

	/**
	 * Adds the lexical category of the given lexeme to the RDF graph
	 *
	 * @param string $lexemeLName
	 * @param ItemId $lexicalCategory
	 */
	private function addLexicalCategory( $lexemeLName, ItemId $lexicalCategory ) {
		$lexicalCategoryLName = $this->vocabulary->getEntityLName( $lexicalCategory );
		$this->entityMentionTraker->entityReferenceMentioned( $lexicalCategory );

		$this->writer->about( RdfVocabulary::NS_ENTITY, $lexemeLName )
			->say( RdfVocabulary::NS_ONTOLOGY, 'lexicalCategory' )
			->is( RdfVocabulary::NS_ENTITY, $lexicalCategoryLName );
	}

	/**
	 * Adds the links to the forms of the given lexeme to the RDF graph
	 *
	 * @param string $lexemeLName
	 * @param FormSet $forms
	 */
	private function addFormsLink( $lexemeLName, FormSet $forms ) {
		foreach ( $forms->toArray() as $form ) {
			$formLName = $this->vocabulary->getEntityLName( $form->getId() );
			$this->writer->about( RdfVocabulary::NS_ENTITY, $lexemeLName )
				->say( self::NS_ONTOLEX, 'lexicalForm' )
				->is( RdfVocabulary::NS_ENTITY, $formLName );
		}
	}

	/**
	 * Adds the content of the forms of the given lexeme to the RDF graph
	 *
	 * @param FormSet $forms
	 */
	private function addFormsContent( FormSet $forms ) {
		foreach ( $forms->toArray() as $form ) {
			$this->addForm( $form );
		}
	}

	/**
	 * Map a Form to the RDF graph
	 *
	 * @param Form $form
	 */
	private function addForm( Form $form ) {
		$formLName = $this->vocabulary->getEntityLName( $form->getId() );

		$this->addFormTypes( $formLName );
		$this->addRepresentations( $formLName, $form->getRepresentations() );
		$this->addGrammaticalFeatures( $formLName, $form->getGrammaticalFeatures() );
	}

	/**
	 * Adds the types of the given form to the RDF graph
	 *
	 * @param string $formLName
	 */
	private function addFormTypes( $formLName ) {
		$this->writer->about( RdfVocabulary::NS_ENTITY, $formLName )
			->a( self::NS_ONTOLEX, 'Form' );
	}

	/**
	 * Adds the representations of the given form to the RDF graph
	 *
	 * @param string $formLName
	 * @param TermList $representations
	 */
	private function addRepresentations( $formLName, TermList $representations ) {
		foreach ( $representations->toTextArray() as $representationCode => $representationText ) {
			$this->writer->about( RdfVocabulary::NS_ENTITY, $formLName )
				->say( 'rdfs', 'label' )
				->text( $representationText, $representationCode )
				->say( RdfVocabulary::NS_SKOS, 'prefLabel' )
				->text( $representationText, $representationCode )
				->say( RdfVocabulary::NS_SCHEMA_ORG, 'name' )
				->text( $representationText, $representationCode )
				->say( self::NS_ONTOLEX, 'representation' )
				->text( $representationText, $representationCode );
		}
	}

	/**
	 * Adds the grammatical features of the given form to the RDF graph
	 *
	 * @param string $formLName
	 * @param ItemId[] $grammaticalFeatures
	 */
	private function addGrammaticalFeatures( $formLName, array $grammaticalFeatures ) {
		foreach ( $grammaticalFeatures as $grammaticalFeature ) {
			$grammaticalFeatureLName = $this->vocabulary->getEntityLName( $grammaticalFeature );
			$this->entityMentionTraker->entityReferenceMentioned( $grammaticalFeature );

			$this->writer->about( RdfVocabulary::NS_ENTITY, $formLName )
				->say( RdfVocabulary::NS_ONTOLOGY, 'grammaticalFeature' )
				->is( RdfVocabulary::NS_ENTITY, $grammaticalFeatureLName );
		}
	}

	/**
	 * Adds the links to the senses of the given lexeme to the RDF graph
	 *
	 * @param string $lexemeLName
	 * @param SenseSet $senses
	 */
	private function addSensesLink( $lexemeLName, SenseSet $senses ) {
		foreach ( $senses->toArray() as $sense ) {
			$senseLName = $this->vocabulary->getEntityLName( $sense->getId() );
			$this->writer->about( RdfVocabulary::NS_ENTITY, $lexemeLName )
				->say( self::NS_ONTOLEX, 'sense' )
				->is( RdfVocabulary::NS_ENTITY, $senseLName );
		}
	}

	/**
	 * Adds the content of the senses of the given lexeme to the RDF graph
	 *
	 * @param SenseSet $senses
	 */
	private function addSensesContent( SenseSet $senses ) {
		foreach ( $senses->toArray() as $sense ) {
			$this->addSense( $sense );
		}
	}

	/**
	 * Map a Sense to the RDF graph
	 *
	 * @param Sense $sense
	 */
	private function addSense( Sense $sense ) {
		$senseLName = $this->vocabulary->getEntityLName( $sense->getId() );

		$this->addSenseTypes( $senseLName );
		$this->addGlosses( $senseLName, $sense->getGlosses() );
	}

	/**
	 * Adds the types of the given sense to the RDF graph
	 *
	 * @param string $senseLName
	 */
	private function addSenseTypes( $senseLName ) {
		$this->writer->about( RdfVocabulary::NS_ENTITY, $senseLName )
			->a( self::NS_ONTOLEX, 'LexicalSense' );
	}

	/**
	 * Adds the glosses of the given sense to the RDF graph
	 *
	 * @param string $senseLName
	 * @param TermList $glosses
	 */
	private function addGlosses( $senseLName, TermList $glosses ) {
		foreach ( $glosses->toTextArray() as $glossCode => $glossText ) {
			$this->writer->about( RdfVocabulary::NS_ENTITY, $senseLName )
				->say( 'rdfs', 'label' )
				->text( $glossText, $glossCode )
				->say( RdfVocabulary::NS_SKOS, 'definition' )
				->text( $glossText, $glossCode );
		}
	}

	/**
	 * Map some aspect of an entity to the RDF graph, as it should appear in the stub
	 * representation of the entity.
	 *
	 * @param EntityDocument $entity
	 */
	public function addEntityStub( EntityDocument $entity ) {
		if ( $entity instanceof Lexeme ) {
			$this->addLexemeSub( $entity );
		}
		if ( $entity instanceof Form ) {
			$this->addFormSub( $entity );
		}
		if ( $entity instanceof Sense ) {
			$this->addSenseSub( $entity );
		}
	}

	/**
	 * Map some aspect of a Lexeme to the RDF graph, as it should appear in the stub
	 * representation of the lexeme.
	 *
	 * @param Lexeme $lexeme
	 */
	private function addLexemeSub( Lexeme $lexeme ) {
		$lexemeLName = $this->vocabulary->getEntityLName( $lexeme->getId() );

		$this->addLexemeTypes( $lexemeLName );
		$this->addLemmas( $lexemeLName, $lexeme->getLemmas() );
	}

	/**
	 * Map some aspect of a Form to the RDF graph, as it should appear in the stub
	 * representation of the form.
	 *
	 * @param Form $form
	 */
	private function addFormSub( Form $form ) {
		$formLName = $this->vocabulary->getEntityLName( $form->getId() );

		$this->addFormTypes( $formLName );
		$this->addRepresentations( $formLName, $form->getRepresentations() );
	}

	/**
	 * Map some aspect of a Sense to the RDF graph, as it should appear in the stub
	 * representation of the sense.
	 *
	 * @param Sense $sense
	 */
	private function addSenseSub( Sense $sense ) {
		$senseLName = $this->vocabulary->getEntityLName( $sense->getId() );

		$this->addSenseTypes( $senseLName );
		$this->addGlosses( $senseLName, $sense->getGlosses() );
	}

}
