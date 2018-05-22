<?php

namespace Wikibase;

use ExtensionRegistry;
use Maintenance;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Repo\WikibaseRepo;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false
	? getenv( 'MW_INSTALL_PATH' )
	: __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for creating blacklisted Lexeme entities.
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class CreateBlacklistedLexemes extends Maintenance {

	const LEMMA = 'lemma';
	const LANGUAGE = 'lang';
	const CATEGORY = 'cat';

	public function __construct() {
		$this->addDescription( 'Created blacklisted lexemes' );

		parent::__construct();
	}

	public function execute() {
		global $wgUser;

		$user = $wgUser;
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->fatalError(
				"You need to have Wikibase enabled in order to use this maintenance script!\n\n"
			);
		}

		if ( !ExtensionRegistry::getInstance()->isLoaded( 'WikibaseLexeme' ) ) {
			$this->fatalError(
				"You need to have WikibaseLexeme enabled in order to use this maintenance script!\n\n"
			);
		}

		$entities = [
			'L1' => [
				self::LEMMA => new Term( 'mis-x-Q36790', 'ama' ),
				self::CATEGORY => new ItemId( 'Q1084' ),
				self::LANGUAGE => new ItemId( 'Q36790' ),
			],

			'L42' => [
				self::LEMMA => new Term( 'en', 'answer' ),
				self::CATEGORY => new ItemId( 'Q1084' ),
				self::LANGUAGE => new ItemId( 'Q1860' ),
			],

			'L99' => [
				self::LEMMA => new Term( 'de', 'Luftballon' ),
				self::CATEGORY => new ItemId( 'Q1084' ),
				self::LANGUAGE => new ItemId( 'Q188' ),
			],

			'L777' => [
				self::LEMMA => new Term( 'pt', 'ganhar' ),
				self::CATEGORY => new ItemId( 'Q24905' ),
				self::LANGUAGE => new ItemId( 'Q5146' ),
			],

			'L666' => [
				self::LEMMA => new Term( 'ru', 'зверь' ),
				self::CATEGORY => new ItemId( 'Q1084' ),
				self::LANGUAGE => new ItemId( 'Q7737' ),
			],

			'L8' => [
				self::LEMMA => new Term( 'en', 'late' ),
				self::CATEGORY => new ItemId( 'Q34698' ),
				self::LANGUAGE => new ItemId( 'Q1860' ),
			],

			'L55' => [
				self::LEMMA => new Term( 'ja', 'こども' ),
				self::CATEGORY => new ItemId( 'Q1084' ),
				self::LANGUAGE => new ItemId( 'Q5287' ),
			],

			'L221' => [
				self::LEMMA => new Term( 'en', 'elementary' ),
				self::CATEGORY => new ItemId( 'Q34698' ),
				self::LANGUAGE => new ItemId( 'Q1860' ),
			],

			'L314' => [
				self::LEMMA => new Term( 'ca', 'pi' ),
				self::CATEGORY => new ItemId( 'Q1084' ),
				self::LANGUAGE => new ItemId( 'Q7026' ),
			],

			'L1887' => [
				self::LEMMA => new Term( 'eo', 'unua' ),
				self::CATEGORY => new ItemId( 'Q34698' ),
				self::LANGUAGE => new ItemId( 'Q143' ),
			],

			'L117' => [
				self::LEMMA => new Term( 'da', 'gentagelse' ),
				self::CATEGORY => new ItemId( 'Q1084' ),
				self::LANGUAGE => new ItemId( 'Q9035' ),
			],

			'L12345' => [
				self::LEMMA => new Term( 'ar', 'صِفْر' ),
				self::CATEGORY => new ItemId( 'Q34698' ),
				self::LANGUAGE => new ItemId( 'Q13955' ),
			],

			'L5' => [
				self::LEMMA => new Term( 'es', 'pino' ),
				self::CATEGORY => new ItemId( 'Q1084' ),
				self::LANGUAGE => new ItemId( 'Q1321' ),
			],

			'L24601' => [
				self::LEMMA => new Term( 'fr', 'condamné' ),
				self::CATEGORY => new ItemId( 'Q1084' ),
				self::LANGUAGE => new ItemId( 'Q150' ),
			],

			'L18' => [
				self::LEMMA => new Term( 'he', 'חי' ),
				self::CATEGORY => new ItemId( 'Q34698' ),
				self::LANGUAGE => new ItemId( 'Q9288' ),
			],

			'L123' => [
				self::LEMMA => new Term( 'mis-x-Q4115189', 'sandbox lexeme' ),
				self::CATEGORY => new ItemId( 'Q4115189' ),
				self::LANGUAGE => new ItemId( 'Q13406268' ),
			],

			'L171081' => [
				self::LEMMA => new Term( 'eu', 'izioki' ),
				self::CATEGORY => new ItemId( 'Q24905' ),
				self::LANGUAGE => new ItemId( 'Q8752' ),
			],

		];

		$this->output( "Starting import...\n\n" );

		foreach ( $entities as $idString => $dataMap ) {
			/** @var Term $lemmaTerm */
			$lemmaTerm = $dataMap[self::LEMMA];
			/** @var ItemId $languageId */
			$languageId = $dataMap[self::LANGUAGE];
			/** @var ItemId $categoryId */
			$categoryId = $dataMap[self::CATEGORY];

			$this->output(
				"Importing Lexeme with lemma " . $lemmaTerm->getText() . " as lexeme $idString... \n"
			);

			$entity = new Lexeme(
				new LexemeId( $idString ),
				new TermList( [ $lemmaTerm ] ),
				$categoryId,
				$languageId
			);

			$store->saveEntity( $entity, 'Import reserved Lexeme', $user, EDIT_NEW );
		}

		$this->output( 'Import completed.' );
	}

}

$maintClass = CreateBlacklistedLexemes::class;
require_once RUN_MAINTENANCE_IF_MAIN;
