<?php

namespace Wikibase\Lexeme\Presentation\Formatters;

use Html;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Lexeme\Domain\Model\LexemeSubEntityId;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * @license GPL-2.0-or-later
 */
class RedirectedLexemeSubEntityIdHtmlFormatter implements EntityIdFormatter {

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	public function __construct( EntityTitleLookup $titleLookup ) {
		$this->titleLookup = $titleLookup;
	}

	/**
	 * @param EntityId $value
	 *
	 * @return string HTML
	 */
	public function formatEntityId( EntityId $value ) {
		if ( !$value instanceof LexemeSubEntityId ) {
			throw new InvalidArgumentException( 'Not a lexeme subentity ID: ' . $value->getSerialization() );
		}

		$title = $this->titleLookup->getTitleForId( $value );

		return Html::element(
			'a',
			[
				'href' => $title->isLocal() ? $title->getLinkURL() : $title->getFullURL(),
			],
			$value->getSerialization()
		);
	}

}
