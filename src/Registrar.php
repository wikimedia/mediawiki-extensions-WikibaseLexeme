<?php

namespace Wikibase\Lexeme;

use Wikibase\Lexeme\MediaWiki\Api\AddForm;
use Wikibase\Lexeme\MediaWiki\Api\AddSense;
use Wikibase\Lexeme\MediaWiki\Api\EditFormElements;
use Wikibase\Lexeme\MediaWiki\Api\EditSenseElements;
use Wikibase\Lexeme\MediaWiki\Api\MergeLexemes;
use Wikibase\Lexeme\MediaWiki\Api\RemoveForm;
use Wikibase\Lexeme\MediaWiki\Api\RemoveSense;
use Wikibase\Lexeme\MediaWiki\Specials\SpecialMergeLexemes;
use Wikibase\Lexeme\MediaWiki\Specials\SpecialNewLexeme;
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
				'WikibaseRepo.ApiHelperFactory',
				'WikibaseRepo.BaseDataModelSerializerFactory',
				'WikibaseRepo.EditEntityFactory',
				'WikibaseRepo.EntityIdParser',
				'WikibaseRepo.Store',
				'WikibaseRepo.SummaryFormatter',
			],
		];
		$wgAPIModules['wblremoveform'] = [
			'class' => RemoveForm::class,
			'factory' => 'Wikibase\Lexeme\MediaWiki\Api\RemoveForm::factory',
			'services' => [
				'WikibaseRepo.ApiHelperFactory',
				'WikibaseRepo.EditEntityFactory',
				'WikibaseRepo.EntityIdParser',
				'WikibaseRepo.Store',
				'WikibaseRepo.SummaryFormatter',
			],
		];
		$wgAPIModules['wbleditformelements'] = [
			'class' => EditFormElements::class,
			'factory' => 'Wikibase\Lexeme\MediaWiki\Api\EditFormElements::factory',
			'services' => [
				'WikibaseRepo.ApiHelperFactory',
				'WikibaseRepo.BaseDataModelSerializerFactory',
				'WikibaseRepo.EditEntityFactory',
				'WikibaseRepo.EntityIdParser',
				'WikibaseRepo.EntityStore',
				'WikibaseRepo.Store',
				'WikibaseRepo.SummaryFormatter',
			],
		];
		$wgAPIModules['wbladdsense'] = [
			'class' => AddSense::class,
			'factory' => 'Wikibase\Lexeme\MediaWiki\Api\AddSense::factory',
			'services' => [
				'WikibaseRepo.ApiHelperFactory',
				'WikibaseRepo.BaseDataModelSerializerFactory',
				'WikibaseRepo.ChangeOpFactoryProvider',
				'WikibaseRepo.EditEntityFactory',
				'WikibaseRepo.EntityIdParser',
				'WikibaseRepo.ExternalFormatStatementDeserializer',
				'WikibaseRepo.Store',
				'WikibaseRepo.StringNormalizer',
				'WikibaseRepo.SummaryFormatter',
			],
		];
		$wgAPIModules['wbleditsenseelements'] = [
			'class' => EditSenseElements::class,
			'factory' => 'Wikibase\Lexeme\MediaWiki\Api\EditSenseElements::factory',
			'services' => [
				'WikibaseRepo.ApiHelperFactory',
				'WikibaseRepo.BaseDataModelSerializerFactory',
				'WikibaseRepo.ChangeOpFactoryProvider',
				'WikibaseRepo.EditEntityFactory',
				'WikibaseRepo.EntityIdParser',
				'WikibaseRepo.EntityStore',
				'WikibaseRepo.ExternalFormatStatementDeserializer',
				'WikibaseRepo.Store',
				'WikibaseRepo.StringNormalizer',
				'WikibaseRepo.SummaryFormatter',
			],
		];
		$wgAPIModules['wblremovesense'] = [
			'class' => RemoveSense::class,
			'factory' => 'Wikibase\Lexeme\MediaWiki\Api\RemoveSense::factory',
			'services' => [
				'WikibaseRepo.ApiHelperFactory',
				'WikibaseRepo.EditEntityFactory',
				'WikibaseRepo.EntityIdParser',
				'WikibaseRepo.Store',
				'WikibaseRepo.SummaryFormatter',
			],
		];
		$wgAPIModules['wblmergelexemes'] = [
			'class' => MergeLexemes::class,
			'factory' => 'Wikibase\Lexeme\MediaWiki\Api\MergeLexemes::factory',
			'services' => [
				'WikibaseRepo.ApiHelperFactory',
			],
		];

		$wgSpecialPages['NewLexeme'] = [
			'class' => SpecialNewLexeme::class,
			'factory' => 'Wikibase\Lexeme\MediaWiki\Specials\SpecialNewLexeme::factory',
			'services' => [
				'LinkRenderer',
				'StatsdDataFactory',
				'WikibaseRepo.EditEntityFactory',
				'WikibaseRepo.EntityNamespaceLookup',
				'WikibaseRepo.EntityTitleStoreLookup',
				'WikibaseRepo.EntityLookup',
				'WikibaseRepo.EntityIdParser',
				'WikibaseRepo.Settings',
				'WikibaseRepo.SummaryFormatter',
				'WikibaseRepo.EntityIdHtmlLinkFormatterFactory',
				'WikibaseRepo.FallbackLabelDescriptionLookupFactory',
				'WikibaseRepo.ValidatorErrorLocalizer',
				'WikibaseLexemeLemmaTermValidator',
			],
		];

		$wgSpecialPages['MergeLexemes'] = [
			'class' => SpecialMergeLexemes::class,
			'factory' => 'Wikibase\Lexeme\MediaWiki\Specials\SpecialMergeLexemes::factory',
			'services' => [
				'PermissionManager',
				'WikibaseRepo.EntityTitleLookup',
				'WikibaseRepo.ExceptionLocalizer',
				'WikibaseRepo.Settings',
			]
		];

		$wgResourceModules = array_merge(
			$wgResourceModules,
			include __DIR__ . '/../WikibaseLexeme.resources.php'
		);
	}

}
