<?php

namespace Wikibase\Lexeme\DataModel;

use Wikibase\DataModel\Entity\EntityId;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class FormId extends EntityId {

	const PATTERN = '/^L[1-9]\d*-F[1-9]\d*\z/';

	/**
	 * @param string $serialization
	 */
	public function __construct( $serialization ) {
		Assert::parameterType( 'string', $serialization, '$serialization' );
		Assert::parameter(
			preg_match( self::PATTERN, $serialization ),
			'$serialization',
			'Form ID must match "' . self::PATTERN . '", given: ' . $serialization
		);

		$this->serialization = $serialization;
		$this->repositoryName = '';
		$this->localPart = $serialization;
	}

	/**
	 * @return string
	 */
	public function getEntityType() {
		return 'form';
	}

	/**
	 * @return string
	 */
	public function getSerialization() {
		return $this->serialization;
	}

	/**
	 * @return string
	 */
	public function serialize() {
		return $this->serialization;
	}

	/**
	 * @param string $serialized
	 */
	public function unserialize( $serialized ) {
		$this->serialization = $serialized;
		$this->repositoryName = '';
		$this->localPart = $serialized;
	}

}
