<?php

if( !defined( 'MEDIAWIKI' ) )
	die( 'Not an entry point.' );

class SpecialBlock_Nuke extends SpecialPage {
	function __construct() {
		//restrict access only to users with blockandnuke right
		parent::__construct( 'blockandnuke', 'blockandnuke' );
	}

	function execute( $par ){
		global $wgUser, $wgRequest, $wgOut, $wgBaNSpamUser;

		if( !$this->userCanExecute( $wgUser ) ){
			$this->displayRestrictionError();
			return;
		}

		$this->setHeaders();
		$this->outputHeader();

		$um = null;
		$spammer = User::newFromName( $wgBaNSpamUser );
		$posted = $wgRequest->wasPosted();
		if( $posted ) {
			$user_id = $wgRequest->getArray( 'userid' );
			$user = $wgRequest->getArray( 'names' );
			$pages = $wgRequest->getArray( 'pages' );
			$user_2 = $wgRequest->getArray( 'names_2' );
			$ips = $wgRequest->getArray( 'ip' );

			if($user){
				$wgOut->addHTML( $this->msg( 'blockandnuke-banhammer' )->escaped() );
				$this->getNewPages($user);
			} elseif( count( $pages ) || count( $user_2 ) || count( $ips ) ) {
				$wgOut->addHTML( $this->msg( 'blockandnuke-banning' )->escaped() );
				$v = false;
				$v = BanPests::blockUser( $user_2, $user_id, $wgUser, $spammer )
					|| BanPests::deletePages( $pages, $this )
					|| BanPests::banIPs( $ips, $wgUser, $this );
				if( !$v ) {
					$wgOut->addHTML( $this->msg( 'blockandnuke-nothing-to-do' )->escaped() );
				}
			} else {
				$wgOut->addHTML( $this->msg( 'blockandnuke-nothing-to-do' )->escaped() );
			}
		} else {
			$this->showUserForm();
		}
	}

	function showUserForm() {
		global $wgOut, $wgUser;

		$names = BanPests::getBannableUsers();
		$whitelist = BanPests::getWhitelist();

		$wgOut->addWikiMsg( 'blockandnuke-tools' );
		$wgOut->addHTML(
			Xml::openElement( 'form', array(
				'action' => $this->getTitle()->getLocalURL( 'action=submit' ),
				'method' => 'post' )
			).
			Html::hidden( 'wpEditToken', $wgUser->getEditToken() ).
			( '<ul>' )
		);

		//make into links  $sk = $wgUser->getSkin();

		foreach($names as $user){
			if ( !in_array( $user, $whitelist ) ) {
				$wgOut->addHTML(
					'<li>' .
					Xml::check( 'names[]', true,
						array( 'value' =>  $user )
					) .
					$user .
					"</li>\n"
				);
			}

		}
		$wgOut->addHTML(
			"</ul>\n" .
			Xml::submitButton( $this->msg( 'blockandnuke-submit-user' )->text() ) .
			"</form>"
		);
	}

	function getNewPages($user) {
		global $wgOut, $wgUser;

		$wgOut->addHTML(
			Xml::openElement(
				'form',
				array(
					'action' => $this->getTitle()->getLocalURL( 'action=delete' ),
					'method' => 'post'
				)
			) .
			Html::hidden( 'wpEditToken', $wgUser->getEditToken() ) .
			'<ul>'
		);

		$pages = BanPests::getBannablePages( $user );
		$ips = BanPests::getBannableIP( $user );
		$linkRenderer = $this->getLinkRenderer();

		if( count( $pages ) ) {
			$wgOut->addHTML( "<h2>" . $this->msg( "blockandnuke-pages" )->escaped() . "</h2>" );

			$wgOut->addHtml( "<ul>" );
			foreach( $pages as $title ) {
				$wgOut->addHtml( "<li>". $linkRenderer->makeLink( $title ) );
				$wgOut->addHtml( Html::hidden( 'pages[]', $title ) );
			}
			$wgOut->addHtml( "</ul>\n" );
		}

		if( count( $user ) ) {
			$wgOut->addHTML( "<h2>" . $this->msg( "blockandnuke-users" )->escaped() . "</h2>" );

			foreach($user as $users){
				$dbr = wfGetDB( DB_SLAVE );
				$result = $dbr->select(
					'recentchanges',
					array( 'rc_user', 'rc_user_text' ),
					array( 'rc_user_text' => $users ),
					__METHOD__,
					array(
						'ORDER BY' => 'rc_user ASC',
					)
				);
				$name = array();
				foreach( $result as $row ) {
					$name[] = array( $row->rc_user_text, $row->rc_user );
				}

				$wgOut->addHtml( "<ul>" );
				$seen = array();
				foreach( $name as $infos ) {
					list( $user_2, $user_id ) = $infos;
					if( !isset( $seen[$user_2] ) ) {
						$seen[$user_2] = true;
						$wgOut->addHtml(
							"<li>" .
							$linkRenderer->makeLink( Title::newFromText( $user_2, NS_USER ) )
						);
						$wgOut->addHTML(
							Html::hidden( 'names_2[]', $user_2 ).
							Html::hidden( 'userid[]', $user_id )
						);
					}
				}
				$wgOut->addHtml( "</ul>\n" );
			}
		}

		if( $ips ) {
			$wgOut->addHTML( "<h2>" . $this->msg( "blockandnuke-ip-addresses" )->escaped() . "</h2>" );

			foreach( $ips as $ip ) {
				$wgOut->addHtml( "<ul>" );
				$seen = array();
				if( !isset( $seen[$ip] ) ) {
					$seen[$ip] = true;
					$wgOut->addHtml(
						"<li>" .
						$linkRenderer->makeLink( Title::newFromText( $ip, NS_USER ) )
					);
					$wgOut->addHTML( Html::hidden( 'ip[]', $ip ) );
				}
				$wgOut->addHtml( "</ul>\n" );
			}
		}

		$wgOut->addHTML(
			"</ul>\n" .
			XML::submitButton( $this->msg( 'blockandnuke' )->text() ).
			"</form>"
		);
	}

	protected function getGroupName() {
		return 'pagetools';
	}
}
