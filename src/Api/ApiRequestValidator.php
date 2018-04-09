<?php

namespace Wikibase\Lexeme\Api;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Wikibase\Lexeme\Api\Error\MessageApiError;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;

/**
 * @license GPL-2.0-or-later
 */
class ApiRequestValidator {

	/**
	 * @param array $changeRequest
	 * @param Constraint $constraint
	 *
	 * @throws ChangeOpDeserializationException
	 *
	 * @return ConstraintViolationListInterface
	 */
	public function validate( array $changeRequest, Constraint $constraint ) {
		$validator = Validation::createValidator();

		try {
			return $validator->validate( $changeRequest, $constraint );
		} catch ( \Symfony\Component\Validator\Exception\UnexpectedTypeException $exception ) {
			// https://github.com/symfony/symfony/issues/14943
			throw new ChangeOpDeserializationException(
				$exception->getMessage(),
				'bad-param-type'
			);
		}
	}

	/**
	 * Convert the first violation into an exception
	 *
	 * Converting the message to string deprives us of the capability to inspect
	 * the params later (e.g. tests) but allows for i18n based messages, in contrast
	 * to other occurrences of ChangeOpDeserializationException.
	 *
	 * A violation "message" in the validator/constraint scope is a mediawiki message key, as set
	 * in e.g. Api/Constraint/RemoveFormConstraint
	 *
	 * @param ConstraintViolationListInterface $violationList
	 */
	public function convertViolationsToException( ConstraintViolationListInterface $violationList ) {
		foreach ( $violationList as $violation ) {
			/**
			 * @var $violation ConstraintViolationInterface
			 */
			throw new ChangeOpDeserializationException(
				$this->violationToMessage( $violation )->plain(),
				$violation->getMessage(),
				$this->getViolationParams( $violation )
			);
		}
	}

	/**
	 * Convert the first violation into an exception
	 *
	 * @param ConstraintViolationListInterface $violationList
	 *
	 * @return MessageApiError[]
	 */
	public function convertViolationsToApiErrors( ConstraintViolationListInterface $violationList ) {
		$errors = [];
		foreach ( $violationList as $violation ) {
			/**
			 * @var $violation ConstraintViolationInterface
			 */
			$errors[] = new MessageApiError( $this->violationToMessage( $violation ) );
		}

		return $errors;
	}

	private function violationToMessage( ConstraintViolationInterface $violation ) {
		return \Message::newFromKey( $violation->getMessage() )
			->params( $this->getViolationParams( $violation ) );
	}

	private function getViolationParams( ConstraintViolationInterface $violation ) {
		return [
			$violation->getPropertyPath(),
			$violation->getInvalidValue()
		];
	}

}
