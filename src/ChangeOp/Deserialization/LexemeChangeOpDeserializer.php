<?php

namespace Wikibase\Lexeme\ChangeOp\Deserialization;

use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;

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
	 * @var LanguageChangeOpDeserializer
	 */
	private $languageChangeOpDeserializer;

	public function __construct(
		LemmaChangeOpDeserializer $lemmaChangeOpDeserializer,
		LanguageChangeOpDeserializer $languageChangeOpDeserializer
	) {
		$this->lemmaChangeOpDeserializer = $lemmaChangeOpDeserializer;
		$this->languageChangeOpDeserializer = $languageChangeOpDeserializer;
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

		if ( array_key_exists( 'language', $changeRequest ) ) {
			$changeOps->add( $this->languageChangeOpDeserializer->createEntityChangeOp( $changeRequest ) );
		}

		return $changeOps;
	}

}
