<?php

namespace Wikibase\Lexeme\Presentation\ChangeOp\Deserialization;

use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\Repo\ChangeOp\Deserialization\ClaimsChangeOpDeserializer;

/**
 * Class for creating ChangeOp for EditEntity API
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LexemeChangeOpDeserializer implements ChangeOpDeserializer {

	/**
	 * @var LemmaChangeOpDeserializer
	 */
	private $lemmaChangeOpDeserializer;

	/**
	 * @var LexicalCategoryChangeOpDeserializer
	 */
	private $lexicalCategoryChangeOpDeserializer;

	/**
	 * @var LanguageChangeOpDeserializer
	 */
	private $languageChangeOpDeserializer;

	/**
	 * @var ClaimsChangeOpDeserializer
	 */
	private $statementChangeOpDeserializer;

	/**
	 * @var FormListChangeOpDeserializer
	 */
	private $formListChangeOpDeserializer;

	/**
	 * @var SenseListChangeOpDeserializer
	 */
	private $senseListChangeOpDeserializer;

	/**
	 * @var ValidationContext
	 */
	private $validationContext;

	public function __construct(
		LemmaChangeOpDeserializer $lemmaChangeOpDeserializer,
		LexicalCategoryChangeOpDeserializer $lexicalCategoryChangeOpDeserializer,
		LanguageChangeOpDeserializer $languageChangeOpDeserializer,
		ClaimsChangeOpDeserializer $statementChangeOpDeserializer,
		FormListChangeOpDeserializer $formListChangeOpDeserializer,
		SenseListChangeOpDeserializer $senseListChangeOpDeserializer
	) {
		$this->lemmaChangeOpDeserializer = $lemmaChangeOpDeserializer;
		$this->lexicalCategoryChangeOpDeserializer = $lexicalCategoryChangeOpDeserializer;
		$this->languageChangeOpDeserializer = $languageChangeOpDeserializer;
		$this->statementChangeOpDeserializer = $statementChangeOpDeserializer;
		$this->formListChangeOpDeserializer = $formListChangeOpDeserializer;
		$this->senseListChangeOpDeserializer = $senseListChangeOpDeserializer;
	}

	/**
	 * @param ValidationContext $validationContext
	 */
	public function setContext( ValidationContext $validationContext ) {
		$this->validationContext = $validationContext;
	}

	/**
	 * @see ChangeOpDeserializer::createEntityChangeOp
	 *
	 * @param array[] $changeRequest
	 *
	 * @return ChangeOp
	 */
	public function createEntityChangeOp( array $changeRequest ) {
		$changeOps = new ChangeOps();

		if ( array_key_exists( 'lemmas', $changeRequest ) ) {
			$changeOps->add( $this->lemmaChangeOpDeserializer->createEntityChangeOp( $changeRequest ) );
		}

		if ( array_key_exists( 'lexicalCategory', $changeRequest ) ) {
			$changeOps->add(
				$this->lexicalCategoryChangeOpDeserializer->createEntityChangeOp( $changeRequest )
			);
		}

		if ( array_key_exists( 'language', $changeRequest ) ) {
			$changeOps->add( $this->languageChangeOpDeserializer->createEntityChangeOp( $changeRequest ) );
		}

		if ( array_key_exists( 'claims', $changeRequest ) ) {
			$changeOps->add( $this->statementChangeOpDeserializer->createEntityChangeOp( $changeRequest ) );
		}

		if ( array_key_exists( 'forms', $changeRequest ) ) {
			$this->formListChangeOpDeserializer->setContext( $this->validationContext->at( 'forms' ) );
			$changeOps->add( $this->formListChangeOpDeserializer->createEntityChangeOp( $changeRequest ) );
		}

		if ( array_key_exists( 'senses', $changeRequest ) ) {
			$this->senseListChangeOpDeserializer->setContext( $this->validationContext->at( 'senses' ) );
			$changeOps->add( $this->senseListChangeOpDeserializer->createEntityChangeOp( $changeRequest ) );
		}

		return $changeOps;
	}

}
