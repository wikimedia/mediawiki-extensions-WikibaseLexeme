<?php

namespace Wikibase\Lexeme\ChangeOp;

use Wikibase\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\NullChangeOp;
use Wikibase\StringNormalizer;

/**
 * Class for creating ChangeOp for EditEntity API
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LexemeChangeOpDeserializer implements ChangeOpDeserializer {

	/**
	 * @see ChangeOpDeserializer::createEntityChangeOp
	 *
	 * @param array[] $changeRequest
	 * @return ChangeOp
	 */
	public function createEntityChangeOp( array $changeRequest ) {
		// Do nothing
		return new NullChangeOp();
	}

}
