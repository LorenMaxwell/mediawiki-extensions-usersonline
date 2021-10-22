<?php

use MediaWiki\MediaWikiServices;

/**
 * @file
 */
class UsersOnlineHooks {

    /**
     * Get "time ago"
     * 
     * Modified from: https://www.php.net/manual/en/dateinterval.format.php
    */
    public static function formatDateDiff($start, $end=null) {
        if(!($start instanceof DateTime)) {
            $start = new DateTime($start);
        }
       
        if($end === null) {
            $end = new DateTime();
        }
       
        if(!($end instanceof DateTime)) {
            $end = new DateTime($start);
        }

        $interval = $end->diff($start);
        $suffix = ( $interval->invert ? ' ago' : '' );
      
        $doPlural = function($nb,$str){return $nb>1?$str.'s':$str;}; // adds plurals
       
        $format = [];
        if($interval->y !== 0) {
            $format[] = "%y ".$doPlural($interval->y, "year");
        }
        if($interval->m !== 0) {
            $format[] = "%m ".$doPlural($interval->m, "month");
        }
        if($interval->d !== 0) {
            $format[] = "%d ".$doPlural($interval->d, "day");
        }
        if($interval->h !== 0) {
            $format[] = "%h ".$doPlural($interval->h, "hour");
        }
        if($interval->i !== 0) {
            $format[] = "%i ".$doPlural($interval->i, "minute");
        }
        if($interval->s !== 0) {
            $format[] = "%s ".$doPlural($interval->s, "second");
        }
        if($interval->y === 0 && $interval->m === 0 && $interval->d === 0 && $interval->h === 0 && $interval->i === 0 && $interval->s === 0 && $interval->f !== 0) {
            return 'less than 1 second ago';
        }

        // We use the two biggest parts
        if(count($format) > 1) {
            $format = array_shift($format).", ".array_shift($format);
        } else {
            $format = array_pop($format);
        }
       
        // Prepend 'since ' or whatever you like
        return $interval->format($format) . $suffix;
    }

	/**
	 * Delete old users online
	*/ 
    private static function deleteOldVisitors() {
        global $wgUsersOnlineTimeout;

		$timeout = 3600;
		if ( is_numeric( $wgUsersOnlineTimeout ) ) {
			$timeout = $wgUsersOnlineTimeout;
		}
		$nowdatetime = date("Y-m-d H:i:s");
        $now = strtotime($nowdatetime);
        $old = $now - $timeout;
        $olddatetime = date("Y-m-d H:i:s", $old);

        $lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
        $db = $lb->getConnectionRef( DB_PRIMARY  );

		$db->delete( 'user_online', [ 'uo_user_id' => 0, 'uo_end_session < "' . $olddatetime . '"' ], __METHOD__ );
        
    }

	/**
	 * Count anonymous users online
	*/ 
	private static function countAnonsOnline() {

        $lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
        $db = $lb->getConnectionRef( DB_REPLICA  );

		$row = $db->selectRow(
			'user_online',
			'COUNT(*) AS cnt',
			'uo_user_id = 0 AND uo_end_session >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 5 MINUTE)',
			__METHOD__,
			'GROUP BY uo_ip_address'
		);
		$anons = (int)$row->cnt;

		return $anons;
	}
	
	/**
	 * Count users online
	*/ 
	private static function countUsersOnline() {

        $lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
        $db = $lb->getConnectionRef( DB_REPLICA  );

		$row = $db->selectRow(
			'user_online',
			'COUNT(*) AS cnt',
			'uo_user_id != 0 AND uo_end_session >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 5 MINUTE)',
			__METHOD__,
			'GROUP BY uo_ip_address'
		);
		$users = (int)$row->cnt;
		
		return $users;
	}

	/**
	 * Get users online
	*/ 
	private static function getUsersOnline() {

        $lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
        $db = $lb->getConnectionRef( DB_REPLICA  );

		$users = [
		    'array-currently' => [],
		    'array-recently' => []
		    ];

		$currently = $db->select(
        	[ 'user_online', 'user' ],
        	[ 'uo_user_id', 'uo_lastLinkURL', 'uo_lastPageTitle', 'uo_start_session', 'uo_end_session', 'user_name' ],
        	[
        		'uo_user_id != 0 AND uo_end_session >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 5 MINUTE)'
        	],
        	__METHOD__,
        	['ORDER BY' => 'uo_end_session DESC'],
        	[
        		'user' => [ 'INNER JOIN', [ 'user_id=uo_user_id' ] ]
        	]
		);

        if ($currently->numRows() > 0) {
    		foreach ($currently as $r) {
    		    $user = $r;
    		    $user->ago = UsersOnlineHooks::formatDateDiff($user->uo_end_session);
    		    $user->online_since = UsersOnlineHooks::formatDateDiff($user->uo_start_session);
    		    $user->user_page = MediaWikiServices::getInstance()->getUserFactory()->newFromId( (int)$user->user_id )->getUserPage()->getLocalURL();
    		    $users['array-currently'][] = (array) $user;
    		}
        }

		$recently = $db->select(
        	[ 'user_online', 'user' ],
        	[ 'uo_user_id', 'uo_lastLinkURL', 'uo_lastPageTitle', 'uo_start_session', 'uo_end_session', 'user_name' ],
        	[
        		'uo_user_id != 0 AND uo_end_session <= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 5 MINUTE) AND uo_end_session >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 24 HOUR)',
        	],
        	__METHOD__,
        	['ORDER BY' => 'uo_end_session DESC'],
        	[
        		'user' => [ 'INNER JOIN', [ 'user_id=uo_user_id' ] ]
        	]
		);
		
        if ($recently->numRows() > 0) {
    		foreach ($recently as $r) {
    		    $user = $r;
    		    $user->ago = UsersOnlineHooks::formatDateDiff($user->uo_end_session);
    		    $user->offline_since = UsersOnlineHooks::formatDateDiff($user->uo_end_session);
    		    $user->user_page = MediaWikiServices::getInstance()->getUserFactory()->newFromId( (int)$user->user_id )->getUserPage()->getLocalURL();
    		    $users['array-recently'][] = (array) $user;
    		}
        }

		return $users;
	}


	/**
	 * Create users online table
	*/ 
    public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
        
        $file = __DIR__ . '/../sql/UsersOnline.sql';
        $updater->addExtensionTable( 'user_online', $file );
        
    }
        
	/**
	 * Update users online data
	*/ 
	public static function onBeforeInitialize( \Title &$title, $unused, \OutputPage $output, \User $user, \WebRequest $request, \MediaWiki $mediaWiki ) {
	    
		$isEnabled = $output->getConfig()->get( 'ShowUsersOnline' );
		if ( !$isEnabled ) {
			return;
		}
		
		// Delete old visitors
		UsersOnlineHooks::deleteOldVisitors();

        // Get previous and current sessions
        $lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
        $db = $lb->getConnectionRef( DB_PRIMARY );

		if ($user->getId() != 0) {

            // Get user's last session_id
    		$last_session_entry = $db->selectRow(
    			'user_online',
    			['uo_id', 'uo_session_id', 'uo_start_session', 'uo_end_session', 'uo_prev_end_session'],
    			'uo_user_id = '. $user->getId(),
    			__METHOD__,
    			''
    		);
		}
		
		$same_session = ($request->getSessionId()->getId() == ($last_session_entry->uo_session_id ?? null));
		
		// row to insert to table
		$row = [
			'uo_id' => $same_session ? $last_session_entry->uo_id ?? null : null,
			'uo_user_id' => $user->getId(),
			'uo_session_id' => $request->getSessionId()->getId(),
			'uo_ip_address' => $user->getName(),
			'uo_lastLinkURL' => $title->getLinkURL(),
			'uo_lastPageTitle' => $title->getText(),
			'uo_start_session' => $same_session ? $last_session_entry->uo_start_session ?? null : date("Y-m-d H:i:s"),
			'uo_end_session' => date("Y-m-d H:i:s"),
			'uo_prev_end_session' => $same_session ? $last_session_entry->uo_prev_end_session ?? null  : $last_session_entry->uo_end_session ?? null
		];
		$method = __METHOD__;
		$db->onTransactionIdle( function() use ( $db, $method, $row ) {
			$db->upsert(
				'user_online',
				$row,
				[ 'uo_id' ],
				[
				    'uo_session_id' => $row['uo_session_id'],
				    'uo_lastLinkURL' => $row['uo_lastLinkURL'],
				    'uo_lastPageTitle' => $row['uo_lastPageTitle'],
				    'uo_start_session' => $row['uo_start_session'],
				    'uo_end_session' => $row['uo_end_session'],
				    'uo_prev_end_session' => $row['uo_prev_end_session']
				],
				$method
			);
		});

	}

	/**
	 * Pass users online data to skin
	 */
	public static function onSkinTemplateNavigation_Universal( $skin, &$links ) {
	    
		$isEnabled = $skin->getConfig()->get( 'ShowUsersOnline' );
		if ( !$isEnabled ) {
			return;
		}
		
		if (method_exists($skin, 'setTemplateVariable')) {
            // Online
    		$portlet['data-extension-portlets']['data-online'] = [];
    		
		    $portlet['data-extension-portlets']['data-online'] = [
		        'id' => 'p-online',
        		'text'  => 'Online',
        		'href'  => '/w/Main page',
        		'title' => 'Online',
        		'id'    => 'n-online',
        		'online' => UsersOnlineHooks::countUsersOnline(),
        		'guests' => UsersOnlineHooks::countAnonsOnline(),
        		'members' => UsersOnlineHooks::getUsersOnline()
	        ];

            $skin->setTemplateVariable($portlet);
		}
        
	}
    
}
