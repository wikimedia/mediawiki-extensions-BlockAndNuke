<?php

/**
 * Aggresive maintenance script to block recent users not found in the whitelist.txt
 * extension file and mass deleting its contributions.
 *
 * PLEASE USE WITH CAUTION. ALL USERS *NOT* IN THE WHITELIST WILL BE BLOCKED AND ITS
 * CONTRIBUTIONS DELETED!
 *
 * @license GPL-3.0+ <https://www.gnu.org/licenses/gpl-3.0.html>
 */

require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/maintenance/Maintenance.php';

class BanHammer extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->requireExtension( "BlockAndNuke" );
		$this->addOption( "hammer", "Actually ban and nuke the entries, will dry run otherwise" );
		$this->addOption( "brief",  "Skip all the nitty-gritty details" );
		$this->mDescription = "Block and Nuke recent users not found in the whitelist.";
	}

	public function maybeOutput( $str ) {
		if ( !$this->hasOption( "brief" ) ) {
			$this->output( $str );
		}
	}

	public function execute() {
		global $wgBaNSpamUser;

		$this->output( "Starting " );
		$real = $this->hasOption( "hammer" );
		$brief = $this->hasOption( "brief" );
		if ( !$real ) {
			$this->output( "dry run\n" );
		} else {
			$this->output( "\n" );
		}

		$bannable = BanPests::getBannableUsers();
		$pages = BanPests::getBannablePages( $bannable );

		$this->output(
			sprintf(
				"Found %d bannable users and %d pages:\n", count( $bannable ), count( $pages )
			)
		);
		if ( count( $pages ) ) {
			$this->maybeOutput( "Pages\n" );
			foreach ( $pages as $page ) {
				if ( $page ) {
					$this->maybeOutput( "\t$page" );
					if ( $real ) {
						$this->maybeOutput( " ... deleting\n" );
						BanPests::deletePage( $page );
					} else {
						$this->maybeOutput( "\n" );
					}
				}
			}
		}

		$spammer = User::newFromName( $wgBaNSpamUser );
		$banningUser = User::newFromName( "WikiSysop" );
		if ( count( $bannable ) ) {
			$this->maybeOutput( "Users\n" );
			foreach ( $bannable as $user ) {
				$this->maybeOutput( "\t$user" );
				$u = User::newFromName( $user );
				if ( $u === false ) {
					$ips = [ $user ];
				} else {
					$ips = BanPests::getBannableIP( $u );
				}
				if ( $real ) {
					$this->maybeOutput( " ... banning\n" );
					if ( $u !== false ) {
						BanPests::banUser( $u, $banningUser, $spammer );
					}
					if ( $ips ) {
						foreach ( $ips as $ip ) {
							$this->maybeOutput( "\t\tEnsuring ban on $ip\n" );
							BanPests::banIPs( $ip, $banningUser );
						}
					}
				} else {
					$this->maybeOutput( "\n" );
					foreach ( $ips as $ip ) {
						$this->maybeOutput( "\t\t{$ip}\n" );
					}
				}
			}
		}
	}
}

$maintClass = "BanHammer";
require_once RUN_MAINTENANCE_IF_MAIN;
