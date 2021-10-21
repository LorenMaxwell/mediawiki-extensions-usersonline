MediaWiki Users Online
========================

This extension shows the user's current session, previous session, and current page and returns the data for a Mustache template

Installation
------------

1) Download and place files in a directory called UsersOnline in your extensions folder.
2) Add the following code at the bottom of your LocalSettings.php: wfLoadExtension( 'UsersOnline' );
3) Run the update script which will automatically create the necessary database tables that this extension needs.
4) Done – Navigate to Special:Version on your wiki to verify that the extension is successfully installed.

Configuration
-----------

		"ShowUsersOnline": true
    
    Turn this feature on/off
    
		"UsersOnlineTimeout": 3600

    Amount of time (in seconds) to show anonymous users as having recently visited

Compatible skins
---------------------

Automatically works with Lift skin: https://github.com/LorenMaxwell/mediawiki-skins-lift
