<?php
/**
 * Internationalisation file for the BlockandNuke extension
 * @addtogroup Extensions
 * @author Eliora Stahl
 */

$aliases = array();

/** English
 * @author Brion Vibber
 */
$aliases['en'] = array(
	'BlockandNuke' => array( 'BlockandNuke' ),
);

$messages = array();

/** English
 * @author Eliora Stahl
 */
$messages['en'] = array(
	'action-blockandnuke'  => "Block and Nuke",
	'blockandnuke'         => 'Block and Nuke',
	'block'                => 'Mass delete',
	'blockandnuke-confirm'        => 'Click the BlockandNuke button to block the selected users and delete all their contributions.<br/>(Do not uncheck any of the boxes below)',
	'blockandnuke-desc'           => 'Gives administrators the ability to [[Special:BlockandNuke]] pages',
	'blockandnuke-nopages'        => "No new pages by [[Special:Contributions/$1|$1]] in recent changes.",
	'blockandnuke-list'           => "The following pages were recently created by [[Special:Contributions/$1|$1]]; put in a comment and hit the button to delete them.",
	'blockandnuke-defaultreason'  => "Mass removal of pages added by $1",
	'blockandnuke-delete-file'    => "BlockandNuke delete spam",
	'blockandnuke-delete-article' => "BlockandNuke delete spam articles.",
	'blockandnuke-tools'          => "List of Users who have recently contributed and are not found on the Whitelist: <br>To add a user to the whitelist edit extensions/BlockandNuke/whitelist.txt",
	'blockandnuke-submit-user'    => 'Block Users',
	'blockandnuke-submit-delete'  => 'BlockandNuke',
	'blockandnuke-block'          => 'Selected users have been blocked and their contributions deleted.',
	'blockandnuke-message' 	   => 'Users blocked by BlockandNuke.',
	'blockandnuke-banhammer'      => "To block the selected users and delete all their contributions click the button below.",
	'blockandnuke-nothing-to-do'  => 'Nothing to do!',
	'blockandnuke-banning'        => 'Banning...',
);
