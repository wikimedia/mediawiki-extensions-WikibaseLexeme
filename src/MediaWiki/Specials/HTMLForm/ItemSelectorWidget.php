<?php

namespace Wikibase\Lexeme\MediaWiki\Specials\HTMLForm;

use OOUI\Tag;
use OOUI\TextInputWidget;

/**
 * Needed to infuse the ItemSelectorWidget into an existing form field in the frontend
 *
 * TODO: make it configurable from PHP in order to inject API URL, timeout etc.
 *
 * @license GPL-2.0-or-later
 */
class ItemSelectorWidget extends TextInputWidget {

	/**
	 * @var Tag
	 */
	private $valueField;

	public function __construct( array $config ) {
		$textInputConfig = $config;
		if ( isset( $config['labelFieldName'] ) ) {
			$textInputConfig['name'] = $config['labelFieldName'];
		}
		if ( isset( $config['labelFieldValue'] ) ) {
			$textInputConfig['value'] = $config['labelFieldValue'];
		}
		parent::__construct( $textInputConfig );

		$this->valueField = new Tag( 'input' );
		$this->valueField->setAttributes( [
			'type' => 'hidden',
			'class' => 'oo-ui-wikibase-item-selector-value'
		] );
		if ( isset( $config['name'] ) ) {
			$this->valueField->setAttributes( [ 'name' => $config['name'] ] );
		} elseif ( isset( $config['fieldname'] ) ) {
			$this->valueField->setAttributes( [ 'name' => $config['fieldname'] ] );
		}

		$this->valueField->setValue( isset( $config['value'] ) ? $config['value'] : $this->getValue() );

		$this->appendContent( $this->valueField );
	}

	protected function getJavaScriptClassName() {
		return 'wikibase.lexeme.widgets.ItemSelectorWidget';
	}

	public function setValue( $value ) {
		parent::setValue( $value );

		// setValue is called in TextInputWidget's constructor, valueField is not defined yet then
		if ( isset( $this->valueField ) ) {
			$this->valueField->setValue( $value );
		}

		return $this;
	}

}
