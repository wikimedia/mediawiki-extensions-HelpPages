<?php

use MediaWiki\Linker\LinkTarget;
use MediaWiki\MediaWikiServices;

class HelpPages {

	/** @var string */
	private static $apiurl = 'https://www.mediawiki.org/w/api.php';

	/**
	 * Makes an API request to mediawiki.org
	 * @param array $params
	 * @return array
	 */
	protected static function makeAPIRequest( $params ) {
		$params['format'] = 'json';
		$url = wfAppendQuery( self::$apiurl, $params );
		$req = MediaWikiServices::getInstance()->getHttpRequestFactory()->create( $url );
		$req->execute();
		$json = $req->getContent();
		$decoded = FormatJson::decode( $json, true );
		return $decoded;
	}

	/**
	 * Get the cache key for a certain title
	 *
	 * @param string $title
	 * @return string
	 */
	protected static function getCacheKey( $title ) {
		global $wgLanguageCode;
		return ObjectCache::getLocalClusterInstance()->makeKey( 'helppages', $wgLanguageCode, md5( $title ), 'v2' );
	}

	/**
	 * @param Title $title
	 */
	public static function purgeCache( Title $title ) {
		ObjectCache::getLocalClusterInstance()->delete( self::getCacheKey( $title->getPrefixedText() ) );
	}

	/**
	 * Use action=parse to get rendered HTML of a page
	 * @param string $title
	 * @return array
	 */
	protected static function parseWikiText( $title ) {
		$params = [
			'action' => 'parse',
			'page' => $title
		];
		$data = self::makeAPIRequest( $params );
		$parsed = $data['parse']['text']['*'];
		$oldid = $data['parse']['revid'];
		return [ $parsed, $oldid ];
	}

	/**
	 * Get the page text in the content language or a fallback
	 * @param string $title page name
	 * @return string|bool false if couldn't be found
	 */
	public static function getPagePlusFallbacks( $title ) {
		global $wgLanguageCode, $wgHelpPagesExpiry;
		$cache = ObjectCache::getLocalClusterInstance();
		$key = self::getCacheKey( $title );
		$cached = $cache->get( $key );
		// $cached = false;
		if ( $cached !== false ) {
			return $cached;
		}
		if ( method_exists( MediaWikiServices::class, 'getLanguageFallback' ) ) {
			// MW 1.35+
			$fallbacks = MediaWikiServices::getInstance()->getLanguageFallback()->getAll( $wgLanguageCode );
		} else {
			$fallbacks = Language::getFallbacksFor( $wgLanguageCode );
		}
		array_unshift( $fallbacks, $wgLanguageCode );
		$titles = [];
		foreach ( $fallbacks as $langCode ) {
			if ( $langCode === 'en' ) {
				$titles[$title] = $langCode;
			} else {
				$titles[$title . '/' . $langCode] = $langCode;
			}
		}
		$params = [
			'action' => 'query',
			'titles' => implode( '|', array_keys( $titles ) )
		];
		$data = self::makeAPIRequest( $params );
		$pages = [];
		foreach ( $data['query']['pages'] as /* $id => */ $info ) {
			if ( isset( $info['missing'] ) ) {
				continue;
			}
			$lang = $titles[$info['title']];
			$pages[$lang] = $info['title'];
		}
		foreach ( $fallbacks as $langCode ) {
			if ( isset( $pages[$langCode] ) ) {
				$html = self::parseWikiText( $pages[$langCode] );
				$cache->set( $key, $html, $wgHelpPagesExpiry );
				return $html;
			}
		}
		return false;
	}

	/**
	 * @param LinkTarget $target
	 * @return bool
	 */
	public static function helpPageExists( LinkTarget $target ) {
		[ $text, /* $oldid */ ] = self::getPagePlusFallbacks( 'Help:' . $target->getText() );
		return (bool)$text;
	}

}
