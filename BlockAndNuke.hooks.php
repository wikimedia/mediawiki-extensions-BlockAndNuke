<?php

class BlockAndNukeHooks {
	public static function onPerformRetroactiveAutoblock( $block, $blockIds ) {
		return true;
	}

	public static function onLanguageGetSpecialPageAliases( &$specialPageAliases, $langCode ) {
		$specialPageAliases['blockandnuke'] = array( 'BlockandNuke' );
	}
}