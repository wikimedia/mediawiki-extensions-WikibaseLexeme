<?php

namespace Wikibase\Lexeme\MediaWiki\EntityLinkFormatters;

use HtmlArmor;
use Language;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Presentation\Formatters\LexemeTermFormatter;
use Wikibase\Repo\Hooks\Formatters\DefaultEntityLinkFormatter;
use Wikibase\Repo\Hooks\Formatters\EntityLinkFormatter;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class FormLinkFormatter implements EntityLinkFormatter {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var DefaultEntityLinkFormatter
	 */
	private $linkFormatter;

	/**
	 * @var LexemeTermFormatter
	 */
	private $representationsFormatter;

	/**
	 * @var Language
	 */
	private $language;

	public function __construct(
		EntityLookup $entityLookup,
		DefaultEntityLinkFormatter $linkFormatter,
		LexemeTermFormatter $representationsFormatter,
		Language $language
	) {
		$this->entityLookup = $entityLookup;
		$this->linkFormatter = $linkFormatter;
		$this->language = $language;
		$this->representationsFormatter = $representationsFormatter;
	}

	public function getHtml( EntityId $entityId, array $labelData = null ) {
		Assert::parameterType( FormId::class, $entityId, '$entityId' );
		'@phan-var FormId $entityId';

		return $this->linkFormatter->getHtml(
			$entityId,
			[
				'language' => $this->language->getCode(),
				'value' => new HtmlArmor(
					$this->representationsFormatter->format( $this->getRepresentations( $entityId ) )
				),
			]
		);
	}

	/**
	 * @param FormId $formId
	 * @return TermList
	 * @suppress PhanUndeclaredMethod
	 */
	private function getRepresentations( FormId $formId ): TermList {
		$form = $this->entityLookup->getEntity( $formId );

		if ( $form === null ) {
			return new TermList();

		}
		return $form->getRepresentations();
	}

	/**
	 * @inheritDoc
	 */
	public function getTitleAttribute(
		EntityId $entityId,
		array $labelData = null,
		array $descriptionData = null
	) {
		return $entityId->getSerialization();
	}

	/**
	 * @param FormId $entityId
	 * @param string $fragment
	 * @return string
	 */
	public function getFragment( EntityId $entityId, $fragment ) {
		Assert::parameterType( FormId::class, $entityId, '$entityId' );

		if ( $fragment === $entityId->getSerialization() ) {
			return $entityId->getIdSuffix();
		} else {
			return $fragment;
		}
	}

}
