<?php

namespace Wikibase\Lexeme\Domain\Merge\Exceptions;

use Message;
use Throwable;
use Wikibase\Lexeme\Domain\Model\LexemeId;

/**
 * @license GPL-2.0-or-later
 */
class LexemeNotFoundException extends MergingException {

	/**
	 * @var LexemeId
	 */
	private $lexemeId;

	public function __construct(
		LexemeId $lexemeId,
		?string $message = null,
		?Throwable $previous = null
	) {
		parent::__construct(
			$message ?: "Lexeme {$lexemeId->getSerialization()} not found",
			0,
			$previous
		);

		$this->lexemeId = $lexemeId;
	}

	public function getErrorMessage(): Message {
		return new Message(
			'wikibase-lexeme-mergelexemes-error-lexeme-not-found',
			[ $this->lexemeId->getSerialization() ]
		);
	}

	public function getApiErrorCode() {
		return 'no-such-entity';
	}

}
