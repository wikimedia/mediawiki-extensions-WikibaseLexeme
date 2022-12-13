<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\MediaWiki\EntityLinkFormatters;

use HtmlArmor;
use Language;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\UnresolvedEntityRedirectException;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\Lexeme;
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

	private EntityLookup $entityLookup;

	private DefaultEntityLinkFormatter $linkFormatter;

	private Language $language;

	private LexemeTermFormatter $lemmaFormatter;

	private EntityTitleTextLookup $entityTitleTextLookup;

	public function __construct(
		EntityTitleTextLookup $entityTitleTextLookup,
		EntityLookup $entityLookup,
		EntityLinkFormatter $linkFormatter,
		LexemeTermFormatter $lemmaFormatter,
		Language $language
	) {
		$this->entityTitleTextLookup = $entityTitleTextLookup;
		$this->entityLookup = $entityLookup;
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
					$this->lemmaFormatter->format( $this->getLemmas( $entityId ) )
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

	/**
	 * @suppress PhanUndeclaredMethod
	 */
	private function getLemmas( LexemeId $entityId ): TermList {
		try {
			$lexeme = $this->entityLookup->getEntity( $entityId );
		} catch ( UnresolvedEntityRedirectException $ex ) { // T228996
			// Regression catch.
			// When there's a double redirect in lexems (eg. L1 -> L2 -> L3)
			// then getting lemmas of L1 will fatal as the second redirect is
			// not handlred by the lookup, and the exception bubbles up here.
			// Fatal was caused by that exception as it wasn't handled. Seen on
			// Special:RecentChanges and Special:WhatLinksHere pages.
			// Handled gracefully with this catch, by returning an empty list,
			// effectively displaying the lexeme by its ID instead.
			return new TermList();
		}

		if ( $lexeme === null ) {
			return new TermList();
		}

		/** @var Lexeme $lexeme */
		return $lexeme->getLemmas();
	}

	public function getFragment( EntityId $entityId, $fragment ): string {
		return $fragment;
	}

}
