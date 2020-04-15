<?php

/**
 * TODO => should rather be defined in extension.json. To be moved there once client-
 * and repo-specific functionality have been split to separate extensions.
 */
return call_user_func( function() {
	$moduleTemplate = [
		'localBasePath' => __DIR__ . '/resources',
		'remoteExtPath' => 'WikibaseLexeme/resources',
	];

	return [
		"wikibase.lexeme" => $moduleTemplate + [
			"scripts" => "__namespace.js",
			"dependencies" => "wikibase"
		],
		"wikibase.lexeme.lexemeview" => $moduleTemplate + [
			"packageFiles" => [
				"hooks/lexeme.viewhook.js",

				"jquery.wikibase.lexemeview.js",
				"datatransfer/LemmaList.js",
				"datamodel/Lemma.js",
				"datamodel/LexemeSubEntityId.js",
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
			],
			"dependencies" => [
				"jquery.util.getDirectionality",
				"jquery.ui.languagesuggester",
				"jquery.ui",
				"jquery.wikibase.wbtooltip",
				"mw.config.values.wbRepo",
				"mediawiki.api",
				"mediawiki.widgets",
				"vue2",
				"vuex2",
				"wikibase.lexeme",
				"wikibase.api.RepoApi",
				"wikibase.templates.lexeme",
				"wikibase.getLanguageNameByCode",
				"wikibase.lexeme.getDeserializer",
				"wikibase.WikibaseContentLanguages",
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
				"wikibase-lexeme-language-selector-label"
			]
		],
		"vuex2" => $moduleTemplate + [
			"scripts" => "vendor/vuex-2.3.0.js",
			"dependencies" => [
				"vue2",
				"promise-polyfill"
			]
		],
		"promise-polyfill" => $moduleTemplate + [
			"scripts" => "vendor/es6-promise.auto.js"
		],
		"wikibase.templates.lexeme" => $moduleTemplate + [
			"class" => "\\Wikibase\\Lexeme\\Presentation\\View\\TemplateModule",
			"dependencies" => [
				"wikibase.templates"
			]
		],
		"wikibase.lexeme.datamodel" => $moduleTemplate + [
			"scripts" => [
				"datamodel/__namespace.js",
				"datamodel/Form.js",
				"datamodel/Sense.js"
			],
			"dependencies" => [
				"util.inherit",
				"wikibase.datamodel",
				"wikibase.lexeme"
			]
		],
		"wikibase.lexeme.getDeserializer" => $moduleTemplate + [
			"packageFiles" => [
				"getDeserializer.js",

				"serialization/LexemeDeserializer.js",
				"datamodel/Lexeme.js"
			],
			"dependencies" => [
				"util.inherit",
				"wikibase.lexeme",
				"wikibase.datamodel",
				"wikibase.lexeme.datamodel",
				"wikibase.serialization",
			]
		],

		// This module cannot go into packageFiles because it's connected to the PHP version of the Widget
		"wikibase.lexeme.widgets.ItemSelectorWidget" => $moduleTemplate + [
			"scripts" => [
				"widgets/__namespace.js",
				"widgets/ItemSelectorWidget.js"
			],
			"dependencies" => [
				"oojs-ui-core",
				"oojs-ui-widgets",
				"mediawiki.widgets",
				"wikibase.lexeme"
			]
		],
		"wikibase.lexeme.special.NewLexeme" => $moduleTemplate + [
			"packageFiles" => [
				"special/NewLexeme.js",

				"services/ItemLookup.js",
				"services/LanguageFromItemExtractor.js",
				"special/formHelpers/LexemeLanguageFieldObserver.js",
			],
			"styles" => [
				"special/new-lexeme.less"
			],
			"dependencies" => [
				"mw.config.values.wbRepo",
				"util.inherit",
				"wikibase.api.RepoApi",
				"wikibase.lexeme.config.LexemeLanguageCodePropertyIdConfig",
				"wikibase.lexeme.widgets.ItemSelectorWidget",
			]
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
				"serialization/SenseSerializer.js"
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
				"wikibase.api.RepoApi"
			]
		],
		"wikibase.lexeme.config.LexemeLanguageCodePropertyIdConfig" => $moduleTemplate + [
			"class" => "Wikibase\\Lexeme\\MediaWiki\\Config\\LexemeLanguageCodePropertyIdConfig"
		],
		"wikibase.experts.Lexeme" => $moduleTemplate + [
			"scripts" => [
				"experts/Lexeme.js"
			],
			"dependencies" => [
				"jquery.valueview.Expert",
				"wikibase.experts.Entity"
			]
		],
		"wikibase.experts.Form" => $moduleTemplate + [
			"scripts" => [
				"experts/Form.js"
			],
			"dependencies" => [
				"jquery.valueview.Expert",
				"wikibase.experts.Entity"
			]
		],
		"wikibase.experts.Sense" => $moduleTemplate + [
			"scripts" => [
				"experts/Sense.js"
			],
			"dependencies" => [
				"jquery.valueview.Expert",
				"wikibase.experts.Entity"
			]
		],
		"wikibase.lexeme.styles" => $moduleTemplate + [
			"styles" => [
				"lexeme.less"
			]
		]
	];
} );
