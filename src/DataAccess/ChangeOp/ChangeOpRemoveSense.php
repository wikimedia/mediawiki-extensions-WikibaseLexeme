<?php

namespace Wikibase\Lexeme\DataAccess\ChangeOp;

use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOpBase;
use Wikibase\Repo\ChangeOp\DummyChangeOpResult;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class ChangeOpRemoveSense extends ChangeOpBase {

	private const SUMMARY_ACTION_REMOVE = 'remove-sense';

	/**
	 * @var SenseId
	 */
	private $senseId;

	/**
	 * @param SenseId $senseId
	 */
	public function __construct( SenseId $senseId ) {
		$this->senseId = $senseId;
	}

	public function validate( EntityDocument $entity ) {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );
		'@phan-var Lexeme $entity';

		/** @var Lexeme $entity */
		if ( $entity->getSenses()->getById( $this->senseId ) === null ) {
			return Result::newError( [
				Error::newError(
					'Sense does not exist',
					null,
					'sense-not-found',
					[ $this->senseId->serialize() ]
				),
			] );
		}

		return Result::newSuccess();
	}

	public function apply( EntityDocument $entity, Summary $summary = null ) {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );
		'@phan-var Lexeme $entity';

		/** @var Lexeme $entity */

		$sense = $entity->getSense( $this->senseId );
		$entity->removeSense( $this->senseId );

		if ( $sense->getGlosses()->count() === 1 ) {
			$array = $sense->getGlosses()->toTextArray();
			reset( $array );
			$language = key( $array );
		} else {
			$language = null;
		}

		$this->updateSummary(
			$summary,
			self::SUMMARY_ACTION_REMOVE,
			$language,
			array_values( $sense->getGlosses()->toTextArray() )
		);

		return new DummyChangeOpResult();
	}

	protected function updateSummary( ?Summary $summary, $action, $language = '', $args = '' ) {
		parent::updateSummary( $summary, $action, $language, $args );
		if ( $summary !== null ) {
			$summary->addAutoCommentArgs( [ $this->senseId->getSerialization() ] );
		}
	}

}
