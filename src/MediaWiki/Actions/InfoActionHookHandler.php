<?php

namespace Wikibase\Lexeme\MediaWiki\Actions;

use IContextSource;
use PageProps;
use Title;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;

/**
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class InfoActionHookHandler {

	/**
	 * @var EntityNamespaceLookup
	 */
	private $namespaceChecker;

	/**
	 * @var EntityIdLookup
	 */
	private $entityIdLookup;

	/**
	 * @var PageProps
	 */
	private $pageProps;

	/**
	 * @var IContextSource
	 */
	private $context;

	public function __construct(
		EntityNamespaceLookup $namespaceChecker,
		EntityIdLookup $entityIdLookup,
		PageProps $pageProps,
		IContextSource $context
	) {
		$this->namespaceChecker = $namespaceChecker;
		$this->entityIdLookup = $entityIdLookup;
		$this->pageProps = $pageProps;
		$this->context = $context;
	}

	/**
	 * @param IContextSource $context
	 * @param array $pageInfo
	 *
	 * @return array[]
	 */
	public function handle( IContextSource $context, array $pageInfo ) {
		// Check if wikibase namespace is enabled
		$title = $context->getTitle();

		if ( !$this->namespaceChecker->isNamespaceWithEntities( $title->getNamespace() )
			|| !$title->exists()
		) {
			return $pageInfo;
		}

		$entityId = $this->entityIdLookup->getEntityIdForTitle( $title );
		if ( !$entityId instanceof LexemeId ) {
			return $pageInfo;
		}

		$pageInfo['header-basic'] = array_merge(
			$pageInfo['header-basic'],
			$this->getSenseAndFormCount( $title )
		);

		return $pageInfo;
	}

	/**
	 * @param Title $title
	 *
	 * @return string[] HTML
	 */
	private function getSenseAndFormCount( Title $title ) {
		$properties = $this->pageProps->getProperties( $title, [ 'wbl-forms', 'wbl-senses' ] );

		if ( $properties ) {
			return $this->formatProperties( $properties );
		}

		return [];
	}

	/**
	 * @param array $properties
	 *
	 * @return string[] HTML
	 */
	private function formatProperties( array $properties ) {
		$output = [];

		foreach ( $properties as $pageId => $pageProperties ) {
			foreach ( $pageProperties as $property => $value ) {
				$output[] = [
					$this->context->msg( 'wikibase-pageinfo-' . $property )->parse(),
					$this->context->getLanguage()->formatNum( (int)$value )
				];
			}
		}

		return $output;
	}

}
