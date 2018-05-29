<?php

namespace Wikibase\Lexeme\Hooks\Formatters;

use Language;
use MessageLocalizer;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Repo\Hooks\Formatters\EntityLinkFormatter;
use Wikibase\Repo\Hooks\Formatters\DefaultEntityLinkFormatter;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class LexemeLinkFormatter implements EntityLinkFormatter {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var DefaultEntityLinkFormatter
	 */
	private $linkFormatter;

	/**
	 * @var MessageLocalizer
	 */
	private $messageLocalizer;

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @param EntityLookup $entityLookup
	 * @param DefaultEntityLinkFormatter $linkFormatter
	 * @param MessageLocalizer $messageLocalizer
	 * @param Language $language
	 */
	public function __construct(
		EntityLookup $entityLookup,
		DefaultEntityLinkFormatter $linkFormatter,
		MessageLocalizer $messageLocalizer,
		Language $language
	) {
		$this->entityLookup = $entityLookup;
		$this->linkFormatter = $linkFormatter;
		$this->messageLocalizer = $messageLocalizer;
		$this->language = $language;
	}

	/**
	 * @see EntityLinkFormatter::getHtml()
	 */
	public function getHtml( EntityId $entityId, array $labelData = null ) {
		Assert::parameterType( LexemeId::class, $entityId, '$entityId' );

		/**
		 * @var Lexeme $lexeme
		 */
		$lexeme = $this->entityLookup->getEntity( $entityId );

		return $this->linkFormatter->getHtml(
			$entityId,
			[
				'language' => $this->language->getCode(),
				'value' => $this->formatLemmas( $lexeme->getLemmas()->toTextArray() ),
			]
		);
	}

	/**
	 * @see EntityLinkFormatter::getTitleAttribute()
	 */
	public function getTitleAttribute(
		Title $title,
		array $labelData = null,
		array $descriptionData = null
	) {
		return $title->getPrefixedText();
	}

	private function formatLemmas( array $lemmas ) {
		return implode(
			$this->messageLocalizer->msg(
				'wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma'
			)->text(),
			$lemmas
		);
	}

}
