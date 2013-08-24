<?php

class BanPests {

	static function getWhitelist() {
		global $wgBaNwhitelist;

		$fh = fopen($wgBaNwhitelist, 'r');
		$file = fread($fh,200);
		fclose($fh);
		return (preg_split('/\r\n|\r|\n/', $file));
	}

	static function getBannableUsers() {
		$dbr = wfGetDB( DB_SLAVE );
		$cond = array( 'rc_new' => 1 ); /* Anyone creating new pages */
		$cond[] = $dbr->makeList(		/* Anyone uploading stuff */
			array(
				'rc_log_type' => "upload",
				'rc_log_action' => "upload"
			), LIST_AND );
		$cond[] = $dbr->makeList( /* New Users older than a day who haven't done anything yet */
			array(
				'rc_log_action' => 'create',
				'rc_log_type' => 'newusers',
			), LIST_AND );
		$result = $dbr->select( 'recentchanges',
			array( 'DISTINCT rc_user_text' ),
			$dbr->makeList( $cond, LIST_OR ),
			__METHOD__,
			array( 'ORDER BY' => 'rc_user_text ASC' ) );
		$names=array();
		while( $row = $dbr->fetchObject( $result ) ) {
			$names[] = $row->rc_user_text;
		}
		$whitelist = array_flip( self::getWhitelist() );
		return array_filter( $names,
			function($u) use ($whitelist) { if( isset( $whitelist[ $u ] ) ) return false; return true; }
		);
	}

	static function getBannableIP( $user ) {
		$dbr = wfGetDB( DB_SLAVE );
		$ip = array();
		if( is_array( $user ) ) {
			foreach( $user as $u ) {
				$ip = array_merge( $ip, self::getBannableIP( User::newFromName( $u ) ) );
			}
		} else {
			$result = $dbr->select( 'recentchanges',
				array( 'DISTINCT rc_ip' ),
				array( 'rc_user_text' => $user->getName() ),
				__METHOD__,
				array( 'ORDER BY' => 'rc_ip ASC' ) );
			while( $row = $dbr->fetchObject( $result ) ) {
				$ip[] = $row->rc_ip;
			}
		}
		$whitelist = array_flip( self::getWhitelist() );
		return array_filter( $ip,
			function($u) use ($whitelist) { if( isset( $whitelist[ $u ] ) ) return false; return true; }
		);
	}

	static function getBannablePages( $user ) {
		$dbr = wfGetDB( DB_SLAVE );
		$result = null;
		if( $user ) {
			$result = $dbr->select( 'recentchanges',
				array( 'rc_namespace', 'rc_title', 'rc_timestamp', 'COUNT(*) AS edits' ),
				array(
					'rc_user_text' => $user,
					'(rc_new = 1) OR (rc_log_type = "upload" AND rc_log_action = "upload")'
				),
				__METHOD__,
				array(
					'ORDER BY' => 'rc_timestamp DESC',
					'GROUP BY' => 'rc_namespace, rc_title'
				)
			);
		}
		$pages = array();
		if( $result ) {
			while( $row = $dbr->fetchObject( $result ) ) {
				$pages[] = Title::makeTitle( $row->rc_namespace, $row->rc_title );
			}
		}

		return $pages;
	}

	static function banIPs( $ips, $banningUser ) {
		foreach( (array)$ips as $ip ) {
			$blk = new Block( $ip, null,
				$banningUser->getID(), wfMsg('block-message'),
				wfTimestamp(), 0, Block::infinity(), 0, 1, 0, 0, 1);
			$blk->isAutoBlocking( true );
			if( $blk->insert() ) {
				$log = new LogPage('block');
				$log->addEntry('block', Title::makeTitle( NS_USER, $ip ),
					'Blocked through Special:BlockandNuke', array('infinite', $ip,  'nocreate'));
			}
		}
	}

	static function banUser( $user, $banningUser, $spammer, $um ) {
		if($user->getID() != 0 && $um ) {
			$um->merge( $user, $spammer, "block", $banningUser );
		} else {
			$blk = new Block($user->getName(), $user->getId(),
				$banningUser->getID(), wfMsg('block-message'),
				wfTimestamp(), 0, Block::infinity(), 0, 1, 0, 0, 1);
			$blk->isAutoBlocking( true );
			if($blk->insert()) {
				$log = new LogPage('block');
				$log->addEntry('block', Title::makeTitle( NS_USER, $user->getName() ),
					'Blocked through Special:BlockandNuke', array('infinite', $user->getName(),  'nocreate'));
			}
		}
	}

	static function blockUser($user, $user_id, $banningUser, $spammer, $um) {
		for($c = 0; $c < max( count($user), count($user_id) ); $c++ ){
			if( isset( $user[$c] ) ) {
				$thisUserObj = User::newFromName( $user[$c] );
			} elseif( isset( $user_id[$c] ) ) {
				$thisUserObj = User::newFromId( $user_id[$c] );
			}
			self::banUser( $thisUserObj, $banningUser, $spammer, $um );
		}
	}

	static function deletePage( $title ) {
		$file = $title->getNamespace() == NS_IMAGE ? wfLocalFile( $title ) : false;
		if ($file) {
			$reason= wfMsg( "block-delete-file" );
			$oldimage = null; // Must be passed by reference
			FileDeleteForm::doDelete( $title, $file, $oldimage, $reason, false );
		} else {
			$reason= wfMsg( "block-delete-article" );
			if( $title->isKnown() ) {
				$article = new Article( $title );
				$article->doDelete( $reason );
			}
		}
	}

	static function deletePages( $pages ) {
		foreach((array)$pages as $page) {
			self::deletePage( Title::newFromURL($page) );
		}
	}

}