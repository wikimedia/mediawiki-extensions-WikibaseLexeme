<?php

namespace Wikibase\Lexeme\Hooks\Formatters;

use Html;
use HtmlArmor;
use Language;
use MessageLocalizer;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormId;
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
	 * @var MessageLocalizer
	 */
	private $messageLocalizer;

	/**
	 * @var Language
	 */
	private $language;

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

	public function getHtml( EntityId $entityId, array $labelData = null ) {
		Assert::parameterType( FormId::class, $entityId, '$entityId' );

		return $this->linkFormatter->getHtml(
			$entityId,
			[
				'language' => $this->language->getCode(),
				'value' => $this->formatRepresentations( $this->getRepresentations( $entityId ) ),
			]
		);
	}

	private function formatRepresentations( array $representations ) {
		return new HtmlArmor( implode(
			$this->messageLocalizer->msg(
				'wikibaselexeme-formidformatter-separator-multiple-representation'
			)->text(),
			array_map(
				function ( $representation, $variant ) {
					return $this->getRepresentationHtml( $representation, $variant );
				},
				$representations,
				array_keys( $representations )
			)
		) );
	}

	private function getRepresentationHtml( $representation, $variant ) {
		$language = Language::factory( $variant );

		return Html::element(
			'span',
			[
				'class' => 'mw-content-' . $language->getDir(),
				'dir' => $language->getDir(),
				'lang' => $language->getHtmlCode(),
			],
			$representation
		);
	}

	private function getRepresentations( FormId $formId ) {
		$form = $this->entityLookup->getEntity( $formId );
		if ( $form instanceof Form ) {
			return $form->getRepresentations()->toTextArray();
		} else {
			return [];
		}
	}

	public function getTitleAttribute(
		Title $title,
		array $labelData = null,
		array $descriptionData = null
	) {
		// TODO: return the right thing here once defined and technically possible
		return $title->getFragment();
	}

}
