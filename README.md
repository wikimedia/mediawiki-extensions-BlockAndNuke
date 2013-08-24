The Block and Nuke extension allows sysops to mass block spam accounts and delete all contributions made by those spam accounts, in just two clicks. See further documenatation at the [BlockandNuke page on MediaWiki.org](http://www.mediawiki.org/wiki/Extension:BlockandNuke).

Usage
-----

1. Edit file `extensions/BlockandNuke/whitelist.txt` and add usernames that should be ignored by the extension. This list must be edited manually. 

One username per line.

    Admin
    Dorris
    Sam

2. Go to Special:Blockandnuke. The special page is listed under Special:SpecialPages, under Other Special Pages as "Block and Nuke". A checklist shows all users who made contributions since last use of this tool and are not listed in whitelist.txt. By default all users are checked.

3. Select users you would like to block - all their contributions will be deleted. 

4. Click 'Select Users'. Then confirm by clicking the button 'Block and Nuke'.
