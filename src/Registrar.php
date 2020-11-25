<?php

namespace Wikibase\Lexeme;

use Wikibase\Lexeme\MediaWiki\Api\AddForm;
use Wikibase\Lexeme\MediaWiki\Api\AddSense;
use Wikibase\Lexeme\MediaWiki\Api\EditFormElements;
use Wikibase\Lexeme\MediaWiki\Api\EditSenseElements;
use Wikibase\Lexeme\MediaWiki\Api\MergeLexemes;
use Wikibase\Lexeme\MediaWiki\Api\RemoveForm;
use Wikibase\Lexeme\MediaWiki\Api\RemoveSense;
use Wikibase\Lib\WikibaseSettings;

/**
 * @license GPL-2.0-or-later
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
			'factory' => 'Wikibase\Lexeme\MediaWiki\Api\AddForm::factory',
			'services' => [
				'WikibaseRepo.EntityIdParser',
			],
		];
		$wgAPIModules['wblremoveform'] = [
			'class' => RemoveForm::class,
			'factory' => 'Wikibase\Lexeme\MediaWiki\Api\RemoveForm::factory',
			'services' => [
				'WikibaseRepo.EntityIdParser',
			],
		];
		$wgAPIModules['wbleditformelements'] = [
			'class' => EditFormElements::class,
			'factory' => 'Wikibase\Lexeme\MediaWiki\Api\EditFormElements::factory',
			'services' => [
				'WikibaseRepo.EntityIdParser',
			],
		];
		$wgAPIModules['wbladdsense'] = [
			'class' => AddSense::class,
			'factory' => 'Wikibase\Lexeme\MediaWiki\Api\AddSense::factory',
			'services' => [
				'WikibaseRepo.EntityIdParser',
			],
		];
		$wgAPIModules['wbleditsenseelements'] = [
			'class' => EditSenseElements::class,
			'factory' => 'Wikibase\Lexeme\MediaWiki\Api\EditSenseElements::factory',
			'services' => [
				'WikibaseRepo.EntityIdParser',
			],
		];
		$wgAPIModules['wblremovesense'] = [
			'class' => RemoveSense::class,
			'factory' => 'Wikibase\Lexeme\MediaWiki\Api\RemoveSense::factory',
			'services' => [
				'WikibaseRepo.EntityIdParser',
			],
		];
		$wgAPIModules['wblmergelexemes'] = [
			'class' => MergeLexemes::class,
			'factory' => 'Wikibase\Lexeme\MediaWiki\Api\MergeLexemes::factory',
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
