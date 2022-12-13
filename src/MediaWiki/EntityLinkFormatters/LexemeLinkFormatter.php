<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\MediaWiki\EntityLinkFormatters;

use HtmlArmor;
use Language;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lexeme\DataAccess\Store\LemmaLookup;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Presentation\Formatters\LexemeTermFormatter;
use Wikibase\Lib\Store\EntityTitleTextLookup;
use Wikibase\Repo\Hooks\Formatters\DefaultEntityLinkFormatter;
use Wikibase\Repo\Hooks\Formatters\EntityLinkFormatter;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class LexemeLinkFormatter implements EntityLinkFormatter {

	private LemmaLookup $lemmaLookup;

	private DefaultEntityLinkFormatter $linkFormatter;

	private Language $language;

	private LexemeTermFormatter $lemmaFormatter;

	private EntityTitleTextLookup $entityTitleTextLookup;

	public function __construct(
		EntityTitleTextLookup $entityTitleTextLookup,
		LemmaLookup $lemmaLookup,
		EntityLinkFormatter $linkFormatter,
		LexemeTermFormatter $lemmaFormatter,
		Language $language
	) {
		$this->entityTitleTextLookup = $entityTitleTextLookup;
		$this->lemmaLookup = $lemmaLookup;
		$this->linkFormatter = $linkFormatter;
		$this->lemmaFormatter = $lemmaFormatter;
		$this->language = $language;
	}

	/**
	 * @inheritDoc
	 */
	public function getHtml( EntityId $entityId, array $labelData = null ): string {
		Assert::parameterType( LexemeId::class, $entityId, '$entityId' );
		'@phan-var LexemeId $entityId';

		return $this->linkFormatter->getHtml(
			$entityId,
			[
				'language' => $this->language->getCode(),
				'value' => new HtmlArmor(
					$this->lemmaFormatter->format( $this->lemmaLookup->getLemmas( $entityId ) )
				),
			]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getTitleAttribute(
		EntityId $entityId,
		array $labelData = null,
		array $descriptionData = null
	): string {
		// TODO Can't this use $entityId->getSerialization() directly?
		//      It may have only used the Title text for historical reasons.
		return $this->entityTitleTextLookup->getPrefixedText( $entityId )
			?? $entityId->getSerialization();
	}

	public function getFragment( EntityId $entityId, $fragment ): string {
		return $fragment;
	}

}
