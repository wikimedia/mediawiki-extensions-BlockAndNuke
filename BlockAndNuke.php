<?php
/**
 * BlockAndNuke extension by Eliora Stahl
 */

// Entry point protection
if( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

// Load internationalization files
$wgMessagesDirs['BlockAndNuke'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['BlockAndNuke'] = __DIR__ . '/BlockandNuke.i18n.php';

// Register extension
$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'BlockAndNuke',
	'descriptionmsg' => 'blockandnuke-desc',
	'author' => array(
		'Eliora Stahl',
		'...'
		),
	'url' => 'https://www.mediawiki.org/wiki/Extension:BlockAndNuke',
	'license-name' => 'GPL-3.0+'
);

// Setup permissions - not recognised as admin
$wgGroupPermissions['sysop']['blockandnuke'] = true;
$wgAvailableRights[] = 'blockandnuke';

// Load classes
$wgAutoloadClasses['SpecialBlock_Nuke'] = __DIR__ . '/BlockandNuke.body.php';
$wgAutoloadClasses['BanPests'] = __DIR__ . '/BanPests.php';

// Setup special page and its class name 'Block_Nuke'
$wgSpecialPages['BlockandNuke'] = 'SpecialBlock_Nuke';

// Extension parameters
$wgBaNwhitelist = __DIR__ . "/whitelist.txt";
$wgBaNSpamUser = "Spammer";

// Register hooks
$wgHooks['PerformRetroactiveAutoblock'][] = function ($block, $blockIds) {
	return true;
};
$wgHooks['LanguageGetSpecialPageAliases'][] = function( &$specialPageAliases, $langCode ) {
        $specialPageAliases['blockandnuke'] = array( 'BlockandNuke' );
};
