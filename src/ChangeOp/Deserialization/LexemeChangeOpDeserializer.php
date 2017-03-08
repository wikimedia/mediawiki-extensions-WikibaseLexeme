<?php

namespace Wikibase\Lexeme\ChangeOp\Deserialization;

use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\ClaimsChangeOpDeserializer;

/**
 * Class for creating ChangeOp for EditEntity API
 *
 * @license GPL-2.0+
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

	private $statementChangeOpDeserializer;

	public function __construct(
		LemmaChangeOpDeserializer $lemmaChangeOpDeserializer,
		LexicalCategoryChangeOpDeserializer $lexicalCategoryChangeOpDeserializer,
		LanguageChangeOpDeserializer $languageChangeOpDeserializer,
		ClaimsChangeOpDeserializer $statementChangeOpDeserializer
	) {
		$this->lemmaChangeOpDeserializer = $lemmaChangeOpDeserializer;
		$this->lexicalCategoryChangeOpDeserializer = $lexicalCategoryChangeOpDeserializer;
		$this->languageChangeOpDeserializer = $languageChangeOpDeserializer;
		$this->statementChangeOpDeserializer = $statementChangeOpDeserializer;
	}

	/**
	 * @see ChangeOpDeserializer::createEntityChangeOp
	 *
	 * @param array[] $changeRequest
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

		return $changeOps;
	}

}
