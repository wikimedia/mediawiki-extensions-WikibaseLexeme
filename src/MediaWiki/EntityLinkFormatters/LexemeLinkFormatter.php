<?php

namespace Wikibase\Lexeme\MediaWiki\EntityLinkFormatters;

use HtmlArmor;
use Language;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Formatters\LexemeTermFormatter;
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
	 * @var Language
	 */
	private $language;

	/**
	 * @var LexemeTermFormatter
	 */
	private $lemmaFormatter;

	/**
	 * @param EntityLookup $entityLookup
	 * @param DefaultEntityLinkFormatter $linkFormatter
	 * @param LexemeTermFormatter $lemmaFormatter
	 * @param Language $language
	 */
	public function __construct(
		EntityLookup $entityLookup,
		DefaultEntityLinkFormatter $linkFormatter,
		LexemeTermFormatter $lemmaFormatter,
		Language $language
	) {
		$this->entityLookup = $entityLookup;
		$this->linkFormatter = $linkFormatter;
		$this->lemmaFormatter = $lemmaFormatter;
		$this->language = $language;
	}

	/**
	 * @see EntityLinkFormatter::getHtml()
	 */
	public function getHtml( EntityId $entityId, array $labelData = null ) {
		Assert::parameterType( LexemeId::class, $entityId, '$entityId' );

		return $this->linkFormatter->getHtml(
			$entityId,
			[
				'language' => $this->language->getCode(),
				'value' => new HtmlArmor(
					$this->lemmaFormatter->format( $this->getLemmas( $entityId ) )
				),
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

	private function getLemmas( LexemeId $entityId ) : TermList {
		$lexeme = $this->entityLookup->getEntity( $entityId );

		/** @var Lexeme $lexeme */
		return $lexeme->getLemmas();
	}

}
