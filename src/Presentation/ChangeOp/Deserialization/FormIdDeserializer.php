<?php

namespace Wikibase\Lexeme\Presentation\ChangeOp\Deserialization;

use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\MediaWiki\Api\Error\ParameterIsNotFormId;

/**
 * A throwing ValidationContext guards us from actual null return values,
 * w/o it the result is too fuzzy to regard it clean
 *
 * @license GPL-2.0-or-later
 */
class FormIdDeserializer {

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	public function __construct( EntityIdParser $idParser ) {
		$this->entityIdParser = $idParser;
	}

	/**
	 * @param mixed $id
	 * @param ValidationContext $validationContext
	 * @return FormId|null
	 */
	public function deserialize( $id, ValidationContext $validationContext ) {
		if ( !is_string( $id ) ) {
			$validationContext->addViolation( new ParameterIsNotFormId( $id ) );
			return null;
		}

		try {
			$formId = $this->entityIdParser->parse( $id );
		} catch ( EntityIdParsingException ) {
			$validationContext->addViolation( new ParameterIsNotFormId( $id ) );
			return null;
		}

		if ( $formId->getEntityType() !== Form::ENTITY_TYPE ) {
			$validationContext->addViolation( new ParameterIsNotFormId( $id ) );
			return null;
		}

		/** @var FormId $formId */
		'@phan-var FormId $formId';
		return $formId;
	}

}
