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
	 * @param ConstraintViolationListInterface $violationList
	 */
	public function convertViolationsToException( ConstraintViolationListInterface $violationList ) {
		foreach ( $violationList as $violation ) {
			/**
			 * @var $violation ConstraintViolationInterface
			 */
			throw new ChangeOpDeserializationException(
				$this->violationToMessage( $violation ),
				$violation->getCode() // TODO code?
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
		return \Message::newFromKey( $violation->getMessage() )->params( [
			$violation->getPropertyPath(),
			$violation->getInvalidValue()
		] );
	}

}
