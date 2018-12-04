<?php

namespace Wikibase\Lexeme;

use Wikibase\Lexeme\MediaWiki\Api\AddForm;
use Wikibase\Lexeme\MediaWiki\Api\AddSense;
use Wikibase\Lexeme\MediaWiki\Api\EditFormElements;
use Wikibase\Lexeme\MediaWiki\Api\EditSenseElements;
use Wikibase\Lexeme\MediaWiki\Api\MergeLexemes;
use Wikibase\Lexeme\MediaWiki\Api\RemoveForm;
use Wikibase\Lexeme\MediaWiki\Api\RemoveSense;
use Wikibase\WikibaseSettings;

/**
 * TODO: Only exists until repo- and client-specific functionality is not split out to individual
 * extensions.
 * API modules, ResourceLoader modules, and SpecialPages are not used on client, and hence should
 * not be loaded/registered in client-only environment.
 */
class Registrar {

	public static function registerExtension() {
		global $wgLexemeEnableRepo;

		if ( !WikibaseSettings::isRepoEnabled() || !$wgLexemeEnableRepo ) {
			return;
		}

		global $wgAPIModules, $wgSpecialPages, $wgResourceModules;

		$wgAPIModules['wbladdform'] = [
			'class' => AddForm::class,
			'factory' => 'Wikibase\Lexeme\MediaWiki\Api\AddForm::newFromGlobalState',
		];
		$wgAPIModules['wblremoveform'] = [
			'class' => RemoveForm::class,
			'factory' => 'Wikibase\Lexeme\MediaWiki\Api\RemoveForm::newFromGlobalState',
		];
		$wgAPIModules['wbleditformelements'] = [
			'class' => EditFormElements::class,
			'factory' => 'Wikibase\Lexeme\MediaWiki\Api\EditFormElements::newFromGlobalState'
		];
		$wgAPIModules['wbladdsense'] = [
			'class' => AddSense::class,
			'factory' => 'Wikibase\Lexeme\MediaWiki\Api\AddSense::newFromGlobalState',
		];
		$wgAPIModules['wbleditsenseelements'] = [
			'class' => EditSenseElements::class,
			'factory' => 'Wikibase\Lexeme\MediaWiki\Api\EditSenseElements::newFromGlobalState'
		];
		$wgAPIModules['wblremovesense'] = [
			'class' => RemoveSense::class,
			'factory' => 'Wikibase\Lexeme\MediaWiki\Api\RemoveSense::newFromGlobalState',
		];
		$wgAPIModules['wblmergelexemes'] = [
			'class' => MergeLexemes::class,
			'factory' => 'Wikibase\Lexeme\MediaWiki\Api\MergeLexemes::newFromGlobalState',
		];

		$wgSpecialPages['NewLexeme']
			= 'Wikibase\Lexeme\MediaWiki\Specials\SpecialNewLexeme::newFromGlobalState';
		$wgSpecialPages['MergeLexemes']
			= 'Wikibase\Lexeme\MediaWiki\Specials\SpecialMergeLexemes::newFromGlobalState';

		$wgResourceModules = array_merge(
			$wgResourceModules,
			include __DIR__ . '/../WikibaseLexeme.resources.php'
		);
	}

}
