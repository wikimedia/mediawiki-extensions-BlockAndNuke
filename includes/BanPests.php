<?php

use MediaWiki\MediaWikiServices;

class BanPests {

	/**
	 * @throws MWException
	 * @return string[]
	 */
	public static function getWhitelist() {
		global $wgBaNwhitelist, $wgWhitelist;

		/* Backward compatibility */
		if ( isset( $wgWhitelist ) && file_exists( $wgWhitelist ) ) {
			$wgBaNwhitelist = $wgWhitelist;
		} elseif ( !isset( $wgBaNwhitelist ) || !file_exists( $wgBaNwhitelist ) ) {
			throw new MWException(
				'You need to specify a whitelist!'
				. ' $wgBaNwhitelist should point to a filename that contains the whitelist.'
			);
		}

		$file = file_get_contents( $wgBaNwhitelist );
		return preg_split( '/\r\n|\r|\n/', $file );
	}

	/**
	 * @return string[]
	 */
	public static function getBannableUsers() {
		$dbr = wfGetDB( DB_REPLICA );
		$cond = [ 'rc_source' => RecentChange::SRC_NEW ]; /* Anyone creating new pages */
		$cond[] = $dbr->makeList( /* Anyone uploading stuff */
			[
				'rc_log_type' => 'upload',
				'rc_log_action' => 'upload'
			],
			LIST_AND
		);
		$cond[] = $dbr->makeList( /* New Users older than a day who haven't done anything yet */
			[
				'rc_log_action' => 'create',
				'rc_log_type' => 'newusers',
			],
			LIST_AND
		);
		$result = $dbr->select(
			'recentchanges',
			[ 'DISTINCT rc_user_text' ],
			$dbr->makeList( $cond, LIST_OR ),
			__METHOD__,
			[ 'ORDER BY' => 'rc_user_text ASC' ]
		);
		$names = [];
		foreach ( $result as $row ) {
			$names[] = $row->rc_user_text;
		}
		$whitelist = array_flip( self::getWhitelist() );
		return array_filter( $names,
			function ( $u ) use ( $whitelist ) {
				return !isset( $whitelist[ $u ] );
			}
		);
	}

	/**
	 * @param User|string[]|string $user
	 * @return string[]
	 */
	public static function getBannableIP( $user ) {
		$dbr = wfGetDB( DB_REPLICA );
		$ip = [];
		if ( is_array( $user ) ) {
			foreach ( $user as $u ) {
				if ( $u ) {
					$ip = array_merge( $ip, self::getBannableIP( User::newFromName( $u ) ) );
				}
			}
		} elseif ( is_object( $user ) ) {
			$result = $dbr->select(
				'recentchanges',
				[ 'DISTINCT rc_ip' ],
				[ 'rc_user_text' => $user->getName() ],
				__METHOD__,
				[ 'ORDER BY' => 'rc_ip ASC' ]
			);
			foreach ( $result as $row ) {
				$ip[] = $row->rc_ip;
			}
		} else {
			$ip[] = $user;
		}
		$whitelist = array_flip( self::getWhitelist() );
		return array_filter( $ip,
			function ( $u ) use ( $whitelist ) {
				return !isset( $whitelist[ $u ] );
			}
		);
	}

	/**
	 * @param User $user
	 * @return Title[]
	 */
	public static function getBannablePages( $user ) {
		$dbr = wfGetDB( DB_REPLICA );
		$result = null;
		if ( $user ) {
			$otherConds = $dbr->makeList( [
				$dbr->makeList( [
					'rc_log_type' => 'upload',
					'rc_log_action' => 'upload'
				], Database::LIST_AND ),
				[ 'rc_source' => RecentChange::SRC_NEW ]
			], Database::LIST_OR );
			$result = $dbr->select(
				'recentchanges',
				[ 'rc_namespace', 'rc_title', 'rc_timestamp', 'COUNT(*) AS edits' ],
				[
					'rc_user_text' => $user,
					$otherConds
				],
				__METHOD__,
				[
					'ORDER BY' => 'rc_timestamp DESC',
					'GROUP BY' => 'rc_namespace, rc_title'
				]
			);
		}
		$pages = [];
		if ( $result ) {
			foreach ( $result as $row ) {
				$pages[] = Title::makeTitle( $row->rc_namespace, $row->rc_title );
			}
		}

		return $pages;
	}

	/**
	 * @param string[] $ips IPs to be banned
	 * @param User $banningUser User doing the ban
	 * @param User|null $sp
	 * @return bool
	 */
	public static function banIPs( $ips, $banningUser, $sp = null ) {
		$ret = [];
		foreach ( (array)$ips as $ip ) {
			if ( !Block::newFromTarget( $ip ) ) {
				$blk = new Block(
					[
						'address'       => $ip,
						'by'            => $banningUser->getID(),
						'reason'        => wfMessage( 'blockandnuke-message' )->text(),
						'expiry'        => wfGetDB( DB_REPLICA )->getInfinity(),
						'createAccount' => true,
						'blockEmail'    => true
					]
				);
				$blk->isAutoBlocking( true );
				if ( $blk->insert() ) {
					$log = new LogPage( 'block' );
					$log->addEntry(
						'block',
						Title::makeTitle( NS_USER, $ip ),
						'Blocked through Special:BlockandNuke',
						[ 'infinite', $ip,  'nocreate' ],
						$banningUser
					);
					$ret[] = $ip;
					if ( $sp ) {
						$sp->getOutput()->addHTML( wfMessage( "blockandnuke-banned-ip", $ip ) . '<br>' );
					}
				}
			}
		}
		$ret = array_filter( $ret );
		return (bool)$ret;
	}

	/**
	 * @param User $user User to be banned
	 * @param User $banningUser User doing the ban
	 * @param User $spammer User for account to be merged into if UserMerge installed
	 * @return array|bool|null
	 */
	public static function banUser( $user, $banningUser, $spammer ) {
		$ret = null;
		if ( !is_object( $user ) ) {
			/* Skip this one */
		} elseif ( $user->getID() != 0 && class_exists( "MergeUser" ) ) {
			$um = new MergeUser( $spammer, $user );
			$ret = $um->merge( $banningUser, __METHOD__ );
		} else {
			if ( !Block::newFromTarget( $user->getName() ) ) {
				$blk = new Block(
					[
						'address'       => $user->getName(),
						'user'          => $user->getID(),
						'by'            => $banningUser->getID(),
						'reason'        => wfMessage( 'blockandnuke-message' )->text(),
						'expiry'        => wfGetDB( DB_REPLICA )->getInfinity(),
						'createAccount' => true,
						'blockEmail'    => true
					]
				);
				$blk->isAutoBlocking( true );
				$ret = $blk->insert();
				if ( $ret ) {
					$log = new LogPage( 'block' );
					$log->addEntry(
						'block',
						Title::makeTitle( NS_USER, $user->getName() ),
						'Blocked through Special:BlockandNuke',
						[ 'infinite', $user->getName(),  'nocreate' ],
						$banningUser
					);
				}
			}
		}
		return $ret;
	}

	/**
	 * @param string[] $user
	 * @param int[] $user_id
	 * @param User $banningUser
	 * @param User $spammer
	 * @return bool
	 */
	public static function blockUser( $user, $user_id, $banningUser, $spammer ) {
		$ret = [];
		$max = max( count( $user ), count( $user_id ) );
		for ( $c = 0; $c < $max; $c++ ) {
			if ( isset( $user[$c] ) ) {
				$thisUserObj = User::newFromName( $user[$c] );
			} elseif ( isset( $user_id[$c] ) ) {
				$thisUserObj = User::newFromId( $user_id[$c] );
			}
			$ret[] = self::banUser( $thisUserObj, $banningUser, $spammer );
		}
		$ret = array_filter( $ret );
		return (bool)$ret;
	}

	/**
	 * @param Title $title
	 * @param User $deleter
	 * @param User|null $sp
	 * @return mixed
	 */
	public static function deletePage( $title, User $deleter, $sp = null ) {
		$ret = null;
		if ( $title->getNamespace() == NS_FILE ) {
			if ( method_exists( MediaWikiServices::class, 'getRepoGroup' ) ) {
				// MediaWiki 1.34+
				$file = MediaWikiServices::getInstance()->getRepoGroup()->getLocalRepo()->newFile( $title );
			} else {
				$file = wfLocalFile( $title );
			}
		} else {
			$file = false;
		}
		if ( $file ) {
			$reason = wfMessage( "blockandnuke-delete-file" )->text();
			$oldimage = null; // Must be passed by reference
			$ret = FileDeleteForm::doDelete( $title, $file, $oldimage, $reason, false, $deleter );
		} else {
			$reason = wfMessage( "blockandnuke-delete-article" )->text();
			if ( $title->isKnown() ) {
				$article = new Article( $title );
				$ret = $article->doDelete( $reason );
				if ( $ret && $sp ) {
					$sp->getOutput()->addHTML( wfMessage( "blockandnuke-deleted-page", $title ) . '<br>' );
				}
			}
		}
		return $ret;
	}

	/**
	 * @param string[] $pages
	 * @param User $deleter
	 * @param User|null $sp
	 * @return bool
	 */
	public static function deletePages( $pages, User $deleter, $sp = null ) {
		$ret = [];
		foreach ( (array)$pages as $page ) {
			$ret[] = self::deletePage( Title::newFromText( $page ), $deleter, $sp );
		}
		$ret = array_filter( $ret );
		return (bool)$ret;
	}

}
