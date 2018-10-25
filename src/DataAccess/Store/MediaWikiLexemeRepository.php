<?php

namespace Wikibase\Lexeme\DataAccess\Store;

use Wikibase\EntityContent;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Storage\LexemeRepository;
use Wikibase\Lexeme\Domain\Storage\UpdateLexemeException;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\StorageException;

/**
 * @license GPL-2.0-or-later
 */
class MediaWikiLexemeRepository implements LexemeRepository {

	private $user;
	private $entityStore;
	private $userIsBot;

	/**
	 * @param \User $user
	 * @param EntityStore $entityStore Needs to be able to save Lexeme entities
	 * @param bool $userIsBot
	 */
	public function __construct( \User $user, EntityStore $entityStore, $userIsBot ) {
		$this->user = $user;
		$this->entityStore = $entityStore;
		$this->userIsBot = $userIsBot;
	}

	public function updateLexeme( Lexeme $lexeme, /* string */ $editSummary ) {
		try {
			return $this->entityStore->saveEntity(
				$lexeme,
				$editSummary,
				$this->user,
				$this->getSaveFlags()
			);
		} catch ( StorageException $ex ) {
			throw new UpdateLexemeException( $ex );
		}
	}

	private function getSaveFlags() {
		// TODO: the EntityContent::EDIT_IGNORE_CONSTRAINTS flag does not seem to be used by Lexeme
		// (LexemeHandler has no onSaveValidators)
		$flags = EDIT_UPDATE | EntityContent::EDIT_IGNORE_CONSTRAINTS;

		if ( $this->userIsBot && $this->user->isAllowed( 'bot' ) ) {
			$flags |= EDIT_FORCE_BOT;
		}

		return $flags;
	}

}
