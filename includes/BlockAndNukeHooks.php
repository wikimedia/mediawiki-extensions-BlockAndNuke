<?php

class BlockAndNukeHooks {

	/**
	 * @param Block $block
	 * @param array $blockIds
	 * @return bool
	 */
	public static function onPerformRetroactiveAutoblock( $block, $blockIds ) {
		return true;
	}
}
