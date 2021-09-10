<?php

namespace Wikibase\Lexeme\Presentation\ChangeOp\Deserialization;

use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\MediaWiki\Api\Error\ParameterIsNotSenseId;

/**
 * A throwing ValidationContext guards us from actual null return values,
 * w/o it the result is too fuzzy to regard it clean
 *
 * @license GPL-2.0-or-later
 */
class SenseIdDeserializer {

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	public function __construct( EntityIdParser $idParser ) {
		$this->entityIdParser = $idParser;
	}

	/**
	 * @param string $id
	 * @param ValidationContext $validationContext
	 * @return SenseId|null
	 */
	public function deserialize( $id, ValidationContext $validationContext ) {
		try {
			$senseId = $this->entityIdParser->parse( $id );
		} catch ( EntityIdParsingException $e ) {
			$validationContext->addViolation( new ParameterIsNotSenseId( $id ) );
			return null;
		}

		if ( $senseId->getEntityType() !== Sense::ENTITY_TYPE ) {
			$validationContext->addViolation( new ParameterIsNotSenseId( $id ) );
			return null;
		}

		/** @var SenseId $senseId */
		'@phan-var SenseId $senseId';
		return $senseId;
	}

}
