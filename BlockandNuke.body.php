<?php

if( !defined( 'MEDIAWIKI' ) )
	die( 'Not an entry point.' );

class SpecialBlock_Nuke extends SpecialPage {
	function __construct() {
		wfLoadExtensionMessages( 'BlockandNuke' );
		//restrict access only to admin
		parent::__construct( 'blockandnuke', 'blockandnuke' );

	}

	function execute( $par ){
		global $wgUser, $wgRequest, $wgOut;

		if( !$this->userCanExecute( $wgUser ) ){
			$this->displayRestrictionError();
			return;
		}

		$this->setHeaders();
		$this->outputHeader();

		$posted = $wgRequest->wasPosted();
		if( $posted ) {
			$user_id = $wgRequest->getArray('userid');
			$user = $wgRequest->getArray('names');
			$pages = $wgRequest->getArray( 'pages' );
			$user_2 = $wgRequest->getArray('names_2');

			if($user){
				$wgOut->addHTML( wfMsg( "block-banhammer" ) );
				$this->getNewPages($user);
			}

			if($pages){
				$this->blockUser($user_2, $user_id);
				$this->doDelete( $pages );
			}
		} else {
			$this->whiteList();
		}

	}

	function whiteList() {
		global $wgOut, $wgUser, $wgBaNwhitelist;

		$fh = fopen($wgBaNwhitelist, 'r');
		$file = fread($fh,200);
		$pieces = (preg_split('/\r\n|\r|\n/', $file));
		fclose($fh);

		$dbr = wfGetDB( DB_SLAVE );
		$result = $dbr->select( 'recentchanges',
			array( 'DISTINCT rc_user', 'rc_user_text' ),
			array( 'rc_new' => 1 ), # OR (rc_log_type = "upload" AND rc_log_action = "upload")
			__METHOD__,
			array( 'ORDER BY' => 'rc_user_text ASC' ) );
		$names=array();
		while( $row = $dbr->fetchObject( $result ) ) {
			$names[]=array($row->rc_user_text, $row->rc_user);
		}

		$wgOut->addWikiMsg( 'block-tools' );
		$wgOut->addHTML(
			Xml::openElement( 'form', array(
				'action' => $this->getTitle()->getLocalURL( 'action=submit' ),
				'method' => 'post')).
			HTML::hidden( 'wpEditToken', $wgUser->editToken() ).
			( '<ul>' ) );

		//make into links  $sk = $wgUser->getSkin();

		foreach($names as $user_info){
			list($user, $user_id) = $user_info;

				if (!in_array($user, $pieces)){
					$wgOut->addHTML( '<li>'.
						Xml::check( 'names[]', true,
						array( 'value' =>  $user)).
						($user).
						"</li>\n" );
				}

		}
		$wgOut->addHTML(
			"</ul>\n" .
			Xml::submitButton( wfMsg( 'block-submit-user' ) ).
			"</form>" );


	}

	function getNewPages($user) {
		global $wgOut, $wgUser;

		$wgOut->addHTML(
			Xml::openElement( 'form', array(
					'action' => $this->getTitle()->getLocalURL( 'action=delete' ),
					'method' => 'post')).
			HTML::hidden( 'wpEditToken', $wgUser->editToken() ).
			( '<ul>' ) );

		$dbr = wfGetDB( DB_SLAVE );
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

		$pages = array();
		while( $row = $dbr->fetchObject( $result ) ) {
			$pages[] = array( Title::makeTitle( $row->rc_namespace, $row->rc_title ), $row->edits );
		}

		foreach( $pages as $info ) {
			list($title, $edits) = $info;
			$wgOut->addHtml(HTML::hidden( 'pages[]', $title));
		}

		foreach($user as $users){
			$dbr = wfGetDB( DB_SLAVE );
			$result = $dbr->select( 'recentchanges',
				array( 'rc_user', 'rc_user_text' ),
				array( 'rc_user_text' => $users ),
				__METHOD__,
				array(
					'ORDER BY' => 'rc_user ASC',
				));
			$name=array();
			while( $row = $dbr->fetchObject( $result ) ) {
				$name[]=array($row->rc_user_text, $row->rc_user);
			}

			foreach($name as $infos) {
				list($user_2, $user_id) = $infos;
				$wgOut->addHTML(HTML::hidden( 'names_2[]', $user_2).
					HTML::hidden( 'userid[]', $user_id));
			}
		}

		$wgOut->addHTML(
			"</ul>\n" .
			XML::submitButton( wfMsg( 'blockandnuke' ) ).
			"</form>"
		);
	}


	function blockUser($user, $user_id) {
		global $wgUser, $wgOut;

		// if($user_id[$c]== 0){$user_id = $this->uid}

		for($c = 0; $c < max( count($user), count($user_id) ); $c++ ){

			$blk = new Block($user[$c], $user_id[$c], $wgUser->getID(), wfMsg('block-message'),
				wfTimestamp(), 0, Block::infinity(), 0, 1, 0, 0, 1);
			if($blk->insert()) {
				$log = new LogPage('block');
				$log->addEntry('block', Title::makeTitle( NS_USER, $user[$c] ),
					'Blocked through Special:BlockandNuke', array('infinite',   $user[$c],  'nocreate'));
			}
		}
	}

	function doDelete( $pages ) {

		foreach($pages as $page) {

			$title = Title::newFromURL($page);
			$file = $title->getNamespace() == NS_IMAGE ? wfLocalFile( $title ) : false;
			if ($file) {
				$reason= wfMsg( "block-delete-file" );
				$oldimage = null; // Must be passed by reference
				FileDeleteForm::doDelete( $title, $file, $oldimage, $reason, false );
			} else {
				$reason= wfMsg( "block-delete-article" );
				$article = new Article( $title );
				$article->doDelete( $reason );
			}
		}
	}


}