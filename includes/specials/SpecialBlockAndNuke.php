<?php

class SpecialBlockAndNuke extends SpecialPage {
	function __construct() {
		// restrict access only to users with blockandnuke right
		parent::__construct( 'BlockandNuke', 'blockandnuke' );
	}

	/**
	 * @param string|null $par
	 */
	function execute( $par ) {
		global $wgBaNSpamUser;

		$user = $this->getUser();
		if ( !$this->userCanExecute( $user ) ) {
			$this->displayRestrictionError();
			return;
		}

		$this->setHeaders();
		$this->outputHeader();

		$um = null;
		$spammer = User::newFromName( $wgBaNSpamUser );
		$request = $this->getRequest();
		if ( $request->wasPosted() ) {
			$target_id = $request->getArray( 'userid' );
			$target = $request->getArray( 'names' );
			$pages = $request->getArray( 'pages' );
			$target_2 = $request->getArray( 'names_2' );
			$ips = $request->getArray( 'ip' );

			$out = $this->getOutput();

			if ( $target ) {
				$out->addHTML( $this->msg( 'blockandnuke-banhammer' )->escaped() );
				$this->getNewPages( $target );
			} elseif ( count( $pages ) || count( $target_2 ) || count( $ips ) ) {
				$out->addHTML( $this->msg( 'blockandnuke-banning' )->escaped() . '<br>' );
				$v = false;
				$v = BanPests::blockUser( $target_2, $target_id, $user, $spammer )
					|| BanPests::deletePages( $pages, $user, $this )
					|| BanPests::banIPs( $ips, $user, $this );
				if ( !$v ) {
					$out->addHTML( $this->msg( 'blockandnuke-nothing-to-do' )->escaped() );
				}
			} else {
				$out->addHTML( $this->msg( 'blockandnuke-nothing-to-do' )->escaped() );
			}
		} else {
			$this->showUserForm();
		}
	}

	function showUserForm() {
		$names = BanPests::getBannableUsers();
		$whitelist = BanPests::getWhitelist();
		$out = $this->getOutput();

		$out->addWikiMsg( 'blockandnuke-tools' );
		$out->addHTML(
			Xml::openElement( 'form', [
				'action' => $this->getPageTitle()->getLocalURL( 'action=submit' ),
				'method' => 'post' ]
			) .
			Html::hidden( 'wpEditToken', $this->getUser()->getEditToken() ) .
			( '<ul>' )
		);

		// make into links  $sk = $this->getUser()->getSkin();

		foreach ( $names as $user ) {
			if ( !in_array( $user, $whitelist ) ) {
				$out->addHTML(
					'<li>' .
					Xml::check( 'names[]', true,
						[ 'value' => $user ]
					) .
					htmlspecialchars( $user ) .
					"</li>\n"
				);
			}

		}
		$out->addHTML(
			"</ul>\n" .
			Xml::submitButton( $this->msg( 'blockandnuke-submit-user' )->text() ) .
			"</form>"
		);
	}

	/**
	 * @param User $user
	 */
	function getNewPages( $user ) {
		$out = $this->getOutput();
		$out->addHTML(
			Xml::openElement(
				'form',
				[
					'action' => $this->getPageTitle()->getLocalURL( 'action=delete' ),
					'method' => 'post'
				]
			) .
			Html::hidden( 'wpEditToken', $this->getUser()->getEditToken() ) .
			'<ul>'
		);

		$pages = BanPests::getBannablePages( $user );
		$ips = BanPests::getBannableIP( $user );
		$linkRenderer = $this->getLinkRenderer();

		if ( count( $pages ) ) {
			$out->addHTML( "<h2>" . $this->msg( "blockandnuke-pages" )->escaped() . "</h2>" );

			$out->addHtml( "<ul>" );
			foreach ( $pages as $title ) {
				$out->addHtml( "<li>" . $linkRenderer->makeLink( $title ) );
				$out->addHtml( Html::hidden( 'pages[]', $title ) );
			}
			$out->addHtml( "</ul>\n" );
		}

		if ( count( $user ) ) {
			$out->addHTML( "<h2>" . $this->msg( "blockandnuke-users" )->escaped() . "</h2>" );

			foreach ( $user as $users ) {
				$dbr = wfGetDB( DB_REPLICA );
				$result = $dbr->select(
					'recentchanges',
					[ 'rc_user', 'rc_user_text' ],
					[ 'rc_user_text' => $users ],
					__METHOD__,
					[
						'ORDER BY' => 'rc_user ASC',
					]
				);
				$name = [];
				foreach ( $result as $row ) {
					$name[] = [ $row->rc_user_text, $row->rc_user ];
				}

				$out->addHtml( "<ul>" );
				$seen = [];
				foreach ( $name as $infos ) {
					list( $user_2, $user_id ) = $infos;
					if ( !isset( $seen[$user_2] ) ) {
						$seen[$user_2] = true;
						$out->addHtml(
							"<li>" .
							$linkRenderer->makeLink( Title::newFromText( $user_2, NS_USER ) )
						);
						$out->addHTML(
							Html::hidden( 'names_2[]', $user_2 ) .
							Html::hidden( 'userid[]', $user_id )
						);
					}
				}
				$out->addHtml( "</ul>\n" );
			}
		}

		if ( $ips ) {
			$out->addHTML( "<h2>" . $this->msg( "blockandnuke-ip-addresses" )->escaped() . "</h2>" );

			foreach ( $ips as $ip ) {
				$out->addHtml( "<ul>" );
				$seen = [];
				if ( !isset( $seen[$ip] ) ) {
					$seen[$ip] = true;
					$out->addHtml(
						"<li>" .
						$linkRenderer->makeLink( Title::newFromText( $ip, NS_USER ) )
					);
					$out->addHTML( Html::hidden( 'ip[]', $ip ) );
				}
				$out->addHtml( "</ul>\n" );
			}
		}

		$out->addHTML(
			"</ul>\n" .
			XML::submitButton( $this->msg( 'blockandnuke' )->text() ) .
			"</form>"
		);
	}

	/** @inheritDoc */
	protected function getGroupName() {
		return 'pagetools';
	}
}
