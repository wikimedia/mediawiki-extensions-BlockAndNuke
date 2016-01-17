<?php

//user will get this message if the haven't installed the extension

if( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

//path of the extension
$dir = __DIR__ . '/';

//load internationalization file.
$wgMessagesDirs['BlockAndNuke'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['BlockAndNuke'] = $dir . 'BlockandNuke.i18n.php';

//setup instructions
$wgExtensionCredits['specialpage'][] = array(
	'path'           => __FILE__,
	'name'           => 'BlockAndNuke',
	'description'    => 'Gives sysops the ability to mass delete users and their pages',
	'descriptionmsg' => 'blockandnuke-desc',
	'author'         => 'Eliora Stahl',
	'url'            => 'http://www.mediawiki.org/wiki/Extension:BlockAndNuke',
);

//Permissions - not recognised as admin
$wgGroupPermissions['sysop']['blockandnuke'] = true;
$wgAvailableRights[] = 'blockandnuke';

$wgAutoloadClasses['SpecialBlock_Nuke'] = $dir . 'BlockandNuke.body.php';
$wgAutoloadClasses['BanPests'] = $dir . 'BanPests.php';

//Tell MediaWiki about the new special page and its class name 'Block_Nuke'
$wgSpecialPages['BlockandNuke'] = 'SpecialBlock_Nuke';

$wgBaNwhitelist = __DIR__ . "/whitelist.txt";

$wgBaNSpamUser = "Spammer";

$wgHooks['PerformRetroactiveAutoblock'][] = function ($block, $blockIds) {
	return true;
};
