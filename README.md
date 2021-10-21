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

Mustache template JSON
---------------------
JSON is returned in the following format:
````
{
    "data-extension-portlets": {
	"data-online": {
	    "id": "n-online",
	    "text": "Online",
	    "href": "\/w\/Main page",
	    "title": "Online",
	    "online": 0,
	    "guests": 0,
	    "members": {
		"array-currently": [],
		"array-recently": [
		    {
			"uo_user_id": "1",
			"uo_lastLinkURL": "\/beta\/wiki\/index.php?title=Main_Page",
			"uo_lastPageTitle": "Main Page",
			"uo_start_session": "2021-10-21 00:37:32",
			"uo_end_session": "2021-10-21 00:48:39",
			"user_name": "Recent visitor",
			"ago": "14 minutes, 50 seconds ago",
			"offline_since": "14 minutes, 50 seconds ago",
			"user_page": "\/beta\/wiki\/index.php?title=User:172.69.68.228"
		    }
		]
	    }
	}
    }
}
````
Compatible skins
---------------------

Automatically works with Lift skin: https://github.com/LorenMaxwell/mediawiki-skins-lift
