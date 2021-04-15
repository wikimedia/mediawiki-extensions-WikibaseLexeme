<?php

namespace Wikibase\Lexeme\DataAccess\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\Domain\DummyObjects\BlankSense;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\DummyChangeOpResult;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class AddSenseToLexemeChangeOp implements ChangeOp {

	/**
	 * @var Lexeme
	 */
	private $lexeme;

	/**
	 * @var ChangeOp
	 */
	private $changeOpSenseEdit;

	public function __construct( Lexeme $lexeme, ChangeOp $changeOpSenseEdit ) {
		$this->lexeme = $lexeme;
		$this->changeOpSenseEdit = $changeOpSenseEdit;
	}

	public function validate( EntityDocument $entity ) {
		Assert::parameterType( BlankSense::class, $entity, '$entity' );

		$this->changeOpSenseEdit->validate( $entity );

		return Result::newSuccess();
	}

	public function apply( EntityDocument $sense, Summary $summary = null ) {
		Assert::parameterType( BlankSense::class, $sense, '$entity' );
		'@phan-var BlankSense $sense';

		/** @var BlankSense $sense */
		$this->lexeme->addOrUpdateSense( $sense );
		$this->changeOpSenseEdit->apply( $sense );

		return new DummyChangeOpResult();
	}

	public function getActions() {
		return [ EntityPermissionChecker::ACTION_EDIT ];
	}

}
