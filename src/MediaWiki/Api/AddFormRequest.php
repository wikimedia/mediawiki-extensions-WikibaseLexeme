<?php

namespace Wikibase\Lexeme\MediaWiki\Api;

use Wikibase\Lexeme\ChangeOp\ChangeOpFormAdd;
use Wikibase\Lexeme\Domain\DataModel\LexemeId;
use Wikibase\Repo\ChangeOp\ChangeOp;

/**
 * @license GPL-2.0-or-later
 */
class AddFormRequest {

	private $lexemeId;
	private $editFormchangeOp;

	public function __construct(
		LexemeId $lexemeId,
		ChangeOp $editFormchangeOp
	) {
		// TODO: consider the below in appropriate validation
		// Assert::parameterElementType( ItemId::class, $grammaticalFeatures, '$grammaticalFeatures' );
		// Assert::parameter( !$representations->isEmpty(), '$representations', 'should not be empty' );

		$this->lexemeId = $lexemeId;
		$this->editFormchangeOp = $editFormchangeOp;
	}

	public function getChangeOp(): ChangeOpFormAdd {
		return new ChangeOpFormAdd( $this->editFormchangeOp );
	}

	public function getLexemeId(): LexemeId {
		return $this->lexemeId;
	}

}
