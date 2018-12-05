<?php

namespace Wikibase\Lexeme\DataAccess\Store;

use Wikibase\Lexeme\Domain\LexemeRedirector;
use Wikibase\Lexeme\Domain\Merge\LexemeRedirectCreationInteractor;
use Wikibase\Lexeme\Domain\Model\LexemeId;

/**
 * @license GPL-2.0-or-later
 */
class MediaWikiLexemeRedirector implements LexemeRedirector {

	private $redirectInteractor;
	private $botEditRequested;

	/**
	 * @param LexemeRedirectCreationInteractor $redirector
	 * @param bool $botEditRequested
	 */
	public function __construct( LexemeRedirectCreationInteractor $redirector, $botEditRequested ) {
		$this->redirectInteractor = $redirector;
		$this->botEditRequested = $botEditRequested;
	}

	public function redirect( LexemeId $sourceId, LexemeId $targetId ) {
		$this->redirectInteractor->createRedirect( $sourceId, $targetId, $this->botEditRequested );
	}

}
