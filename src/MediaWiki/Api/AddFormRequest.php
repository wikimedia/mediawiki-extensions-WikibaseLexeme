<?php

namespace Wikibase\Lexeme\MediaWiki\Api;

use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpFormAdd;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Repo\ChangeOp\ChangeOp;

/**
 * @license GPL-2.0-or-later
 */
class AddFormRequest {

	private $lexemeId;
	private $editFormchangeOp;

	/**
	 * @var int|null
	 */
	private $baseRevId;

	/**
	 * @param LexemeId $lexemeId
	 * @param ChangeOp $editFormChangeOp
	 * @param int|null $baseRevId
	 */
	public function __construct(
		LexemeId $lexemeId,
		ChangeOp $editFormChangeOp,
		$baseRevId
	) {
		// TODO: consider the below in appropriate validation
		// Assert::parameterElementType( ItemId::class, $grammaticalFeatures, '$grammaticalFeatures' );
		// Assert::parameter( !$representations->isEmpty(), '$representations', 'should not be empty' );

		$this->lexemeId = $lexemeId;
		$this->editFormchangeOp = $editFormChangeOp;
		$this->baseRevId = $baseRevId;
	}

	public function getChangeOp(): ChangeOpFormAdd {
		return new ChangeOpFormAdd( $this->editFormchangeOp );
	}

	public function getLexemeId(): LexemeId {
		return $this->lexemeId;
	}

	/**
	 * @return int|null
	 */
	public function getBaseRevId() {
		return $this->baseRevId;
	}

}
