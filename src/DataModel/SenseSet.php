<?php

namespace Wikibase\Lexeme\DataModel;

use Countable;
use InvalidArgumentException;

/**
 * Set of Senses in which uniqueness of a Sense is controlled by its ID.
 * Supposed to be used only inside the Lexeme class.
 *
 * @license GPL-2.0-or-later
 */
class SenseSet implements Countable {

	/**
	 * @var Sense[] indexed by serialization of SenseId
	 */
	private $senses = [];

	/**
	 * @param Sense[] $senses
	 */
	public function __construct( array $senses = [] ) {
		foreach ( $senses as $sense ) {
			if ( !$sense instanceof Sense ) {
				throw new InvalidArgumentException( '$senses must be an array of Senses' );
			}

			$this->add( $sense );
		}
	}

	/**
	 * @return Sense[]
	 */
	public function toArray() {
		$senses = $this->senses;
		ksort( $senses );
		return array_values( $senses );
	}

	/**
	 * @return int
	 */
	public function count() {
		return count( $this->senses );
	}

	/**
	 * @return int
	 */
	public function maxSenseIdNumber() {
		$max = 0;

		foreach ( $this->senses as $senseId => $sense ) {
			$senseIdPart = explode( '-', $senseId )[1];
			$senseIdNumber = (int)substr( $senseIdPart, 1 );
			if ( $senseIdNumber > $max ) {
				$max = $senseIdNumber;
			}
		}

		return $max;
	}

	public function add( Sense $sense ) {
		$senseId = $sense->getId()->getSerialization();
		if ( array_key_exists( $senseId, $this->senses ) ) {
			throw new InvalidArgumentException(
				'At least two senses with the same ID were provided: `' . $senseId . '`'
			);
		}

		$this->senses[$senseId] = $sense;
	}

	public function remove( SenseId $senseId ) {
		unset( $this->senses[$senseId->getSerialization()] );
	}

	/**
	 * Replace the sense identified by $sense->getId() with the given one or add it.
	 *
	 * @param Sense $sense
	 */
	public function put( Sense $sense ) {
		$this->remove( $sense->getId() );
		$this->add( $sense );
	}

	/**
	 * @param SenseId $senseId
	 *
	 * @return Sense|null
	 */
	public function getById( SenseId $senseId ) {
		return array_key_exists( $senseId->getSerialization(), $this->senses ) ?
			$this->senses[$senseId->getSerialization()] :
			null;
	}

	/**
	 * @return self
	 */
	public function copy() {
		return clone $this;
	}

	/**
	 * @see http://php.net/manual/en/language.oop5.cloning.php
	 */
	public function __clone() {
		$clonedSenses = [];
		foreach ( $this->senses as $key => $sense ) {
			$clonedSenses[$key] = clone $sense;
		}
		$this->senses = $clonedSenses;
	}

	/**
	 * @return bool
	 */
	public function isEmpty() {
		return $this->senses === [];
	}

}
