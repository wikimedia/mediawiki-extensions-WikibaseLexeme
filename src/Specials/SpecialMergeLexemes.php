<?php

namespace Wikibase\Lexeme\Specials;

use Html;
use HTMLForm;
use Wikibase\Repo\Specials\SpecialWikibasePage;

/**
 * Special page for merging one lexeme into another.
 *
 * @license GPL-2.0-or-later
 */
class SpecialMergeLexemes extends SpecialWikibasePage {

	const FROM_ID = 'from-id';
	const TO_ID = 'to-id';

	public function __construct() {
		parent::__construct(
			'MergeLexemes',
			'item-merge',
			false // TODO: flip/remove this once the special page does what it should
		);
	}

	public function execute( $subPage ) {
		parent::execute( $subPage );

		$this->showMergeForm();
	}

	public static function newFromGlobalState() {
		return new self();
	}

	private function showMergeForm() {
		HTMLForm::factory( 'ooui', $this->getFormElements(), $this->getContext() )
			->setId( 'wb-mergelexemes' )
			->setPreText( $this->anonymousEditWarning() )
			->setHeaderText( $this->msg( 'wikibase-lexeme-mergelexemes-intro' )->parse() )
			->setSubmitID( 'wb-mergelexemes-submit' )
			->setSubmitName( 'wikibase-lexeme-mergelexemes-submit' )
			->setSubmitTextMsg( 'wikibase-lexeme-mergelexemes-submit' )
			->setWrapperLegendMsg( 'special-mergelexemes' )
			->setSubmitCallback( function () {
			} )
			->show();
	}

	private function getFormElements() {
		return [
			self::FROM_ID => [
				'name' => self::FROM_ID,
				'default' => $this->getRequest()->getVal( self::FROM_ID ),
				'type' => 'text',
				'id' => 'wb-mergelexemes-from-id',
				'label-message' => 'wikibase-lexeme-mergelexemes-from-id'
			],
			self::TO_ID => [
				'name' => self::TO_ID,
				'default' => $this->getRequest()->getVal( self::TO_ID ),
				'type' => 'text',
				'id' => 'wb-mergelexemes-to-id',
				'label-message' => 'wikibase-lexeme-mergelexemes-to-id'
			]
		];
	}

	private function anonymousEditWarning() {
		if ( $this->getUser()->isAnon() ) {
			return Html::rawElement(
				'p',
				[ 'class' => 'warning' ],
				$this->msg( 'wikibase-anonymouseditwarning' )->text()
			);
		}

		return '';
	}

}
