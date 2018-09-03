<?php

namespace Wikibase\Lexeme;

use Wikibase\WikibaseSettings;

/**
 * TODO: Only exists until repo- and client-specific functionality is not split out to individual
 * extensions.
 * API modules, ResourceLoader modules, and SpecialPages are not used on client, and hence should
 * not be loaded/registered in client-only environment.
 */
class Registrar {

	public static function registerExtension() {
		global $wgLexemeEnableRepo, $wgLexemeEnableSenses;

		if ( !WikibaseSettings::isRepoEnabled() || !$wgLexemeEnableRepo ) {
			return;
		}

		global $wgAPIModules, $wgSpecialPages, $wgResourceModules;

		$wgAPIModules['wbladdform'] = [
			'class' => 'Wikibase\Lexeme\Api\AddForm',
			'factory' => 'Wikibase\Lexeme\Api\AddForm::newFromGlobalState',
		];
		$wgAPIModules['wblremoveform'] = [
			'class' => 'Wikibase\Lexeme\Api\RemoveForm',
			'factory' => 'Wikibase\Lexeme\Api\RemoveForm::newFromGlobalState',
		];
		$wgAPIModules['wbleditformelements'] = [
			'class' => 'Wikibase\Lexeme\Api\EditFormElements',
			'factory' => 'Wikibase\Lexeme\Api\EditFormElements::newFromGlobalState'
		];
		if ( $wgLexemeEnableSenses || defined( 'MW_PHPUNIT_TEST' ) ) {
			$wgAPIModules['wbladdsense'] = [
				'class' => 'Wikibase\Lexeme\Api\AddSense',
				'factory' => 'Wikibase\Lexeme\Api\AddSense::newFromGlobalState',
			];
			$wgAPIModules['wbleditsenseelements'] = [
				'class' => 'Wikibase\Lexeme\Api\EditSenseElements',
				'factory' => 'Wikibase\Lexeme\Api\EditSenseElements::newFromGlobalState'
			];
			$wgAPIModules['wblremovesense'] = [
				'class' => 'Wikibase\Lexeme\Api\RemoveSense',
				'factory' => 'Wikibase\Lexeme\Api\RemoveSense::newFromGlobalState',
			];
		}
		$wgAPIModules['wblmergelexemes'] = [
			'class' => 'Wikibase\Lexeme\Api\MergeLexemes',
			'factory' => 'Wikibase\Lexeme\Api\MergeLexemes::newFromGlobalState',
		];

		$wgSpecialPages['NewLexeme'] = 'Wikibase\Lexeme\Specials\SpecialNewLexeme::newFromGlobalState';
		$wgSpecialPages['MergeLexemes']
			= 'Wikibase\Lexeme\Specials\SpecialMergeLexemes::newFromGlobalState';

		$wgResourceModules = array_merge(
			$wgResourceModules,
			include __DIR__ . '/../WikibaseLexeme.resources.php'
		);
	}

}
