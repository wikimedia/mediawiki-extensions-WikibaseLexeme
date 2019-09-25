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
		"jquery.wikibase.lexemeview" => $moduleTemplate + [
			"packageFiles" => [
				"jquery.wikibase.lexemeview.js",

				"jquery.wikibase.lexemeformview.js",
				"jquery.wikibase.lexemeformlistview.js",
				"jquery.wikibase.senselistview.js",
				"jquery.wikibase.senseview.js",
				"datamodel/LexemeSubEntityId.js",
			],
			"dependencies" => [
				"jquery.ui.EditableTemplatedWidget",
				"jquery.ui.widget",
				"jquery.wikibase.entityview",
				"jquery.wikibase.grammaticalfeatureview",
				"lexeme-header",
				"wikibase.lexeme",
				"wikibase.lexeme.store",
				"wikibase.lexeme.view.ViewFactoryFactory",
				"wikibase.lexeme.widgets.GlossWidget",
				"wikibase.lexeme.widgets.RepresentationWidget",
				"wikibase.templates.lexeme"
			],
			"messages" => [
				"wikibaselexeme-empty-form-representation",
				"wikibaselexeme-enter-form-representation",
				"wikibaselexeme-statementsection-statements-about-form",
				"wikibaselexeme-statementsection-statements-about-sense",
				"wikibaselexeme-form-grammatical-features",
				"wikibaselexeme-add-form",
				"wikibaselexeme-add-sense"
			]
		],
		"wikibase.lexeme" => $moduleTemplate + [
			"scripts" => "__namespace.js",
			"dependencies" => "wikibase"
		],

		"wikibase.lexeme.lexemeview.viewhook" => $moduleTemplate + [
			"scripts" => "hooks/lexeme.viewhook.js"
		],
		"wikibase.lexeme.lexemeview" => $moduleTemplate + [
			"dependencies" => [
				"wikibase.lexeme.lexemeview.viewhook",
				"jquery.wikibase.lexemeview",
				"wikibase.lexeme.getDeserializer"
			]
		],
		"jquery.wikibase.grammaticalfeatureview" => $moduleTemplate + [
			"scripts" => "jquery.wikibase.grammaticalfeatureview.js",
			"dependencies" => [
				"jquery.ui.EditableTemplatedWidget",
				"wikibase.templates.lexeme",
				"wikibase.lexeme.widgets.GrammaticalFeatureListWidget"
			],
			"messages" => [
				"wikibaselexeme-grammatical-features-input-placeholder"
			]
		],
		"vuex" => $moduleTemplate + [
			"scripts" => "vendor/vuex-2.3.0.js",
			"dependencies" => [
				"vue2",
				"promise-polyfill"
			]
		],
		"promise-polyfill" => $moduleTemplate + [
			"scripts" => "vendor/es6-promise.auto.js"
		],
		"lexeme-header" => $moduleTemplate + [
			"packageFiles" => [
				"widgets/LexemeHeader.js",

				"widgets/__namespace.js",
				"widgets/LexemeHeader.newLexemeHeader.js",
				"widgets/LanguageAndLexicalCategoryWidget.js",
				"widgets/ItemSelectorWrapper.js",
				"widgets/LexemeHeader.newLexemeHeaderStore.js",
				"widgets/LemmaWidget.newLemmaWidget.js",
				"datamodel/Lemma.js",
				"datatransfer/LemmaList.js"
			],
			"dependencies" => [
				"vue2",
				"vuex",
				"wikibase.lexeme.widgets.RedundantLanguageIndicator",
				"wikibase.api.RepoApi",
				"jquery.wikibase.wbtooltip",
				"mediawiki.api",
			],
			"messages" => [
				"wikibase-edit",
				"wikibase-save",
				"wikibase-cancel",
				"wikibase-add",
				"wikibase-remove",
				"wikibaselexeme-lemma-field-lemma-label",
				"wikibaselexeme-lemma-field-language-label",
				"wikibaselexeme-lemma-redundant-language",
				"wikibaselexeme-field-language-label",
				"wikibaselexeme-field-lexical-category-label",
				"wikibaselexeme-error-cannot-remove-last-lemma"
			]
		],
		"wikibase.lexeme.widgets.RedundantLanguageIndicator" => $moduleTemplate + [
			"scripts" => "widgets/RedundantLanguageIndicator.js",
		],
		"wikibase.lexeme.widgets.InvalidLanguageIndicator" => $moduleTemplate + [
			"scripts" => "widgets/InvalidLanguageIndicator.js",
			"dependencies" => [
				"mw.config.values.wbRepo",
			]
		],
		"wikibase.lexeme.widgets.RepresentationWidget" => $moduleTemplate + [
			"scripts" => "widgets/RepresentationWidget.js",
			"dependencies" => [
				"vue2",
				"vuex",
				"wikibase.lexeme.widgets.RedundantLanguageIndicator",
				"wikibase.lexeme.store.actionTypes"
			],
			"messages" => [
				"wikibase-add",
				"wikibase-remove",
				"wikibaselexeme-form-field-representation-label",
				"wikibaselexeme-form-field-language-label",
				"wikibaselexeme-form-representation-redundant-language"
			]
		],
		"wikibase.lexeme.widgets.GlossWidget" => $moduleTemplate + [
			"packageFiles" => [
				"widgets/GlossWidget.js",
				"widgets/LanguageSelectorWrapper.js",
			],
			"dependencies" => [
				"vue2",
				"vuex",
				"jquery.util.getDirectionality",
				"wikibase.lexeme.widgets.RedundantLanguageIndicator",
				"wikibase.lexeme.widgets.InvalidLanguageIndicator",
				"wikibase.getLanguageNameByCode",
				"wikibase.WikibaseContentLanguages",
				"jquery.ui.languagesuggester",
				"wikibase.getLanguageNameByCode"
			],
			"messages" => [
				"wikibase-edit",
				"wikibase-save",
				"wikibase-cancel",
				"wikibase-add",
				"wikibase-remove",
				"wikibaselexeme-gloss-field-language-label",
				"wikibaselexeme-gloss-field-gloss-label",
				"wikibaselexeme-sense-gloss-redundant-language",
				"wikibaselexeme-sense-gloss-invalid-language",
				"wikibase-lexeme-language-selector-label"
			]
		],
		"wikibase.templates.lexeme" => $moduleTemplate + [
			"class" => "\\Wikibase\\Lexeme\\Presentation\\View\\TemplateModule",
			"dependencies" => [
				"wikibase.templates"
			]
		],
		"wikibase.lexeme.store" => $moduleTemplate + [
			"packageFiles" => [
				"store/index.js",
				"store/mutations.js",
			],
			"dependencies" => [
				"vuex",
				"wikibase.lexeme.store.actions",
				"wikibase.lexeme.store.mutationTypes"
			]
		],
		"wikibase.lexeme.store.actions" => $moduleTemplate + [
			"scripts" => "store/actions.js",
			"dependencies" => [
				"wikibase.lexeme.store.actionTypes",
				"wikibase.lexeme.store.mutationTypes"
			]
		],
		"wikibase.lexeme.store.actionTypes" => $moduleTemplate + [
			"scripts" => "store/actionTypes.js"
		],
		"wikibase.lexeme.store.mutationTypes" => $moduleTemplate + [
			"scripts" => "store/mutationTypes.js"
		],
		"wikibase.lexeme.datamodel.Form" => $moduleTemplate + [
			"scripts" => [
				"datamodel/__namespace.js",
				"datamodel/Form.js"
			],
			"dependencies" => [
				"util.inherit",
				"wikibase.lexeme"
			]
		],
		"wikibase.lexeme.datamodel.Sense" => $moduleTemplate + [
			"scripts" => [
				"datamodel/__namespace.js",
				"datamodel/Sense.js"
			],
			"dependencies" => [
				"util.inherit",
				"wikibase.lexeme"
			]
		],
		"wikibase.lexeme.getDeserializer" => $moduleTemplate + [
			"scripts" => "getDeserializer.js",
			"dependencies" => [
				"wikibase.lexeme.serialization.LexemeDeserializer"
			]
		],

		"wikibase.lexeme.serialization.LexemeDeserializer" => $moduleTemplate + [
			"packageFiles" => [
				"serialization/LexemeDeserializer.js",

				"serialization/__namespace.js",
				"datamodel/Lexeme.js"
			],
			"dependencies" => [
				"util.inherit",
				"wikibase.lexeme",
				"wikibase.datamodel.Entity",
				"wikibase.datamodel.StatementGroupSet",
				"wikibase.datamodel.TermMap",
				"wikibase.lexeme.datamodel.Form",
				"wikibase.lexeme.datamodel.Sense",
				"wikibase.serialization.Deserializer",
				"wikibase.serialization.StatementGroupSetDeserializer",
				"wikibase.serialization.TermMapDeserializer"
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
		"wikibase.lexeme.widgets.GrammaticalFeatureListWidget" => $moduleTemplate + [
			"scripts" => [
				"widgets/GrammaticalFeatureListWidget.js"
			],
			"dependencies" => [
				"oojs-ui-core",
				"oojs-ui-widgets",
				"mediawiki.widgets"
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
				"wikibase.api.getLocationAgnosticMwApi",
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
				"wikibase.lexeme.datamodel.Form",
				"wikibase.lexeme.datamodel.Sense",
				"wikibase.serialization.Serializer",
				"wikibase.serialization.TermMapSerializer",
				"wikibase.entityChangers.EntityChangersFactory",
				"wikibase.view.ControllerViewFactory",
				"wikibase.api.getLocationAgnosticMwApi",
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
