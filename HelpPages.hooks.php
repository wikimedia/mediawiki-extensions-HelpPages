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
	 * @param SkinTemplate $sktemplate
	 * @param array $links
	 * @return bool
	 */
	public static function onSkinTemplateNavigationUniversal( &$sktemplate, &$links ) {
		//var_dump($links);
		$context = $sktemplate->getContext();
		$title = $sktemplate->getTitle();
		//var_dump($links);
		if ( $title->getNamespace() == NS_HELP ) {
			list ( $text, $oldid ) = HelpPages::getPagePlusFallbacks( 'Help:' . $title->getText() );
			if ( $text ) {
				$links['namespaces']['help']['class'] = 'selected';
				$links['namespaces']['help_talk']['class'] = '';
				$links['namespaces']['help_talk']['href'] = '//www.mediawiki.org/wiki/Help talk:' . $title->getText();
				$links['views'] = array(); // Kill the 'Create' button @todo make this suck less
				$links['views'][] = array(
					'class' => false,
					'text' => $context->msg( 'helppages-edit-tab' ),
					'href' => wfAppendQuery(
						'//www.mediawiki.org/w/index.php',
						array(
							'action' => 'edit',
							'title' => $title->getPrefixedText()
						)
					)
				);
			}
		}
		return true;
	}

	/**
	 * Use action=purge to clear cache
	 * @param WikiPage $article
	 * @return bool
	 */
	public static function onArticlePurge( &$article ) {
		HelpPages::purgeCache( $article->getTitle() );
		return true;
	}
}
