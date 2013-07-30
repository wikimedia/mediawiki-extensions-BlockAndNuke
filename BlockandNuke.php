<?php
//user will get this message if the haven't installed the extension
if( !defined( 'MEDIAWIKI' ) )
	die( 'Not an entry point.' );
//path of the extension
$dir = dirname(__FILE__) . '/';

//load internationalization file.
$wgExtensionMessagesFiles['BlockandNuke'] = $dir . 'BlockandNuke.i18n.php';

//setup instructions
$wgExtensionCredits['specialpage'][] = array(
	'path'           => __FILE__,
	'name'           => 'BlockandNuke',
	'description'    => 'Gives sysops the ability to mass delete users and their pages',
	'descriptionmsg' => 'block-desc',
	'author'         => 'Eliora Stahl',
	'url'            => 'http://www.mediawiki.org/wiki/Extension:BlockandNuke',
);

//Permissions - not recognised as admin
$wgGroupPermissions['sysop']['blockandnuke'] = true;
$wgAvailableRights[] = 'blockandnuke';

// Location of the Special:Extension class 'Block_Nuke'. Load this php code.
$wgAutoloadClasses['SpecialBlock_Nuke'] = $dir . 'BlockandNuke.body.php';

//Tell MediaWiki about the new special page and its class name 'Block_Nuke'
$wgSpecialPages['BlockandNuke'] = 'SpecialBlock_Nuke';
//Under heading 'pagetools' on Special Pages
$wgSpecialPageGroups['BlockandNuke'] = 'pagetools';

$wgBaNwhitelist = __DIR__ . "/whitelist.txt";
