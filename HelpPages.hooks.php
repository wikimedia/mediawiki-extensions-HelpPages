<?php

class HelpPagesHooks {
	/**
	 * @param $article Article
	 * @return bool
	 */
	public static function onShowMissingArticle( $article ) {
		$context = $article->getContext();
		$output = $context->getOutput();
		$title = $article->getTitle();
		if ( $title->getNamespace() == NS_HELP ) {
			list( $text, $oldid ) = HelpPages::getPagePlusFallbacks( 'Help:' . $title->getText() );
			if ( $text ) {
				// Add a notice indicating that it was taken from mediawiki.org
				$output->addHTML( $context->msg( 'helppages-notice', $oldid )->parse() );
				$output->addHTML( $text );
				// Hide the "this page does not exist" notice and edit section links
				$output->addModuleStyles( 'ext.HelpPages' );
			}
		}
		return true;
	}

	/**
	 * Use action=purge to clear cache
	 * @param $article Article
	 * @return bool
	 */
	public static function onArticlePurge( &$article ) {
		global $wgLanguageCode, $wgMemc;
		$title = $article->getContext()->getTitle();
		$key = wfMemcKey( 'helppages', $wgLanguageCode, md5( $title ), 'v2' );
		$wgMemc->delete( $key );
		return true;
	}
}
