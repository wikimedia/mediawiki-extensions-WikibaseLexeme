<?php

/**
 * TODO => should rather be defined in extension.json. To be moved there once client-
 * and repo-specific functionality have been split to separate extensions.
 */

use MediaWiki\MediaWikiServices;
use MediaWiki\ResourceLoader\Context;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LemmaTermValidator;
use Wikibase\Lexeme\Presentation\View\TemplateModule;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Repo\WikibaseRepo;

return call_user_func( static function () {
	$moduleTemplate = [
		'localBasePath' => __DIR__ . '/resources',
		'remoteExtPath' => 'WikibaseLexeme/resources',
	];

	$defaultViewConfigFile = [
		"name" => "view/config.json",
		"callback" => static function () {
			return [
				'tags' => WikibaseRepo::getSettings()->getSetting( 'viewUiTags' ),
			];
		},
	];

	$modules = [
		"wikibase.lexeme" => $moduleTemplate + [
			"scripts" => "__namespace.js",
			"dependencies" => "wikibase",
		],
		"wikibase.lexeme.lexemeview" => $moduleTemplate + [
			"packageFiles" => [
				"hooks/lexeme.viewhook.js",

				"jquery.wikibase.lexemeview.js",
				"datatransfer/LemmaList.js",
				"datamodel/Lemma.js",
				"datamodel/LexemeSubEntityId.js",
				"focusElement.js",
				"jquery.wikibase.lexemeformview.js",
				"jquery.wikibase.lexemeformlistview.js",
				"jquery.wikibase.senselistview.js",
				"jquery.wikibase.senseview.js",
				"jquery.wikibase.grammaticalfeatureview.js",
				"store/index.js",
				"store/actions.js",
				"store/actionTypes.js",
				"store/mutations.js",
				"store/mutationTypes.js",
				"widgets/__namespace.js",
				"widgets/GlossWidget.js",
				"widgets/GrammaticalFeatureListWidget.js",
				"widgets/InvalidLanguageIndicator.js",
				"widgets/ItemSelectorWrapper.js",
				"widgets/LexemeHeader.js",
				"widgets/LanguageSelectorWrapper.js",
				"widgets/LexemeHeader.newLexemeHeader.js",
				"widgets/LanguageAndLexicalCategoryWidget.js",
				"widgets/LexemeHeader.newLexemeHeaderStore.js",
				"widgets/LemmaWidget.newLemmaWidget.js",
				"widgets/RepresentationWidget.js",
				"widgets/RedundantLanguageIndicator.js",
				[
					// used by GlossWidget
					'name' => 'widgets/languages.json',
					'callback' => 'Wikibase\Lexeme\WikibaseLexemeHooks::getLexemeViewLanguages',
				],
				$defaultViewConfigFile,

			],
			"dependencies" => [
				"jquery.util.getDirectionality",
				"jquery.ui.languagesuggester",
				"jquery.ui",
				"jquery.wikibase.wbtooltip",
				"mw.config.values.wbRepo",
				"mediawiki.api",
				"mediawiki.widgets",
				"vue",
				"vuex",
				"wikibase.lexeme",
				"wikibase.api.RepoApi",
				"wikibase.templates.lexeme",
				"wikibase.getLanguageNameByCode",
				"wikibase.lexeme.getDeserializer",
				"wikibase.lexeme.view.ViewFactoryFactory",
				"oojs-ui-core",
				"oojs-ui-widgets",
			],
			"messages" => [
				"wikibaselexeme-add-form",
				"wikibaselexeme-add-sense",
				"wikibaselexeme-empty-form-representation",
				"wikibaselexeme-enter-form-representation",
				"wikibaselexeme-form-grammatical-features",
				"wikibaselexeme-form-field-language-label",
				"wikibaselexeme-form-field-representation-label",
				"wikibaselexeme-form-representation-redundant-language",
				"wikibaselexeme-statementsection-statements-about-form",
				"wikibaselexeme-statementsection-statements-about-sense",
				"wikibase-edit",
				"wikibase-save",
				"wikibase-cancel",
				"wikibase-add",
				"wikibase-remove",
				"wikibaselexeme-error-cannot-remove-last-lemma",
				"wikibaselexeme-field-language-label",
				"wikibaselexeme-field-lexical-category-label",
				"wikibaselexeme-gloss-field-gloss-label",
				"wikibaselexeme-gloss-field-language-label",
				"wikibaselexeme-grammatical-features-input-placeholder",
				"wikibaselexeme-lemma-field-language-label",
				"wikibaselexeme-lemma-field-lemma-label",
				"wikibaselexeme-lemma-redundant-language",
				"wikibaselexeme-sense-gloss-invalid-language",
				"wikibaselexeme-sense-gloss-redundant-language",
				"wikibase-lexeme-language-selector-label",
			],
			"templates" => [
				'lexemeHeader.vue' => 'templates/lexemeHeader.vue.html',
				'glossWidget.vue' => 'templates/glossWidget.vue.html',
				'languageAndLexicalCategoryWidget.vue' => 'templates/languageAndLexicalCategoryWidget.vue.html',
				'lemma.vue' => 'templates/lemma.vue.html',
				'representations.vue' => 'templates/representations.vue.html',
			],
		],
		"wikibase.templates.lexeme" => $moduleTemplate + [
			'scripts' => [
				[
					'name' => 'wikibase.templates.lexeme.js',
					'callback' => [ TemplateModule::class, 'getScript' ],
					'versionCallback' => [ TemplateModule::class, 'getVersion' ],
				],
			],
			"dependencies" => [
				"wikibase.templates",
			],
		],
		"mediawiki.template.vue" => $moduleTemplate + [
			'scripts' => [
				'mediawiki.template.vue.js',
			],
		],
		"wikibase.lexeme.datamodel" => $moduleTemplate + [
			"scripts" => [
				"datamodel/__namespace.js",
				"datamodel/Form.js",
				"datamodel/Sense.js",
			],
			"dependencies" => [
				"util.inherit",
				"wikibase.datamodel",
				"wikibase.lexeme",
			],
		],
		"wikibase.lexeme.getDeserializer" => $moduleTemplate + [
			"packageFiles" => [
				"getDeserializer.js",

				"serialization/LexemeDeserializer.js",
				"datamodel/Lexeme.js",
			],
			"dependencies" => [
				"util.inherit",
				"wikibase.lexeme",
				"wikibase.datamodel",
				"wikibase.lexeme.datamodel",
				"wikibase.serialization",
			],
		],

		"wikibase.lexeme.view.ViewFactoryFactory" => $moduleTemplate + [
			"packageFiles" => [
				"view/ViewFactoryFactory.js",

				"view/ReadModeViewFactory.js",
				"view/ControllerViewFactory.js",
				"entityChangers/FormChanger.js",
				"entityChangers/SenseChanger.js",
				"entityChangers/LexemeRevisionStore.js",
				"serialization/FormSerializer.js",
				"serialization/SenseSerializer.js",
				$defaultViewConfigFile,
			],
			"dependencies" => [
				"util.inherit",
				"wikibase.lexeme",
				"wikibase.lexeme.datamodel",
				"wikibase.serialization",
				"wikibase.lexeme.getDeserializer",
				"wikibase.entityChangers.EntityChangersFactory",
				"wikibase.view.ControllerViewFactory",
				"wikibase.view.ReadModeViewFactory",
				"wikibase.api.RepoApi",
			],
		],
		"wikibase.lexeme.config.LexemeLanguageCodePropertyIdConfig" => $moduleTemplate + [
			"class" => "Wikibase\\Lexeme\\MediaWiki\\Config\\LexemeLanguageCodePropertyIdConfig",
		],
		"wikibase.experts.Lexeme" => $moduleTemplate + [
			"scripts" => [
				"experts/Lexeme.js",
			],
			"dependencies" => [
				"jquery.valueview.Expert",
				"wikibase.experts.Entity",
			],
		],
		"wikibase.experts.Form" => $moduleTemplate + [
			"scripts" => [
				"experts/Form.js",
			],
			"dependencies" => [
				"jquery.valueview.Expert",
				"wikibase.experts.Entity",
			],
		],
		"wikibase.experts.Sense" => $moduleTemplate + [
			"scripts" => [
				"experts/Sense.js",
			],
			"dependencies" => [
				"jquery.valueview.Expert",
				"wikibase.experts.Entity",
			],
		],
		"wikibase.lexeme.styles" => $moduleTemplate + [
			"styles" => [
				"lexeme.less",
			],
			"skinStyles" => [
				"vector" => [
					"lexeme.vector.less",
				],
				'vector-2022' => [
					'lexeme.vector.less',
				],
			],
		],

		"wikibase.lexeme.special.NewLexeme" => $moduleTemplate + [
			"packageFiles" => [
				'special/NewLexeme.js',
				'special/new-lexeme-dist/SpecialNewLexeme.cjs.js',
				[
					"name" => "special/settings.json",
					"callback" => static function () {
						$wbRepoSettings = WikibaseRepo::getSettings();
						return [
							'licenseUrl' => $wbRepoSettings->getSetting( 'dataRightsUrl' ),
							'licenseText' => $wbRepoSettings->getSetting( 'dataRightsText' ),
							'tags' => $wbRepoSettings->getSetting( 'specialPageTags' ),
							'maxLemmaLength' => LemmaTermValidator::LEMMA_MAX_LENGTH,
							'availableSearchProfiles' => array_keys(
								$wbRepoSettings->getSetting( 'searchProfiles' )
							),
						];
					},
				],
				[
					'name' => 'special/languageNames.json',
					'callback' => static function ( Context $context ) {
						$services = MediaWikiServices::getInstance();
						$cache = $services->getLocalServerObjectCache();

						return $cache->getWithSetCallback(
							$cache->makeKey(
								'wikibaseLexeme-languageNames',
								$context->getLanguage()
							),
							60 * 60, // 1 hour
							static function () use ( $context, $services ) {
								$termLanguages = WikibaseLexemeServices::getTermLanguages( $services );
								$languageNameLookup = WikibaseRepo::getLanguageNameLookupFactory( $services )
									->getForLanguageCode( $context->getLanguage() );
								$names = [];
								foreach ( $termLanguages->getLanguages() as $languageCode ) {
									$names[$languageCode] = $languageNameLookup->getName( $languageCode );
								}
								return $names;
							}
						);
					},
				],
			],
			"styles" => [
				'special/new-lexeme-dist/new-lexeme-special-page.css',
			],
			"dependencies" => [
				'vue',
				'vuex',
				'@wikimedia/codex',
				'mediawiki.user',
				'wikibase.lexeme.config.LexemeLanguageCodePropertyIdConfig',
			],
			"messages" => [
				"wikibaselexeme-newlexeme-lemma",
				"wikibaselexeme-newlexeme-lemma-placeholder-with-example",
				"wikibaselexeme-newlexeme-lemma-empty-error",
				"wikibaselexeme-newlexeme-lemma-too-long-error",
				"wikibaselexeme-newlexeme-lemma-language",
				"wikibaselexeme-newlexeme-lemma-language-empty-error",
				"wikibaselexeme-newlexeme-lemma-language-help-link-target",
				"wikibaselexeme-newlexeme-lemma-language-help-link-text",
				"wikibaselexeme-newlexeme-lemma-language-invalid-error",
				"wikibaselexeme-newlexeme-lemma-language-placeholder-with-example",
				"wikibaselexeme-newlexeme-language",
				"wikibaselexeme-newlexeme-language-empty-error",
				"wikibaselexeme-newlexeme-language-invalid-error",
				"wikibaselexeme-newlexeme-language-placeholder-with-example",
				"wikibaselexeme-newlexeme-lexicalcategory",
				"wikibaselexeme-newlexeme-lexicalcategory-empty-error",
				"wikibaselexeme-newlexeme-lexicalcategory-invalid-error",
				"wikibaselexeme-newlexeme-lexicalcategory-placeholder-with-example",
				"wikibaselexeme-newlexeme-search-existing",
				"wikibaselexeme-newlexeme-submit",
				"wikibaselexeme-newlexeme-submitting",
				"wikibase-anonymouseditwarning",
				"wikibase-anonymouseditnotificationtempuser",
				"wikibase-entityselector-notfound",
				"wikibase-shortcopyrightwarning",
				"wikibaselexeme-newlexeme-submit-error",
				"wikibaselexeme-newlexeme-invalid-language-code-warning",
				"wikibase-lexeme-lemma-language-option",
				"copyrightpage",
				"wikibaselexeme-form-field-required",
			],
		],
		"wikibase.lexeme.special.NewLexeme.styles" => $moduleTemplate + [
			"styles" => [
				'special/new-lexeme.less',
			],
		],
		"wikibase.lexeme.special.NewLexeme.legacyBrowserFallback" => $moduleTemplate + [
			"packageFiles" => [ 'special/NewLexemeFallback.js' ],
		],
	];

	return $modules;
} );
