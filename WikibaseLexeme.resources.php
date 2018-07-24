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
			"scripts" => "jquery.wikibase.lexemeview.js",
			"dependencies" => [
				"jquery.wikibase.entityview",
				"jquery.wikibase.lexemeformlistview",
				"jquery.wikibase.senselistview",
				"wikibase.lexeme.view.ViewFactoryFactory",
				"lexeme-header"
			]
		],
		"wikibase.lexeme" => $moduleTemplate + [
			"scripts" => "__namespace.js",
			"dependencies" => "wikibase"
		],

		"wikibase.lexeme.entityChangers" => $moduleTemplate + [
			"scripts" => [
				"entityChangers/__namespace.js"
			],
			"dependencies" => [
				"wikibase.lexeme"
			]
		],

		"wikibase.lexeme.entityChangers.FormChanger" => $moduleTemplate + [
			"scripts" => [
				"entityChangers/FormChanger.js"
			],
			"dependencies" => [
				"wikibase.lexeme.entityChangers",
				"wikibase.lexeme.serialization.FormSerializer"
			]
		],

		"wikibase.lexeme.entityChangers.SenseChanger" => $moduleTemplate + [
				"scripts" => [
					"entityChangers/SenseChanger.js"
				],
				"dependencies" => [
					"wikibase.lexeme.entityChangers",
					"wikibase.lexeme.serialization.SenseSerializer"
				]
			],

		"wikibase.lexeme.entityChangers.LexemeRevisionStore" => $moduleTemplate + [
			"scripts" => [
				"entityChangers/LexemeRevisionStore.js"
			],
			"dependencies" => [
				"wikibase.lexeme",
				"wikibase.lexeme.entityChangers"
			]
		],

		"wikibase.lexeme.lexemeview" => $moduleTemplate + [
			"dependencies" => [
				"jquery.wikibase.lexemeview",
				"wikibase.lexeme.getDeserializer"
			]
		],
		"jquery.wikibase.lexemeformlistview" => $moduleTemplate + [
			"scripts" => "jquery.wikibase.lexemeformlistview.js",
			"dependencies" => [
				"jquery.ui.widget",
				"jquery.wikibase.lexemeformview"
			],
			"messages" => [
				"wikibaselexeme-add-form"
			]
		],
		"jquery.wikibase.lexemeformview" => $moduleTemplate + [
			"scripts" => "jquery.wikibase.lexemeformview.js",
			"dependencies" => [
				"jquery.ui.EditableTemplatedWidget",
				"wikibase.templates.lexeme",
				"jquery.wikibase.grammaticalfeatureview",
				"wikibase.lexeme.widgets.RepresentationWidget",
				"wikibase.lexeme.store"
			],
			"messages" => [
				"wikibaselexeme-empty-form-representation",
				"wikibaselexeme-enter-form-representation",
				"wikibaselexeme-statementsection-statements-about-form",
				"wikibaselexeme-form-grammatical-features"
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
		"jquery.wikibase.senselistview" => $moduleTemplate + [
			"scripts" => "jquery.wikibase.senselistview.js",
			"dependencies" => [
				"jquery.ui.widget",
				"jquery.wikibase.senseview"
			],
			"messages" => [
				"wikibaselexeme-add-sense"
			]
		],
		"jquery.wikibase.senseview" => $moduleTemplate + [
			"scripts" => "jquery.wikibase.senseview.js",
			"dependencies" => [
				"jquery.ui.EditableTemplatedWidget",
				"wikibase.templates.lexeme",
				"wikibase.lexeme.widgets.GlossWidget"
			],
			"messages" => [
				"wikibaselexeme-statementsection-statements-about-sense"
			]
		],
		"vue" => $moduleTemplate + [
			"scripts" => "vendor/vue-2.3.3.min.js"
		],
		"vuex" => $moduleTemplate + [
			"scripts" => "vendor/vuex-2.3.0.js",
			"dependencies" => [
				"vue",
				"promise-polyfill"
			]
		],
		"promise-polyfill" => $moduleTemplate + [
			"scripts" => "vendor/es6-promise.auto.js"
		],
		"lexeme-header" => $moduleTemplate + [
			"scripts" => [
				"widgets/__namespace.js",
				"widgets/LexemeHeader.js"
			],
			"dependencies" => [
				"vue",
				"vuex",
				"wikibase.lexeme.widgets.LanguageAndLexicalCategoryWidget",
				"wikibase.lexeme.widgets.LexemeHeader.newLexemeHeader",
				"wikibase.lexeme.widgets.LexemeHeader.newLexemeHeaderStore",
				"wikibase.lexeme.widgets.LemmaWidget.newLemmaWidget",
				"wikibase.api.RepoApi",
				"jquery.wikibase.wbtooltip",
				"mediawiki.api",
				"wikibase.lexeme.datamodel.Lemma"
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
			"dependencies" => [
				"vue"
			]
		],
		"wikibase.lexeme.widgets.RepresentationWidget" => $moduleTemplate + [
			"scripts" => "widgets/RepresentationWidget.js",
			"dependencies" => [
				"vue",
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
			"scripts" => "widgets/GlossWidget.js",
			"dependencies" => [
				"vue",
				"vuex",
				"jquery.util.getDirectionality",
				"wikibase.lexeme.i18n.Messages"
			],
			"messages" => [
				"wikibase-edit",
				"wikibase-save",
				"wikibase-cancel",
				"wikibase-add",
				"wikibase-remove",
				"wikibaselexeme-gloss-field-language-label",
				"wikibaselexeme-gloss-field-gloss-label"
			]
		],
		"wikibase.lexeme.widgets.LexemeHeader.newLexemeHeaderStore" => $moduleTemplate + [
			"scripts" => "widgets/LexemeHeader.newLexemeHeaderStore.js"
		],
		"wikibase.lexeme.widgets.LemmaWidget.newLemmaWidget" => $moduleTemplate + [
			"scripts" => "widgets/LemmaWidget.newLemmaWidget.js",
			"dependencies" => [
				"wikibase.lexeme.datatransfer.LemmaList",
				"wikibase.lexeme.datamodel.Lemma",
				"wikibase.lexeme.widgets.RedundantLanguageIndicator"
			]
		],
		"wikibase.lexeme.widgets.LexemeHeader.newLexemeHeader" => $moduleTemplate + [
			"scripts" => "widgets/LexemeHeader.newLexemeHeader.js"
		],
		"wikibase.lexeme.widgets.LanguageAndLexicalCategoryWidget" => $moduleTemplate + [
			"scripts" => "widgets/LanguageAndLexicalCategoryWidget.js",
			"dependencies" => [
				"wikibase.lexeme.widgets.ItemSelectorWrapper"
			]
		],
		"wikibase.lexeme.widgets.ItemSelectorWrapper" => $moduleTemplate + [
			"scripts" => "widgets/ItemSelectorWrapper.js"
		],
		"wikibase.lexeme.i18n.Messages" => $moduleTemplate + [
			"scripts" => "i18n/Messages.js"
		],
		"wikibase.templates.lexeme" => $moduleTemplate + [
			"class" => "\\Wikibase\\Lexeme\\View\\TemplateModule",
			"dependencies" => [
				"wikibase.templates"
			]
		],
		"wikibase.lexeme.datamodel.Lexeme" => $moduleTemplate + [
			"scripts" => [
				"datamodel/__namespace.js",
				"datamodel/Lexeme.js"
			],
			"dependencies" => [
				"util.inherit",
				"wikibase.datamodel.Entity",
				"wikibase.datamodel.StatementGroupSet",
				"wikibase.datamodel.TermMap",
				"wikibase.lexeme"
			]
		],
		"wikibase.lexeme.datamodel.Lemma" => $moduleTemplate + [
			"scripts" => "datamodel/Lemma.js"
		],
		"wikibase.lexeme.datatransfer.LemmaList" => $moduleTemplate + [
			"scripts" => "datatransfer/LemmaList.js"
		],
		"wikibase.lexeme.store" => $moduleTemplate + [
			"scripts" => "store/index.js",
			"dependencies" => [
				"vuex",
				"wikibase.lexeme.store.actions",
				"wikibase.lexeme.store.mutations"
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
		"wikibase.lexeme.store.mutations" => $moduleTemplate + [
			"scripts" => "store/mutations.js",
			"dependencies" => [
				"wikibase.lexeme.store.mutationTypes"
			]
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

		"wikibase.lexeme.serialization.FormSerializer" => $moduleTemplate + [
			"scripts" => [
				"serialization/__namespace.js",
				"serialization/FormSerializer.js"
			],
			"dependencies" => [
				"util.inherit",
				"wikibase.lexeme",
				"wikibase.lexeme.datamodel.Form",
				"wikibase.serialization.Serializer",
				"wikibase.serialization.TermMapSerializer"
			]
		],

		"wikibase.lexeme.serialization.SenseSerializer" => $moduleTemplate + [
			"scripts" => [
				"serialization/__namespace.js",
				"serialization/SenseSerializer.js"
			],
			"dependencies" => [
				"util.inherit",
				"wikibase.lexeme",
				"wikibase.lexeme.datamodel.Sense",
				"wikibase.serialization.Serializer",
				"wikibase.serialization.TermMapSerializer"
			]
		],

		"wikibase.lexeme.serialization.LexemeDeserializer" => $moduleTemplate + [
			"scripts" => [
				"serialization/__namespace.js",
				"serialization/LexemeDeserializer.js"
			],
			"dependencies" => [
				"util.inherit",
				"wikibase.lexeme",
				"wikibase.lexeme.datamodel.Lexeme",
				"wikibase.lexeme.datamodel.Form",
				"wikibase.lexeme.datamodel.Sense",
				"wikibase.serialization.Deserializer",
				"wikibase.serialization.StatementGroupSetDeserializer",
				"wikibase.serialization.TermMapDeserializer"
			]
		],

		"wikibase.lexeme.widgets.ItemSelectorWidget" => $moduleTemplate + [
			"scripts" => [
				"widgets/__namespace.js",
				"widgets/ItemSelectorWidget.js"
			],
			"dependencies" => [
				"oojs-ui-core",
				"oojs-ui-widgets",
				"wikibase.lexeme.widgets.LabelDescriptionOptionWidget",
				"wikibase.lexeme"
			]
		],
		"wikibase.lexeme.widgets.LabelDescriptionOptionWidget" => $moduleTemplate + [
			"scripts" => [
				"widgets/__namespace.js",
				"widgets/LabelDescriptionOptionWidget.js"
			],
			"dependencies" => [
				"oojs-ui-core",
				"oojs-ui-widgets"
			]
		],
		"wikibase.lexeme.widgets.GrammaticalFeatureListWidget" => $moduleTemplate + [
			"scripts" => [
				"widgets/GrammaticalFeatureListWidget.js"
			],
			"dependencies" => [
				"oojs-ui-core",
				"oojs-ui-widgets"
			]
		],
		"wikibase.lexeme.special" => $moduleTemplate + [
			"scripts" => "special/__namespace.js",
			"dependencies" => "wikibase.lexeme"
		],
		"wikibase.lexeme.special.formHelpers.LexemeLanguageFieldObserver" => $moduleTemplate + [
			"scripts" => [
				"special/formHelpers/__namespace.js",
				"special/formHelpers/LexemeLanguageFieldObserver.js"
			],
			"dependencies" => [
				"wikibase.lexeme.special"
			]
		],
		"wikibase.lexeme.services.ItemLookup" => $moduleTemplate + [
			"scripts" => [
				"services/__namespace.js",
				"services/ItemLookup.js"
			],
			"dependencies" => [
				"wikibase.lexeme"
			]
		],
		"wikibase.lexeme.services.LanguageFromItemExtractor" => $moduleTemplate + [
			"scripts" => [
				"services/__namespace.js",
				"services/LanguageFromItemExtractor.js"
			],
			"dependencies" => [
				"wikibase.lexeme"
			]
		],
		"wikibase.lexeme.special.NewLexeme.styles" => $moduleTemplate + [
			"styles" => [
				"special/new-lexeme.less"
			]
		],
		"wikibase.lexeme.special.NewLexeme" => $moduleTemplate + [
			"scripts" => [
				"special/__namespace.js",
				"special/NewLexeme.js"
			],
			"dependencies" => [
				"mw.config.values.wbRepo",
				"util.inherit",
				"wikibase.api.getLocationAgnosticMwApi",
				"wikibase.api.RepoApi",
				"wikibase.lexeme.config.LexemeLanguageCodePropertyIdConfig",
				"wikibase.lexeme.services.ItemLookup",
				"wikibase.lexeme.services.LanguageFromItemExtractor",
				"wikibase.lexeme.special.formHelpers.LexemeLanguageFieldObserver",
				"wikibase.lexeme.widgets.ItemSelectorWidget"
			]
		],
		"wikibase.lexeme.view.ControllerViewFactory" => $moduleTemplate + [
			"scripts" => [
				"view/__namespace.js",
				"view/ControllerViewFactory.js"
			],
			"dependencies" => [
				"util.inherit",
				"wikibase.lexeme",
				"wikibase.lexeme.datamodel.Form",
				"wikibase.lexeme.datamodel.Sense",
				"wikibase.lexeme.entityChangers.FormChanger",
				"wikibase.lexeme.entityChangers.SenseChanger",
				"wikibase.lexeme.entityChangers.LexemeRevisionStore",
				"wikibase.entityChangers.EntityChangersFactory",
				"wikibase.view.ControllerViewFactory",
				"wikibase.api.getLocationAgnosticMwApi",
				"wikibase.api.RepoApi"
			]
		],
		"wikibase.lexeme.view.ReadModeViewFactory" => $moduleTemplate + [
			"scripts" => [
				"view/__namespace.js",
				"view/ReadModeViewFactory.js"
			],
			"dependencies" => [
				"util.inherit",
				"wikibase.view.ReadModeViewFactory"
			]
		],
		"wikibase.lexeme.view.ViewFactoryFactory" => $moduleTemplate + [
			"scripts" => [
				"view/__namespace.js",
				"view/ViewFactoryFactory.js"
			],
			"dependencies" => [
				"wikibase.lexeme.view.ReadModeViewFactory",
				"wikibase.lexeme.view.ControllerViewFactory"
			]
		],
		"wikibase.lexeme.config.LexemeLanguageCodePropertyIdConfig" => $moduleTemplate + [
			"class" => "Wikibase\\Lexeme\\Config\\LexemeLanguageCodePropertyIdConfig"
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
