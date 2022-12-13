<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\MediaWiki\Actions;

use Article;
use HistoryAction;
use IContextSource;
use Wikibase\Lexeme\DataAccess\Store\LemmaLookup;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Presentation\Formatters\LexemeTermFormatter;
use Wikibase\Lib\Store\EntityIdLookup;

/**
 * @license GPL-2.0-or-later
 */
class LexemeHistoryAction extends HistoryAction {

	private EntityIdLookup $entityIdLookup;
	private LemmaLookup $lemmaLookup;
	private LexemeTermFormatter $lexemeTermFormatter;

	public function __construct(
		Article $article,
		IContextSource $context,
		EntityIdLookup $entityIdLookup,
		LemmaLookup $lemmaLookup,
		LexemeTermFormatter $lexemeTermFormatter
	) {
		parent::__construct( $article, $context );

		$this->entityIdLookup = $entityIdLookup;
		$this->lemmaLookup = $lemmaLookup;
		$this->lexemeTermFormatter = $lexemeTermFormatter;
	}

	protected function getPageTitle(): string {
		/** @var LexemeId $lexemeId */
		$lexemeId = $this->entityIdLookup->getEntityIdForTitle( $this->getTitle() );
		'@phan-var LexemeId $lexemeId';

		if ( !$lexemeId ) {
			return parent::getPageTitle();
		}

		$idSerialization = $lexemeId->getSerialization();
		$lemmaTerms = $this->lemmaLookup->getLemmas( $lexemeId );

		$labelText = $this->lexemeTermFormatter->format( $lemmaTerms );

		return $this->msg( 'wikibase-history-title-with-label' )
			->plaintextParams( $idSerialization )->rawParams( $labelText )->parse();
	}
}
