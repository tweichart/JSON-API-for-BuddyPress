<?php

/*
  Controller name: Buddypress Read
  Controller description: Buddypress controller for reading actions
 */

require_once JSON_API_FOR_BUDDYPRESS_HOME . '/library/functions.class.php';

class JSON_API_BuddypressRead_Controller {

    /**
     * Returns an object with all activities
     * @return Object Activities
     */
    public function get_activities() {
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
        $this->initVars('activity');

        if (!bp_has_activities())
            return $this->error('activity');
        if ($this->pages !== 1) {
            $aParams ['max'] = true;
            $aParams ['per_page'] = $this->offset;
            $iPages = $this->pages;
        }

        $aParams ['display_comments'] = $this->comments;
        $aParams ['sort'] = $this->sort;

        $aParams ['filter'] ['user_id'] = $this->userid;
        $aParams ['filter'] ['object'] = $this->component;
        $aParams ['filter'] ['action'] = $this->type;
        $aParams ['filter'] ['primary_id'] = $this->itemid;
        $aParams ['filter'] ['secondary_id'] = $this->secondaryitemid;
        $iLimit = $this->limit;

        if ($this->pages === 1) {
            $aParams ['page'] = 1;
            if ($iLimit != 0)
                $aParams['per_page'] = $iLimit;
            $aTempActivities = bp_activity_get($aParams);
            if (!empty($aTempActivities['activities'])) {
                $oReturn->activities [0] = $aTempActivities['activities'];
            } else {
                return $this->error('activity');
            }
            return $oReturn;
        }

        for ($i = 1; $i <= $iPages; $i++) {
            if ($iLimit != 0 && ($i * $aParams['per_page']) > $iLimit) {
                $aParams['per_page'] = $aParams['per_page'] - (($i * $aParams['per_page']) - $iLimit);
                $bLastRun = true;
            }
            $aParams ['page'] = $i;
            $aTempActivities = bp_activity_get($aParams);
            if (empty($aTempActivities['activities'])) {
                if ($i == 1)
                    return $this->error('activity');
                else
                    break;
            }
            else {
                $oReturn->activities [$i] = $aTempActivities['activities'];
                if ($bLastRun)
                    break;
            }
        }

        return $oReturn;
    }

    /**
     * Returns an object with profile information
     * @return Object Profile Fields
     */
    public function get_profile() {
        /* Possible parameters:
         * String username: the username you want information from (required)
         */
        $this->initVars('profile');
        $oReturn = new stdClass();

        if ($this->username === false || !username_exists($this->username)) {
            return $this->error('profile', 1);
        }

        $oUser = get_user_by('login', $this->username);

        if (!bp_has_profile(array('user_id' => $oUser->data->ID))) {
            return $this->error('profile', 0);
        }

        while (bp_profile_groups(array('user_id' => $oUser->data->ID))) {
            bp_the_profile_group();
            if (bp_profile_group_has_fields()) {
                $sGroupName = bp_get_the_profile_group_name();
                while (bp_profile_fields()) {
                    bp_the_profile_field();
                    $sFieldName = bp_get_the_profile_field_name();
                    if (bp_field_has_data()) {
                        $sFieldValue = bp_get_the_profile_field_value();
                    }
                    $oReturn->groups->$sGroupName->$sFieldName = $sFieldValue;
                }
            }
        }
        return $oReturn;
    }

    /**
     * Returns an object with messages for the current user
     * @return Object Messages
     */
    public function get_messages() {
        /* Possible parameters:
         * String box: the box you the messages are in (possible values are 'inbox', 'sentbox', 'notices', default is 'inbox')
         * int per_page: items to be displayed per page (default 10)
         * boolean limit: maximum numbers of emtries (default no limit)
         */
        $this->initVars('message');
        $oReturn = new stdClass();

        $aParams ['box'] = $this->box;
        $aParams ['per_page'] = $this->per_page;
        $aParams ['max'] = $this->limit;

        if (bp_has_message_threads($aParams)) {
            while (bp_message_threads()) {
                bp_message_thread();
                $aTemp = new stdClass();

                $aTemp->id = bp_get_message_thread_id();
                $aTemp->from = bp_get_message_thread_from();
                $aTemp->to = bp_get_message_thread_to();
                $aTemp->subject = bp_get_message_thread_subject();
                $aTemp->excerpt = bp_get_message_thread_excerpt();
                $aTemp->link = bp_get_message_thread_view_link();

                $oReturn->messages [] = $aTemp;
            }
        } else {
            return $this->error('message');
        }
        return $oReturn;
    }

    /**
     * Returns an object with notifications for the current user
     * @return Object Notifications
     */
    public function get_notifications() {
        /* Possible parameters:
         * none
         */
        $oReturn = new stdClass();

        $aNotifications = bp_core_get_notifications_for_user(get_current_user_id());

        if (empty($aNotifications))
            return $this->error('notification');

        foreach ($aNotifications as $sNotificationMessage) {
            $oTemp = new stdClass();
            $oTemp->msg = $sNotificationMessage;
            $oReturn->notifications [] = $oTemp;
        }

        return $oReturn;
    }

    /**
     * Returns an object with friends for the given user
     * @return Object Friends
     */
    public function get_friends() {
        /* Possible parameters:
         * String username: the username you want information from (required)
         */
        $this->initVars('friends');
        $oReturn = new stdClass();

        if ($this->username === false || !username_exists($this->username)) {
            return $this->error('friends', 0);
        }

        $oUser = get_user_by('login', $this->username);

        $sFriends = bp_get_friend_ids($oUser->data->ID);
        $aFriends = explode(",", $sFriends);
        if ($aFriends[0] == "")
            return $this->error('friends', 1);
        foreach ($aFriends as $sFriendID) {
            $oReturn->friends [] = (int) $sFriendID;
        }
        $oReturn->count = count($oReturn->friends);
        return $oReturn;
    }

    /**
     * Returns an object with friendship requests for the given user
     * @return Object Friends
     */
    public function get_friendship_request() {
        /* Possible parameters:
         * String username: the username you want information from (required)
         */
        $this->initVars('friends');
        $oReturn = new stdClass();

        if ($this->username === false || !username_exists($this->username)) {
            return $this->error('friends', 0);
        }
        $oUser = get_user_by('login', $this->username);

        $sFriends = bp_get_friendship_requests($oUser->data->ID);
        $aFriends = explode(",", $sFriends);
        var_dump($aFriends);
        if ($aFriends[0] == "0")
            return $this->error('friends', 2);
        foreach ($aFriends as $sFriendID) {
            $oReturn->friends [] = (int) $sFriendID;
        }
        $oReturn->count = count($oReturn->friends);
        return $oReturn;
    }

    /**
     * Returns an object with the status of friendship of the two users
     * @return Object Friends
     */
    public function get_friendship_status() {
        /* Possible parameters:
         * String username: the username you want information from (required)
         * String friendname: the name of the possible friend (required)
         */
        $this->initVars('friends');
        $oReturn = new stdClass();

        if ($this->username === false || !username_exists($this->username)) {
            return $this->error('friends', 0);
        }

        if ($this->friendname === false || !username_exists($this->friendname)) {
            return $this->error('friends', 3);
        }

        $oUser = get_user_by('login', $this->username);
        $oUserFriend = get_user_by('login', $this->friendname);

        $oReturn->friendshipstatus = friends_check_friendship_status($oUser->data->ID, $oUserFriend->data->ID);
        return $oReturn;
    }

    /**
     * Returns an object with groups matching to the given parameters
     * @return Object Groups
     */
    public function get_groups() {
        /* Possible parameters:
         * String username: the username you want information from (default => all groups)
         * Boolean show_hidden: Show hidden groups to non-admins (default: false)
         * String type: active, newest, alphabetical, random, popular, most-forum-topics or most-forum-posts (default active)
         * int page: The page to return if limiting per page (default 1)
         * int per_page: The number of results to return per page (default 20)
         */
        $this->initVars('groups');
        $oReturn = new stdClass();

        if ($this->username !== false || username_exists($this->username)) {
            $oUser = get_user_by('login', $this->username);
            $aParams ['user_id'] = $oUser->data->ID;
        }

        $aParams ['show_hidden'] = $this->show_hidden;
        $aParams ['type'] = $this->type;
        $aParams ['page'] = $this->page;
        $aParams ['per_page'] = $this->per_page;

        $aGroups = groups_get_groups($aParams);

        if ($aGroups['total'] == "0")
            return $this->error('groups', 0);

        foreach ($aGroups['groups'] as $aGroup) {
            $oReturn->groups [] = $aGroup;
        }

        $oReturn->count = (int) $aGroups['total'];

        return $oReturn;
    }

    /**
     * Returns a boolean with the result of the match
     * @return boolean is_invited
     */
    public function check_user_has_invite_to_group() {
        /* Possible parameters:
         * String username: the username you want information from (required)
         * int groupid: the groupid you are searching for (if not set, groupslug is searched; groupid or groupslug required)
         * String groupslug: the slug to search for (just used if groupid is not set; groupid or groupslug required)
         * String type: sent to check for sent invites, all to check for all
         */
        $this->initVars('groups');

        $oReturn = new stdClass();

        if ($this->username === false || !username_exists($this->username)) {
            return $this->error('groups', 1);
        }
        $oUser = get_user_by('login', $this->username);

        if ($this->groupid === false && $this->groupslug === false)
            return $this->error('groups', 2);

        if (groups_get_group(array('group_id' => $this->groupid))) {
            $this->groupid = groups_get_id(sanitize_title($this->groupslug));
            if ($this->groupid === 0)
                return $this->error('groups', 3);
        }
        if ($this->type === false || $this->type != "sent" || $this->type != "all")
            $this->type = 'sent';

        $oReturn->is_invited = groups_check_user_has_invite((int) $oUser->data->ID, $this->groupid, $this->type);
        $oReturn->is_invited = is_null($oReturn->is_invited) ? false : true;

        return $oReturn;
    }

    /**
     * Method to handle calls for the library
     * @param String $sName name of the static method to call
     * @param Array $aArguments arguments for the method
     * @return return value of static library function, otherwise null
     */
    public function __call($sName, $aArguments) {
        if (class_exists("JSON_API_FOR_BUDDYPRESS_FUNCTION") &&
                method_exists(JSON_API_FOR_BUDDYPRESS_FUNCTION, $sName) &&
                is_callable("JSON_API_FOR_BUDDYPRESS_FUNCTION::" . $sName)) {
            try {
                return call_user_func_array("JSON_API_FOR_BUDDYPRESS_FUNCTION::" . $sName, $aArguments);
            } catch (Exception $e) {
                $oReturn = new stdClass();
                $oReturn->status = "error";
                $oReturn->msg = $e->getMessage();
                die(json_encode($oReturn));
            }
        }
        else
            return NULL;
    }

    /**
     * Method to handle calls for parameters
     * @param String $sName Name of the variable
     * @return mixed value of the variable, otherwise null
     */
    public function __get($sName) {
        return isset(JSON_API_FOR_BUDDYPRESS_FUNCTION::$sVars[$sName]) ? JSON_API_FOR_BUDDYPRESS_FUNCTION::$sVars[$sName] : NULL;
    }

}

?>