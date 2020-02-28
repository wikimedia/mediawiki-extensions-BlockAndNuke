<?php

class BlockAndNukeHooks {
	public static function onPerformRetroactiveAutoblock( $block, $blockIds ) {
		return true;
	}
}
