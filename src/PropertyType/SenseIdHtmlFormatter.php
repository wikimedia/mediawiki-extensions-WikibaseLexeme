<?php

namespace Wikibase\Lexeme\PropertyType;

use Html;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Lexeme\DataModel\SenseId;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * @license GPL-2.0-or-later
 */
class SenseIdHtmlFormatter implements EntityIdFormatter {

	/**
	 * @var SenseIdTextFormatter
	 */
	private $senseIdTextFormatter;

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	public function __construct(
		SenseIdTextFormatter $senseIdTextFormatter,
		EntityTitleLookup $titleLookup
	) {
		$this->senseIdTextFormatter = $senseIdTextFormatter;
		$this->titleLookup = $titleLookup;
	}

	/**
	 * @param SenseId $value
	 *
	 * @return string HTML
	 */
	public function formatEntityId( EntityId $value ) {
		$title = $this->titleLookup->getTitleForId( $value );

		$text = $this->senseIdTextFormatter->formatEntityId( $value );

		return Html::element(
			'a',
			[
				'href' => $title->isLocal() ? $title->getLinkURL() : $title->getFullURL(),
			],
			$text
		);
	}

}
