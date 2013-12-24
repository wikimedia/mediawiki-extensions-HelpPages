<?php
/*
 * HelpPages extension
 * Fetches help pages from mediawiki.org if they
 * don't exist locally
 *
 * See also: https://www.mediawiki.org/wiki/Project:PD_help
 *
 * @file
 * @ingroup Extensions
 * @author Kunal Mehta
 * @license Public domain
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}

/**
 * How long to cache the rendered HTML for
 *
 * default is one week
 */
$wgHelpPagesExpiry = 60 * 60 * 24 * 7;

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'HelpPages',
	'author' => 'Kunal Mehta',
	'url' => 'https://www.mediawiki.org/wiki/Extension:HelpPages',
	'descriptionmsg' => 'helppages-desc',
	'version' => '0.2',
);

$dir = dirname(__FILE__);

$wgAutoloadClasses['HelpPages'] = $dir . '/HelpPages.body.php';
$wgAutoloadClasses['HelpPagesHooks'] = $dir . '/HelpPages.hooks.php';

$wgExtensionMessagesFiles['HelpPages'] = "$dir/HelpPages.i18n.php";

$wgHooks['ShowMissingArticle'][] = 'HelpPagesHooks::onShowMissingArticle';
$wgHooks['SkinTemplateNavigation::Universal'][] = 'HelpPagesHooks::onSkinTemplateNavigationUniversal';
$wgHooks['ArticlePurge'][] = 'HelpPagesHooks::onArticlePurge';
$wgHooks['LinkBegin'][] = 'HelpPagesHooks::onLinkBegin';

$wgResourceModules['ext.HelpPages'] = array(
	'styles' => 'ext.HelpPages.css',
	'localBasePath' => $dir,
	'remoteExtPath' => 'HelpPages',
);