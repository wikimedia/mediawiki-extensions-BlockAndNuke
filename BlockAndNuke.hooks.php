<?php

class BlockAndNukeHooks {
	public static function onPerformRetroactiveAutoblock( $block, $blockIds ) {
		return true;
	}

	public static function onLanguageGetSpecialPageAliases( &$specialPageAliases, $langCode ) {
		$specialPageAliases['blockandnuke'] = array( 'BlockandNuke' );
	}

	public static function onRegistration() {
		global $wgBaNwhitelist;
		if ( $wgBaNwhitelist === null ) {
			$wgBaNwhitelist = __DIR__ . '/whitelist.txt';
		}
	}
}