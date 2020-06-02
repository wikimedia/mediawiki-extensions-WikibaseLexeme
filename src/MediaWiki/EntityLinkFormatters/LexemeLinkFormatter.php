<?php

namespace Wikibase\Lexeme\MediaWiki\EntityLinkFormatters;

use HtmlArmor;
use Language;
use Title;
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
	 * @var EntityTitleTextLookup
	 */
	private $entityTitleTextLookup;

	/**
	 * @param EntityLookup $entityLookup
	 * @param DefaultEntityLinkFormatter $linkFormatter
	 * @param LexemeTermFormatter $lemmaFormatter
	 * @param Language $language
	 */
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
	public function getHtml( EntityId $entityId, array $labelData = null ) {
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
	 * @suppress PhanParamSignatureRealMismatchHasNoParamType
	 * @inheritDoc
	 */
	public function getTitleAttribute(
		$entityIdOrTitle,
		array $labelData = null,
		array $descriptionData = null
	) {
		$paramType = Title::class . '|' . EntityId::class;
		Assert::parameterType( $paramType, $entityIdOrTitle, '$entityIdOrTitle' );

		if ( $entityIdOrTitle instanceof EntityId ) {
			return $this->entityTitleTextLookup->getPrefixedText( $entityIdOrTitle );
		}
		if ( $entityIdOrTitle instanceof Title ) {
			return $entityIdOrTitle->getPrefixedText();
		}
		throw new \LogicException( 'Should have been EntityId or Title' );
	}

	/**
	 * @param LexemeId $entityId
	 * @return TermList
	 * @suppress PhanUndeclaredMethod
	 */
	private function getLemmas( LexemeId $entityId ) : TermList {
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

	public function getFragment( EntityId $entityId, $fragment ) {
		return $fragment;
	}

}
