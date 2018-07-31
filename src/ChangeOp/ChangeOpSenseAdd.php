<?php

namespace Wikibase\Lexeme\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataTransfer\BlankSense;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpBase;
use Wikibase\Summary;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class ChangeOpSenseAdd extends ChangeOpBase {

	const SUMMARY_ACTION_ADD = 'add-sense';

	/**
	 * @var ChangeOp
	 */
	private $changeOpSense;

	/**
	 * @param ChangeOp $changeOpSense
	 */
	public function __construct( ChangeOp $changeOpSense ) {
		$this->changeOpSense = $changeOpSense;
	}

	public function validate( EntityDocument $entity ) {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );

		return Result::newSuccess();
	}

	public function apply( EntityDocument $entity, Summary $summary = null ) {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );

		/** @var Lexeme $entity */

		$blankSense = new BlankSense();
		$blankSense->setLexeme( $entity );

		$this->changeOpSense->apply( $blankSense, null );

		$sense = $entity->addSense(
			$blankSense->getGlosses()
		);

		if ( $sense->getGlosses()->count() === 1 ) {
			$array = $sense->getGlosses()->toTextArray();
			reset( $array );
			$language = key( $array );
		} else {
			$language = null;
		}

		if ( $summary !== null ) {
			// TODO: consistently do not extend ChangeOpBase?
			$this->updateSummary(
				$summary,
				self::SUMMARY_ACTION_ADD,
				$language,
				array_values( $sense->getGlosses()->toTextArray() )
			);
			// TODO: use SenseId not string?
			$summary->addAutoCommentArgs( $sense->getId()->getSerialization() );
		}
	}

}
