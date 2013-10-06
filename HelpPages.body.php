<?php

class HelpPages {

	static $apiurl = 'https://www.mediawiki.org/w/api.php';

	/**
	 * Makes an API request to mediawiki.org
	 * @param $params array
	 * @return array
	 */
	public static function makeAPIRequest( $params ) {
		$params['format'] = 'json';
		$url = wfAppendQuery( self::$apiurl, $params );
		$req = MWHttpRequest::factory( $url );
		$req->execute();
		$json = $req->getContent();
		$decoded = FormatJson::decode( $json, true );
		return $decoded;
	}

	/**
	 * Use action=parse to get rendered HTML of a page
	 * @param $title string
	 * @return array
	 */
	public static function parseWikiText( $title ) {
		$params = array(
			'action' => 'parse',
			'page' => $title
		);
		$data = self::makeAPIRequest( $params );
		$parsed = $data['parse']['text']['*'];
		$oldid = $data['parse']['revid'];
		return array( $parsed, $oldid );
	}

	/**
	 * Get the page text in the content language or a fallback
	 * @param $title string page name
	 * @return string|bool false if couldn't be found
	 */
	public static function getPagePlusFallbacks( $title ) {
		global $wgLanguageCode, $wgMemc, $wgHelpPagesExpiry;
		$key = wfMemcKey( 'helppages', $wgLanguageCode, md5( $title ), 'v2' );
		$cached = $wgMemc->get( $key );
		//$cached = false;
		if ( $cached !== false ) {
			return $cached;
		}
		$fallbacks = Language::getFallbacksFor( $wgLanguageCode );
		array_unshift( $fallbacks, $wgLanguageCode );
		$titles = array();
		foreach ( $fallbacks as $langCode ) {
			if ( $langCode === 'en' ) {
				$titles[$title] = $langCode;
			} else {
				$titles[$title . '/' . $langCode] = $langCode;
			}
		}
		$params = array(
			'action' => 'query',
			'titles' => implode( '|', array_keys( $titles ) )
		);
		$data = self::makeAPIRequest( $params );
		$pages = array();
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
				$wgMemc->set( $key, $html, $wgHelpPagesExpiry );
				return $html;
			}
		}
		return false;
	}

}