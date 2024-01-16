<?php

namespace Wikibase\Lexeme;

use MediaWiki\MediaWikiServices;
use Psr\Container\ContainerInterface;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LemmaTermValidator;
use Wikibase\Lexeme\DataAccess\Store\LemmaLookup;
use Wikibase\Lexeme\Interactors\MergeLexemes\MergeLexemesInteractor;
use Wikibase\Lexeme\MediaWiki\Content\LexemeLanguageNameLookupFactory;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\EditFormChangeOpDeserializer;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\Store\ItemOrderProvider;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseLexemeServices {

	public static function getTermLanguages(
		ContainerInterface $services = null
	): ContentLanguages {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseLexemeTermLanguages' );
	}

	public static function getLanguageNameLookupFactory(
		ContainerInterface $services = null
	): LexemeLanguageNameLookupFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseLexemeLanguageNameLookupFactory' );
	}

	public static function getMobileView(
		ContainerInterface $services = null
	): bool {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseLexemeMobileView' );
	}

	public static function getLemmaLookup(
		ContainerInterface $services = null
	): LemmaLookup {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseLexemeLemmaLookup' );
	}

	public static function getLemmaTermValidator(
		ContainerInterface $services = null
	): LemmaTermValidator {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseLexemeLemmaTermValidator' );
	}

	public static function getEditFormChangeOpDeserializer(
		ContainerInterface $services = null
	): EditFormChangeOpDeserializer {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseLexemeEditFormChangeOpDeserializer' );
	}

	public static function getGrammaticalFeaturesOrderProvider(
		ContainerInterface $services = null
	): ItemOrderProvider {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseLexemeGrammaticalFeaturesOrderProvider' );
	}

	public static function getMergeLexemesInteractor(
		ContainerInterface $services = null
	): MergeLexemesInteractor {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseLexemeMergeLexemesInteractor' );
	}

}
