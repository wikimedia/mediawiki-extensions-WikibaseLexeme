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

	public function __construct( Lexeme $lexeme ) {
		$this->lexeme = $lexeme;
	}

	public function validate( EntityDocument $entity ) {
		Assert::parameterType( BlankSense::class, $entity, '$entity' );

		return Result::newSuccess();
	}

	public function apply( EntityDocument $sense, Summary $summary = null ) {
		Assert::parameterType( BlankSense::class, $sense, '$entity' );
		'@phan-var BlankSense $sense';

		/** @var BlankSense $sense */
		$this->lexeme->addOrUpdateSense( $sense );

		return new DummyChangeOpResult();
	}

	public function getActions() {
		return [ EntityPermissionChecker::ACTION_EDIT ];
	}

}
