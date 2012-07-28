<?php

/*
  Controller name: Buddypress Read
  Controller description: Buddypress controller for reading actions
 */

class JSON_API_BuddypressRead_Controller {

        /**
         * Returns an object with all activities
         * @global Object $json_api
         * @return Object Activities
         */
        public function get_activities () {
                /* Possible parameters:
                 * int pages: number of pages to display (default unset)
                 * int offset: number of entries per page (default 10 if pages is set, otherwise unset)
                 * int limit: number of maximum results (default 0 for unlimited)
                 * String sort: sort ASC or DESC (default DESC)
                 * String comments: 'stream' for within stream display, 'threaded' for below each activity item (default unset)
                 * Int userid: userID to filter on, comma-separated for more than one ID (default unset)
                 * String component: object to filter on e.g. groups, profile, status, friends (default unset)
                 * String type: action to filter on e.g. activity_update, profile_updated (default unset)
                 * int itemid: object ID to filter on e.g. a group_id or forum_id or blog_id etc. (default unset)
                 * int secondaryitemid: secondary object ID to filter on e.g. a post_id (default unset)
                 */

                $oReturn = new stdClass();
                global $json_api;

                if ( !bp_has_activities () )
                        return $this->error ( 'activity' );

                if ( !is_null ( $json_api->query->pages ) ) {
                        $aParams [ 'max' ] = true;
                        $aParams [ 'per_page' ] = is_null ( $json_api->query->offset ) ? 10 : $json_api->query->offset;
                        $iPages = $json_api->query->pages;
                }

                $aParams [ 'display_comments' ] = is_null ( $json_api->query->comments ) ? false : $json_api->query->comments;
                $aParams [ 'sort' ] = is_null ( $json_api->query->sort ) ? 'DESC' : 'ASC';

                $aParams [ 'filter' ] [ 'user_id' ] = is_null ( $json_api->query->userid ) ? false : $json_api->query->userid;
                $aParams [ 'filter' ] [ 'object' ] = is_null ( $json_api->query->component ) ? false : $json_api->query->component;
                $aParams [ 'filter' ] [ 'action' ] = is_null ( $json_api->query->type ) ? false : $json_api->query->type;
                $aParams [ 'filter' ] [ 'primary_id' ] = is_null ( $json_api->query->itemid ) ? false : $json_api->query->itemid;
                $aParams [ 'filter' ] [ 'secondary_id' ] = is_null ( $json_api->query->secondaryitemid ) ? false : $json_api->query->secondaryitemid;

                $iLimit = is_null ( $json_api->query->limit ) ? 0 : $json_api->query->limit;

                if ( is_null ( $json_api->query->pages ) ) {
                        $aParams [ 'page' ] = 1;
                        if ( $iLimit != 0 )
                                $aParams[ 'per_page' ] = $iLimit;
                        $aTempActivities = bp_activity_get ( $aParams );
                        if ( !empty ( $aTempActivities[ 'activities' ] ) ) {
                                $oReturn->activities [ 0 ] = $aTempActivities[ 'activities' ];
                        }
                        else {
                                return $this->error ( 'activity' );
                        }
                        return $oReturn;
                }

                for ( $i = 1; $i <= $iPages; $i++ ) {
                        if ( $iLimit != 0 && ($i * $aParams[ 'per_page' ]) > $iLimit ) {
                                $aParams[ 'per_page' ] = $aParams[ 'per_page' ] - (($i * $aParams[ 'per_page' ]) - $iLimit);
                                $bLastRun = true;
                        }
                        $aParams [ 'page' ] = $i;
                        $aTempActivities = bp_activity_get ( $aParams );
                        if ( empty ( $aTempActivities[ 'activities' ] ) ) {
                                if ( $i == 1 )
                                        return $this->error ( 'activity' );
                                else
                                        break;
                        }
                        else {
                                $oReturn->activities [ $i ] = $aTempActivities[ 'activities' ];
                                if ( $bLastRun )
                                        break;
                        }
                }

                return $oReturn;
        }

        /**
         * Returns an object with profile information
         * @global Object $json_api
         * @return Object Profile Fields
         */
        public function get_profile () {
                /* Possible parameters:
                 * String username: the username you want information from (required)
                 */
                global $json_api;
                $oReturn = new stdClass();

                if ( is_null ( $json_api->query->username ) || !username_exists ( $json_api->query->username ) ) {
                        return $this->error ( 'profile', 1 );
                }

                $oUser = get_user_by ( 'login', $json_api->query->username );

                if ( !bp_has_profile ( array ( 'user_id' => $oUser->data->ID ) ) ) {
                        return $this->error ( 'profile', 0 );
                }

                while ( bp_profile_groups ( array ( 'user_id' => $oUser->data->ID ) ) ) {
                        bp_the_profile_group ();
                        if ( bp_profile_group_has_fields () ) {
                                $sGroupName = bp_get_the_profile_group_name ();
                                while ( bp_profile_fields () ) {
                                        bp_the_profile_field ();
                                        $sFieldName = bp_get_the_profile_field_name ();
                                        if ( bp_field_has_data () ) {
                                                $sFieldValue = bp_get_the_profile_field_value ();
                                        }
                                        $oReturn->groups->$sGroupName->$sFieldName = $sFieldValue;
                                }
                        }
                }
                return $oReturn;
        }

        /**
         * Returns a String containing an error message
         * @param String $sModule Modules name
         * @param type $iCode Errorcode
         */
        private function error ( $sModule, $iCode ) {
                $oReturn = new stdClass();
                $oReturn->status = "error";
                switch ( $sModule ) {
                        case "activity":
                                $oReturn->msg = __ ( 'No Activities found.' );
                                break;
                        case "profile":
                                switch ( $iCode ) {
                                        case 0:
                                                $oReturn->msg = __ ( 'No Profile found.' );
                                                break;
                                        case 1:
                                                $oReturn->msg = __ ( 'Username not found.' );
                                                break;
                                }
                }
                if ( $oReturn->msg == "" )
                        $oReturn->msg = __ ( 'An undefined error occured.' );
                return $oReturn;
        }

}

?>