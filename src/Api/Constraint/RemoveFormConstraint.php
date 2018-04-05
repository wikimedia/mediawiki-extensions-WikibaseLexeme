<?php

namespace Wikibase\Lexeme\Api\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Wikibase\Lexeme\DataModel\FormId;

/**
 * @license GPL-2.0-or-later
 */
class RemoveFormConstraint {

	/**
	 * @return Constraint
	 */
	public static function many() {
		return new Assert\Collection( [
			'fields' => [
				'forms' => new Assert\All( [
					self::one()
				] )
			],
			'allowExtraFields' => true
		] );
	}

	/**
	 * @return Constraint
	 */
	public static function one() {
		return new Assert\Collection( [
			'fields' => [
				'id' => new Assert\Regex( [
					'pattern' => FormId::PATTERN,
					'message' => 'wikibaselexeme-api-error-parameter-not-form-id'
				] ),
				'remove' => new Assert\Optional(), // a request w/o it is valid, just no-op

				// TODO solve the following globally
				'bot' => new Assert\Optional(),
				'token' => new Assert\Optional(),
			],
			'missingFieldsMessage' => 'wikibaselexeme-api-error-parameter-required'
		] );
	}

}
