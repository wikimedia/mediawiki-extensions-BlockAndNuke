<?php

require_once( dirname( dirname( __DIR__ ) ) . '/maintenance/Maintenance.php' );

class BanHammer extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addOption( "hammer", "Actually ban and nuke the entries, will dry run otherwise" );
		$this->addOption( "brief",  "Skip all the nitty-gritty details" );
		$this->mDescription = "Block and Nuke recent users not found in the whitelist.";
	}

	public function maybeOutput( $str ) {
		if(!$this->hasOption( "brief" ) ){
			$this->output( $str );
		}
	}

	public function execute() {
		global $wgBaNSpamUser;

		$this->output( "Starting ");
		$real = $this->hasOption( "hammer" );
		$brief = $this->hasOption( "brief" );
		if( !$real ) {
			$this->output( "dry run\n" );
		} else {
			$this->output( "\n" );
		}

		$bannable = BanPests::getBannableUsers();
		$pages = BanPests::getBannablePages( $bannable );

		$this->output( sprintf( "Found %d bannable users and %d pages:\n", count( $bannable ), count( $pages ) ) );
		if( count( $pages ) ) {
			$this->maybeOutput( "Pages\n" );
			foreach( $pages as $page ) {
				if( $page ) {
					$this->maybeOutput( "\t$page" );
					if( $real ) {
						$this->maybeOutput( " ... deleting\n" );
						BanPests::deletePage( Title::newFromURL( $page ) );
					} else {
						$this->maybeOutput( "\n" );
					}
				}
			}
		}

		$spammer = User::newFromName( $wgBaNSpamUser );
		$banningUser = User::newFromName( "WikiSysop" );
		$um = null;
		if( class_exists( "UserMerger" ) ) {
			$um = new UserMerger( null );
		}
		if( count( $bannable ) ) {
			$this->maybeOutput( "Users\n" );
			foreach( $bannable as $user ) {
				$this->maybeOutput( "\t$user" );
				$u = User::newFromName( $user );
				if ( $u === false ) {
					$ips = array( $user );
				} else {
					$ips = BanPests::getBannableIP( $u );
				}
				if( $real ) {
					$this->maybeOutput( " ... banning\n" );
					if( $u !== false ) {
						BanPests::banUser( $u, $banningUser, $spammer, $um );
					}
					if( $ips ) {
						foreach($ips as $ip) {
							$this->maybeOutput( "\t\tEnsuring ban on $ip\n" );
							BanPests::banIPs( $ip, $banningUser  );
						}
					}
				} else {
					$this->maybeOutput( "\n" );
					foreach($ips as $ip) {
						$this->maybeOutput( "\t\t{$ip}\n" );
					}
				}
			}
		}
	}
}

$maintClass = "BanHammer";
require_once( RUN_MAINTENANCE_IF_MAIN );
