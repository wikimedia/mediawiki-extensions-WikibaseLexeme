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

	public function __construct( LemmaChangeOpDeserializer $lemmaChangeOpDeserializer ) {
		$this->lemmaChangeOpDeserializer = $lemmaChangeOpDeserializer;
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

		return $changeOps;
	}

}
