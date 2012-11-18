<?php

/*
  Controller name: Buddypress Read
  Controller description: Buddypress controller for reading actions
 */

require_once JSON_API_FOR_BUDDYPRESS_HOME . '/library/functions.class.php';

class JSON_API_BuddypressRead_Controller {

    /**
     * Returns an Array with all activities
     * @param int pages: number of pages to display (default unset)
     * @param int offset: number of entries per page (default 10 if pages is set, otherwise unset)
     * @param int limit: number of maximum results (default 0 for unlimited)
     * @param String sort: sort ASC or DESC (default DESC)
     * @param String comments: 'stream' for within stream display, 'threaded' for below each activity item (default unset)
     * @param Int userid: userID to filter on, comma-separated for more than one ID (default unset)
     * @param String component: object to filter on e.g. groups, profile, status, friends (default unset)
     * @param String type: action to filter on e.g. activity_update, profile_updated (default unset)
     * @param int itemid: object ID to filter on e.g. a group_id or forum_id or blog_id etc. (default unset)
     * @param int secondaryitemid: secondary object ID to filter on e.g. a post_id (default unset)
     * @return array activities: an array containing the activities
     */
    public function activity_get_activities() {
        $oReturn = new stdClass();
        $this->init('activity', 'see_activity');

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
                foreach ($aTempActivities['activities'] as $oActivity) {
                    $oReturn->activities[(int) $oActivity->id]->component = $oActivity->component;
                    $oReturn->activities[(int) $oActivity->id]->user[(int) $oActivity->user_id]->username = $oActivity->user_login;
                    $oReturn->activities[(int) $oActivity->id]->user[(int) $oActivity->user_id]->mail = $oActivity->user_email;
                    $oReturn->activities[(int) $oActivity->id]->user[(int) $oActivity->user_id]->display_name = $oActivity->display_name;
                    $oReturn->activities[(int) $oActivity->id]->type = $oActivity->type;
                    $oReturn->activities[(int) $oActivity->id]->time = $oActivity->date_recorded;
                    $oReturn->activities[(int) $oActivity->id]->is_hidden = $oActivity->hide_sitewide === "0" ? false : true;
                    $oReturn->activities[(int) $oActivity->id]->is_spam = $oActivity->is_spam === "0" ? false : true;
                }
                $oReturn->count = count($aTempActivities['activities']);
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
                foreach ($aTempActivities['activities'] as $oActivity) {
                    $oReturn->activities[(int) $oActivity->id]->component = $oActivity->component;
                    $oReturn->activities[(int) $oActivity->id]->user[(int) $oActivity->user_id]->username = $oActivity->user_login;
                    $oReturn->activities[(int) $oActivity->id]->user[(int) $oActivity->user_id]->mail = $oActivity->user_email;
                    $oReturn->activities[(int) $oActivity->id]->user[(int) $oActivity->user_id]->display_name = $oActivity->display_name;
                    $oReturn->activities[(int) $oActivity->id]->type = $oActivity->type;
                    $oReturn->activities[(int) $oActivity->id]->time = $oActivity->date_recorded;
                    $oReturn->activities[(int) $oActivity->id]->is_hidden = $oActivity->hide_sitewide === "0" ? false : true;
                    $oReturn->activities[(int) $oActivity->id]->is_spam = $oActivity->is_spam === "0" ? false : true;
                }
                $oReturn->count = count($aTempActivities['activities']);
                if ($bLastRun)
                    break;
            }
        }

        return $oReturn;
    }

    /**
     * Returns an array with the profile's fields
     * @param String username: the username you want information from (required)
     * @return array profilefields: an array containing the profilefields
     */
    public function profile_get_profile() {
        $this->init('xprofile');
        $oReturn = new stdClass();

        if ($this->username === false || !username_exists($this->username)) {
            return $this->error('xprofile', 1);
        }

        $oUser = get_user_by('login', $this->username);

        if (!bp_has_profile(array('user_id' => $oUser->data->ID))) {
            return $this->error('xprofile', 0);
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
                    $oReturn->profilefields->$sGroupName->$sFieldName = $sFieldValue;
                }
            }
        }
        return $oReturn;
    }

    /**
     * Returns an array with messages for the current username
     * @param String box: the box you the messages are in (possible values are 'inbox', 'sentbox', 'notices', default is 'inbox')
     * @param int per_page: items to be displayed per page (default 10)
     * @param boolean limit: maximum numbers of emtries (default no limit)
     * @return array messages: contains the messages
     */
    public function messages_get_messages() {
        $this->init('messages');
        $oReturn = new stdClass();

        $aParams ['box'] = $this->box;
        $aParams ['per_page'] = $this->per_page;
        $aParams ['max'] = $this->limit;

        if (bp_has_message_threads($aParams)) {
            while (bp_message_threads()) {
                bp_message_thread();
                $aTemp = new stdClass();
                preg_match("#>(.*?)<#", bp_get_message_thread_from(), $aFrom);
                $oUser = get_user_by('login', $aFrom[1]);
                $aTemp->from[(int) $oUser->data->ID]->username = $aFrom[1];
                $aTemp->from[(int) $oUser->data->ID]->mail = $oUser->data->user_email;
                $aTemp->from[(int) $oUser->data->ID]->display_name = $oUser->data->display_name;
                preg_match("#>(.*?)<#", bp_get_message_thread_to(), $aTo);
                $oUser = get_user_by('login', $aTo[1]);
                $aTemp->to[(int) $oUser->data->ID]->username = $aTo[1];
                $aTemp->to[(int) $oUser->data->ID]->mail = $oUser->data->user_email;
                $aTemp->to[(int) $oUser->data->ID]->display_name = $oUser->data->display_name;
                $aTemp->subject = bp_get_message_thread_subject();
                $aTemp->excerpt = bp_get_message_thread_excerpt();
                $aTemp->link = bp_get_message_thread_view_link();

                $oReturn->messages [(int) bp_get_message_thread_id()] = $aTemp;
            }
        } else {
            return $this->error('messages');
        }
        return $oReturn;
    }

    /**
     * Returns an array with notifications for the current user
     * @param none there are no parameters to be used
     * @return array notifications: the notifications as a link
     */
    public function notifications_get_notifications() {
        $this->init('notifications');
        $oReturn = new stdClass();

        $aNotifications = bp_core_get_notifications_for_user(get_current_user_id());

        if (empty($aNotifications)) {
            return $this->error('notifications');
        }

        foreach ($aNotifications as $sNotificationMessage) {
            $oReturn->notifications [] = $sNotificationMessage;
        }
        $oReturn->count = count($aNotifications);

        return $oReturn;
    }

    /**
     * Returns an array with friends for the given user
     * @param String username: the username you want information from (required)
     * @return array friends: array with the friends the user got
     */
    public function friends_get_friends() {
        $this->init('friends');
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
            $oUser = get_user_by('id', $sFriendID);
            $oReturn->friends [(int) $sFriendID]->username = $oUser->data->user_login;
            $oReturn->friends [(int) $sFriendID]->display_name = $oUser->data->display_name;
            $oReturn->friends [(int) $sFriendID]->mail = $oUser->data->user_email;
        }
        $oReturn->count = count($aFriends);
        return $oReturn;
    }

    /**
     * Returns an array with friendship requests for the given user
     * @params String username: the username you want information from (required)
     * @return array friends: an array containing friends with some mor info
     */
    public function friends_get_friendship_request() {
        $this->init('friends');
        $oReturn = new stdClass();

        if ($this->username === false || !username_exists($this->username)) {
            return $this->error('friends', 0);
        }
        $oUser = get_user_by('login', $this->username);

        if (!is_user_logged_in() || get_current_user_id() != $oUser->data->ID)
            return $this->error('base', 0);

        $sFriends = bp_get_friendship_requests($oUser->data->ID);
        $aFriends = explode(",", $sFriends);

        if ($aFriends[0] == "0")
            return $this->error('friends', 2);
        foreach ($aFriends as $sFriendID) {
            $oUser = get_user_by('id', $sFriendID);
            $oReturn->friends [(int) $sFriendID]->username = $oUser->data->user_login;
            $oReturn->friends [(int) $sFriendID]->display_name = $oUser->data->display_name;
            $oReturn->friends [(int) $sFriendID]->mail = $oUser->data->user_email;
        }
        $oReturn->count = count($oReturn->friends);
        return $oReturn;
    }

    /**
     * Returns a string with the status of friendship of the two users
     * @param String username: the username you want information from (required)
     * @param String friendname: the name of the possible friend (required)
     * @return string friendshipstatus: 'is_friend', 'not_friends' or 'pending'
     */
    public function friends_get_friendship_status() {
        $this->init('friends');
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
     * Returns an array with groups matching to the given parameters
     * @param String username: the username you want information from (default => all groups)
     * @param Boolean show_hidden: Show hidden groups to non-admins (default: false)
     * @param String type: active, newest, alphabetical, random, popular, most-forum-topics or most-forum-posts (default active)
     * @param int page: The page to return if limiting per page (default 1)
     * @param int per_page: The number of results to return per page (default 20)
     * @return array groups: array with meta infos
     */
    public function groups_get_groups() {
        $this->init('groups');
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
            $oReturn->groups[(int) $aGroup->id]->name = $aGroup->name;
            $oReturn->groups[(int) $aGroup->id]->description = $aGroup->description;
            $oReturn->groups[(int) $aGroup->id]->status = $aGroup->status;
            if ($aGroup->status == "private" && !is_user_logged_in() && !$aGroup->is_member === true)
                continue;
            $oUser = get_user_by('id', $aGroup->creator_id);
            $oReturn->groups[(int) $aGroup->id]->creator[(int) $aGroup->creator_id]->username = $oUser->data->user_login;
            $oReturn->groups[(int) $aGroup->id]->creator[(int) $aGroup->creator_id]->mail = $oUser->data->user_email;
            $oReturn->groups[(int) $aGroup->id]->creator[(int) $aGroup->creator_id]->display_name = $oUser->data->display_name;
            $oReturn->groups[(int) $aGroup->id]->slug = $aGroup->slug;
            $oReturn->groups[(int) $aGroup->id]->is_forum_enabled = $aGroup->enable_forum == "1" ? true : false;
            $oReturn->groups[(int) $aGroup->id]->date_created = $aGroup->date_created;
            $oReturn->groups[(int) $aGroup->id]->count_member = $aGroup->total_member_count;
        }

        $oReturn->count = count($aGroups['groups']);

        return $oReturn;
    }

    /**
     * Returns a boolean depending on an existing invite
     * @param String username: the username you want information from (required)
     * @param int groupid: the groupid you are searching for (if not set, groupslug is searched; groupid or groupslug required)
     * @param String groupslug: the slug to search for (just used if groupid is not set; groupid or groupslug required)
     * @param String type: sent to check for sent invites, all to check for all
     * @return boolean is_invited: true if invited, else false
     */
    public function groups_check_user_has_invite_to_group() {
        $this->init('groups');

        $oReturn = new stdClass();

        if ($this->username === false || !username_exists($this->username)) {
            return $this->error('groups', 1);
        }
        $oUser = get_user_by('login', $this->username);

        $mGroupName = $this->get_group_from_params();

        if ($mGroupName !== true)
            return $this->error('groups', $mGroupName);

        if ($this->type === false || $this->type != "sent" || $this->type != "all")
            $this->type = 'sent';

        $oReturn->is_invited = groups_check_user_has_invite((int) $oUser->data->ID, $this->groupid, $this->type);
        $oReturn->is_invited = is_null($oReturn->is_invited) ? false : true;

        return $oReturn;
    }

    /**
     * Returns a boolean depending on an existing memebership request
     * @param String username: the username you want information from (required)
     * @param int groupid: the groupid you are searching for (if not set, groupslug is searched; groupid or groupslug required)
     * @param String groupslug: the slug to search for (just used if groupid is not set; groupid or groupslug required)
     * @return boolean membership_requested: true if requested, else false
     */
    public function groups_check_user_membership_request_to_group() {
        $this->init('groups');

        $oReturn = new stdClass();

        if ($this->username === false || !username_exists($this->username)) {
            return $this->error('groups', 1);
        }
        $oUser = get_user_by('login', $this->username);

        $mGroupName = $this->get_group_from_params();

        if ($mGroupName !== true)
            return $this->error('groups', $mGroupName);

        $oReturn->membership_requested = groups_check_for_membership_request((int) $oUser->data->ID, $this->groupid);
        $oReturn->membership_requested = is_null($oReturn->membership_requested) ? false : true;

        return $oReturn;
    }

    /**
     * Returns an array containing all admins for the given group
     * @param int groupid: the groupid you are searching for (if not set, groupslug is searched; groupid or groupslug required)
     * @param String groupslug: the slug to search for (just used if groupid is not set; groupid or groupslug required)
     * @return array group_admins: array containing the admins
     */
    public function groups_get_group_admins() {
        $this->init('groups');

        $oReturn = new stdClass();

        $mGroupExists = $this->get_group_from_params();

        if ($mGroupExists === false)
            return $this->error('base', 0);
        else if (is_int($mGroupExists) && $mGroupExists !== true)
            return $this->error('groups', $mGroupExists);

        $aGroupAdmins = groups_get_group_admins($this->groupid);
        foreach ($aGroupAdmins as $oGroupAdmin) {
            $oUser = get_user_by('id', $oGroupAdmin->user_id);
            $oReturn->group_admins[(int) $oGroupAdmin->user_id]->username = $oUser->data->user_login;
            $oReturn->group_admins[(int) $oGroupAdmin->user_id]->mail = $oUser->data->user_email;
            $oReturn->group_admins[(int) $oGroupAdmin->user_id]->display_name = $oUser->data->display_name;
        }
        $oReturn->count = count($aGroupAdmins);
        return $oReturn;
    }

    /**
     * Returns an array containing all mods for the given group
     * @params int groupid: the groupid you are searching for (if not set, groupslug is searched; groupid or groupslug required)
     * @params String groupslug: the slug to search for (just used if groupid is not set; groupid or groupslug required)
     * @return array group_mods: array containing the mods
     */
    public function groups_get_group_mods() {
        $this->init('groups');

        $oReturn = new stdClass();

        $mGroupExists = $this->get_group_from_params();

        if ($mGroupExists === false)
            return $this->error('base', 0);
        else if (is_int($mGroupExists) && $mGroupExists !== true)
            return $this->error('groups', $mGroupExists);

        $oReturn->group_mods = groups_get_group_mods($this->groupid);
        $aGroupMods = groups_get_group_mods($this->groupid);
        foreach ($aGroupMods as $aGroupMod) {
            $oUser = get_user_by('id', $aGroupMod->user_id);
            $oReturn->group_mods[(int) $aGroupMod->user_id]->username = $oUser->data->user_login;
            $oReturn->group_mods[(int) $aGroupMod->user_id]->mail = $oUser->data->user_email;
            $oReturn->group_mods[(int) $aGroupMod->user_id]->display_name = $oUser->data->display_name;
        }
        return $oReturn;
    }

    /**
     * Returns an array containing all members for the given group
     * @params int groupid: the groupid you are searching for (if not set, groupslug is searched; groupid or groupslug required)
     * @params String groupslug: the slug to search for (just used if groupid is not set; groupid or groupslug required)
     * @params int limit: maximum members displayed
     * @return array group_members: group members with some more info
     */
    public function groups_get_group_members() {
        $this->init('groups');

        $oReturn = new stdClass();

        $mGroupExists = $this->get_group_from_params();

        if ($mGroupExists === false)
            return $this->error('base', 0);
        else if (is_int($mGroupExists) && $mGroupExists !== true)
            return $this->error('groups', $mGroupExists);

        $aMembers = groups_get_group_members($this->groupid, $this->limit);
        if ($aMembers === false) {
            $oReturn->group_members = array();
            $oReturn->count = 0;
            return $oReturn;
        }

        foreach ($aMembers['members'] as $aMember) {
            $oReturn->group_members[(int) $aMember->user_id]->username = $aMember->user_login;
            $oReturn->group_members[(int) $aMember->user_id]->mail = $aMember->user_email;
            $oReturn->group_members[(int) $aMember->user_id]->display_name = $aMember->display_name;
        }
        $oReturn->count = $aMembers['count'];

        return $oReturn;
    }

    /**
     * Returns an array containing info about the group forum
     * @param int forumid: the forumid you are searching for (if not set, forumslug is searched; forumid or forumslug required)
     * @param String forumslug: the slug to search for (just used if forumid is not set; forumid or forumslug required)
     * @return array forums: the group forum with metainfo
     */
    public function groupforum_get_forum() {
        $this->init('forums');

        $oReturn = new stdClass();

        $mForumExists = $this->groupforum_check_forum_existence();

        if ($mForumExists === false)
            return $this->error('base', 0);
        else if (is_int($mForumExists) && $mForumExists !== true)
            return $this->error('forums', $mForumExists);

        $oForum = bp_forums_get_forum((int) $this->forumid);

        $oReturn->forums[(int) $oForum->forum_id]->name = $oForum->forum_name;
        $oReturn->forums[(int) $oForum->forum_id]->slug = $oForum->forum_slug;
        $oReturn->forums[(int) $oForum->forum_id]->description = $oForum->forum_desc;
        $oReturn->forums[(int) $oForum->forum_id]->topics_count = (int) $oForum->topics;
        $oReturn->forums[(int) $oForum->forum_id]->post_count = (int) $oForum->posts;
        return $oReturn;
    }

    /**
     * Returns an array containing info about the group forum
     * @param int groupid: the groupid you are searching for (if not set, groupslug is searched; groupid or groupslug required)
     * @param String groupslug: the slug to search for (just used if groupid is not set; groupid or groupslug required)
     * @return array forums: the group forum for the group
     */
    public function groupforum_get_forum_by_group() {
        $this->init('forums');

        $oReturn = new stdClass();

        $mGroupExists = $this->get_group_from_params();

        if ($mGroupExists === false)
            return $this->error('base', 0);
        else if (is_int($mGroupExists) && $mGroupExists !== true)
            return $this->error('forums', $mGroupExists);

        $oGroup = groups_get_group(array('group_id' => $this->groupid));
        if ($oGroup->enable_forum == "0")
            return $this->error('forums', 0);
        $iForumId = groups_get_groupmeta($oGroup->id, 'forum_id');
        if ($iForumId == "0")
            return $this->error('forums', 1);
        $oForum = bp_forums_get_forum((int) $iForumId);

        $oReturn->forums[(int) $oForum->forum_id]->name = $oForum->forum_name;
        $oReturn->forums[(int) $oForum->forum_id]->slug = $oForum->forum_slug;
        $oReturn->forums[(int) $oForum->forum_id]->description = $oForum->forum_desc;
        $oReturn->forums[(int) $oForum->forum_id]->topics_count = (int) $oForum->topics;
        $oReturn->forums[(int) $oForum->forum_id]->post_count = (int) $oForum->posts;
        return $oReturn;
    }

    /**
     * Returns an array containing the topics from a group's forum
     * @param int forumid: the forumid you are searching for (if not set, forumid is searched; forumid or forumslug required)
     * @param String forumslug: the forumslug to search for (just used if forumid is not set; forumid or forumslug required)
     * @param int page: the page number you want to display (default 1)
     * @param int per_page: the number of results you want per page (default 15)
     * @param String type: newest, popular, unreplied, tag (default newest)
     * @param String tagname: just used if type = tag
     * @param boolean detailed: true for detailed view (default false)
     * @return array topics: all the group forum topics found
     */
    public function groupforum_get_forum_topics() {
        $this->init('forums');

        $oReturn = new stdClass();

        $mForumExists = $this->groupforum_check_forum_existence();

        if ($mForumExists === false)
            return $this->error('base', 0);
        else if (is_int($mForumExists) && $mForumExists !== true)
            return $this->error('forums', $mForumExists);

        $aConfig = array();
        $aConfig['type'] = $this->type;
        $aConfig['filter'] = $this->type == 'tag' ? $this->tagname : false;
        $aConfig['forum_id'] = $this->forumid;
        $aConfig['page'] = $this->page;
        $aConfig['per_page'] = $this->per_page;

        $aTopics = bp_forums_get_forum_topics($aConfig);
        if (is_null($aTopics))
            $this->error('forums', 7);
        foreach ($aTopics as $aTopic) {
            $oReturn->topics[(int) $aTopic->topic_id]->title = $aTopic->topic_title;
            $oReturn->topics[(int) $aTopic->topic_id]->slug = $aTopic->topic_slug;
            $oUser = get_user_by('id', $aTopic->topic_poster);
            $oReturn->topics[(int) $aTopic->topic_id]->poster[(int) $oUser->data->ID]->username = $oUser->data->user_login;
            $oReturn->topics[(int) $aTopic->topic_id]->poster[(int) $oUser->data->ID]->mail = $oUser->data->user_email;
            $oReturn->topics[(int) $aTopic->topic_id]->poster[(int) $oUser->data->ID]->display_name = $oUser->data->display_name;
            $oReturn->topics[(int) $aTopic->topic_id]->post_count = (int) $aTopic->topic_posts;
            if ($this->detailed === true) {
                $oTopic = bp_forums_get_topic_details($aTopic->topic_id);

                $oUser = get_user_by('id', $oTopic->topic_last_poster);
                $oReturn->topics[(int) $aTopic->topic_id]->last_poster[(int) $oTopic->topic_last_poster]->username = $oUser->data->user_login;
                $oReturn->topics[(int) $aTopic->topic_id]->last_poster[(int) $oTopic->topic_last_poster]->mail = $oUser->data->user_email;
                $oReturn->topics[(int) $aTopic->topic_id]->last_poster[(int) $oTopic->topic_last_poster]->display_name = $oUser->data->display_name;
                $oReturn->topics[(int) $aTopic->topic_id]->start_time = $oTopic->topic_start_time;
                $oReturn->topics[(int) $aTopic->topic_id]->forum_id = (int) $oTopic->forum_id;
                $oReturn->topics[(int) $aTopic->topic_id]->topic_status = $oTopic->topic_status;
                $oReturn->topics[(int) $aTopic->topic_id]->is_open = (int) $oTopic->topic_open === 1 ? true : false;
                $oReturn->topics[(int) $aTopic->topic_id]->is_sticky = (int) $oTopic->topic_sticky === 1 ? true : false;
            }
        }
        $oReturn->count = count($aTopics);
        return $oReturn;
    }

    /**
     * Returns an array containing the posts from a group's forum
     * @param int topicid: the topicid you are searching for (if not set, topicslug is searched; topicid or topicslug required)
     * @param String topicslug: the slug to search for (just used if topicid is not set; topicid or topicslugs required)
     * @param int page: the page number you want to display (default 1)
     * @param int per_page: the number of results you want per page (default 15)
     * @param String order: desc for descending or asc for ascending (default asc)
     * @return array posts: all the group forum posts found
     */
    public function groupforum_get_topic_posts() {
        $this->init('forums');

        $oReturn = new stdClass();

        $mTopicExists = $this->groupforum_check_topic_existence();
        if ($mTopicExists === false)
            return $this->error('base', 0);
        else if (is_int($mTopicExists) && $mTopicExists !== true)
            return $this->error('forums', $mTopicExists);

        $aConfig = array();
        $aConfig['topic_id'] = $this->topicid;
        $aConfig['page'] = $this->page;
        $aConfig['per_page'] = $this->per_page;
        $aConfig['order'] = $this->order;
        $aPosts = bp_forums_get_topic_posts($aConfig);

        foreach ($aPosts as $oPost) {
            $oReturn->posts[(int) $oPost->post_id]->topicid = (int) $oPost->topic_id;
            $oUser = get_user_by('id', (int) $oPost->poster_id);
            $oReturn->posts[(int) $oPost->post_id]->poster[(int) $oPost->poster_id]->username = $oUser->data->user_login;
            $oReturn->posts[(int) $oPost->post_id]->poster[(int) $oPost->poster_id]->mail = $oUser->data->user_email;
            $oReturn->posts[(int) $oPost->post_id]->poster[(int) $oPost->poster_id]->display_name = $oUser->data->display_name;
            $oReturn->posts[(int) $oPost->post_id]->post_text = $oPost->post_text;
            $oReturn->posts[(int) $oPost->post_id]->post_time = $oPost->post_time;
            $oReturn->posts[(int) $oPost->post_id]->post_position = (int) $oPost->post_position;
        }
        $oReturn->count = count($aPosts);

        return $oReturn;
    }

    /**
     * Returns an array containing info about the sitewide forum
     * @param int forumid: the forumid you are searching for (if not set, forumslug is searched; forumid or forumslug required)
     * @param String forumslug: the slug to search for (just used if forumid is not set; forumid or forumslug required)
     * @return array forums: sitewide forum with some infos
     */
    public function sitewideforum_get_forum() {
        $this->init('forums');

        $oReturn = new stdClass();

        $mForumExists = $this->sitewideforum_check_forum_existence();

        if ($mForumExists !== true)
            return $this->error('forums', $mForumExists);
        foreach ($this->forumid as $iId) {
            $oForum = bbp_get_forum((int) $iId);
            $oReturn->forums[$iId]->title = $oForum->post_title;
            $oReturn->forums[$iId]->name = $oForum->post_name;
            $oUser = get_user_by('id', $oForum->post_author);
            $oReturn->forums[$iId]->author[$oForum->post_author]->username = $oUser->data->user_login;
            $oReturn->forums[$iId]->author[$oForum->post_author]->mail = $oUser->data->user_email;
            $oReturn->forums[$iId]->author[$oForum->post_author]->display_name = $oUser->data->display_name;
            $oReturn->forums[$iId]->date = $oForum->post_date;
            $oReturn->forums[$iId]->last_change = $oForum->post_modified;
            $oReturn->forums[$iId]->status = $oForum->post_status;
            $oReturn->forums[$iId]->name = $oForum->post_name;
            $iTopicCount = bbp_get_forum_topic_count((int) $this->forumid);
            $oReturn->forums[$iId]->topics_count = is_null($iTopicCount) ? 0 : (int) $iTopicCount;
            $iPostCount = bbp_get_forum_post_count((int) $this->forumid);
            $oReturn->forums[$iId]->post_count = is_null($iPostCount) ? 0 : (int) $iPostCount;
        }

        return $oReturn;
    }

    /**
     * Returns an array containing all sitewide forums
     * @params int parentid: all children of the given id (default 0 = all)
     * @return array forums: all sitewide forums
     */
    public function sitewideforum_get_all_forums() {
        $this->init('forums');

        $oReturn = new stdClass();
        global $wpdb;
        $sParentQuery = $this->parentid === false ? "" : " AND post_parent=" . (int) $this->parentid;
        $aForums = $wpdb->get_results($wpdb->prepare(
                        "SELECT ID, post_parent, post_author, post_title, post_date, post_modified
                 FROM   $wpdb->posts
                 WHERE  post_type='forum'" . $sParentQuery
                ));

        if (empty($aForums))
            return $this->error('forums', 9);

        foreach ($aForums as $aForum) {
            $iId = (int) $aForum->ID;
            $oUser = get_user_by('id', (int) $aForum->post_author);
            $oReturn->forums[$iId]->author[(int) $aForum->post_author]->username = $oUser->data->user_login;
            $oReturn->forums[$iId]->author[(int) $aForum->post_author]->mail = $oUser->data->user_email;
            $oReturn->forums[$iId]->author[(int) $aForum->post_author]->display_name = $oUser->data->display_name;
            $oReturn->forums[$iId]->date = $aForum->post_date;
            $oReturn->forums[$iId]->last_changes = $aForum->post_modified;
            $oReturn->forums[$iId]->title = $aForum->post_title;
            $oReturn->forums[$iId]->parent = (int) $aForum->post_parent;
        }
        $oReturn->count = count($aForums);
        return $oReturn;
    }

    /**
     * Returns an array containing all topics of a sitewide forum
     * @param int forumid: the forumid you are searching for (if not set, forumslug is searched; forumid or forumslug required)
     * @param String forumslug: the slug to search for (just used if forumid is not set; forumid or forumslug required)
     * @param boolean display_content: set this to true if you want the content to be displayed too (default false)
     * @return array forums->topics: array of sitewide forums with the topics in it
     */
    public function sitewideforum_get_forum_topics() {
        $this->init('forums');

        $oReturn = new stdClass();

        $mForumExists = $this->sitewideforum_check_forum_existence();

        if ($mForumExists !== true)
            return $this->error('forums', $mForumExists);
        global $wpdb;
        foreach ($this->forumid as $iId) {
            $aTopics = $wpdb->get_results($wpdb->prepare(
                            "SELECT ID, post_parent, post_author, post_title, post_date, post_modified, post_content
                     FROM   $wpdb->posts
                     WHERE  post_type='topic'
                     AND post_parent='" . $iId . "'"
                    ));
            if (empty($aTopics)) {
                $oReturn->forums[(int) $iId]->topics = "";
                continue;
            }
            foreach ($aTopics as $aTopic) {
                $oUser = get_user_by('id', (int) $aTopic->post_author);
                $oReturn->forums[(int) $iId]->topics[(int) $aTopic->ID]->author[(int) $aTopic->post_author]->username = $oUser->data->user_login;
                $oReturn->forums[(int) $iId]->topics[(int) $aTopic->ID]->author[(int) $aTopic->post_author]->mail = $oUser->data->user_email;
                $oReturn->forums[(int) $iId]->topics[(int) $aTopic->ID]->author[(int) $aTopic->post_author]->display_name = $oUser->data->display_name;
                $oReturn->forums[(int) $iId]->topics[(int) $aTopic->ID]->date = $aTopic->post_date;
                if ($this->display_content !== false)
                    $oReturn->forums[(int) $iId]->topics[(int) $aTopic->ID]->content = $aTopic->post_content;
                $oReturn->forums[(int) $iId]->topics[(int) $aTopic->ID]->last_changes = $aTopic->post_modified;
                $oReturn->forums[(int) $iId]->topics[(int) $aTopic->ID]->title = $aTopic->post_title;
            }
            $oReturn->forums[(int) $iId]->count = count($aTopics);
        }
        return $oReturn;
    }

    /**
     * Returns an array containing all replies to a topic from a sitewide forum
     * @param int topicid: the topicid you are searching for (if not set, topicslug is searched; topicid or topicsslug required)
     * @param String topicslug: the slug to search for (just used if topicid is not set; topicid or topicslug required)
     * @param boolean display_content: set this to true if you want the content to be displayed too (default false)
     * @return array topics->replies: an array containing the replies
     */
    public function sitewideforum_get_topic_replies() {
        $this->init('forums');

        $oReturn = new stdClass();

        $mForumExists = $this->sitewideforum_check_topic_existence();

        if ($mForumExists !== true)
            return $this->error('forums', $mForumExists);
        foreach ($this->topicid as $iId) {
            global $wpdb;
            $aReplies = $wpdb->get_results($wpdb->prepare(
                            "SELECT ID, post_parent, post_author, post_title, post_date, post_modified, post_content
                     FROM   $wpdb->posts
                     WHERE  post_type='reply'
                     AND post_parent='" . $iId . "'"
                    ));

            if (empty($aReplies)) {
                $oReturn->topics[$iId]->replies = "";
                $oReturn->topics[$iId]->count = 0;
                continue;
            }
            foreach ($aReplies as $oReply) {
                $oUser = get_user_by('id', (int) $oReply->post_author);
                $oReturn->topics[$iId]->replies[(int) $oReply->ID]->author[(int) $oReply->post_author]->username = $oUser->data->user_login;
                $oReturn->topics[$iId]->replies[(int) $oReply->ID]->author[(int) $oReply->post_author]->mail = $oUser->data->user_email;
                $oReturn->topics[$iId]->replies[(int) $oReply->ID]->author[(int) $oReply->post_author]->display_name = $oUser->data->display_name;
                $oReturn->topics[$iId]->replies[(int) $oReply->ID]->date = $oReply->post_date;
                if ($this->display_content !== false)
                    $oReturn->topics[$iId]->replies[(int) $oReply->ID]->content = $oReply->post_content;
                $oReturn->topics[$iId]->replies[(int) $oReply->ID]->last_changes = $oReply->post_modified;
                $oReturn->topics[$iId]->replies[(int) $oReply->ID]->title = $oReply->post_title;
            }
            $oReturn->topics[$iId]->count = count($aReplies);
        }

        return $oReturn;
    }

    /**
     * Returns the settings for the current user
     * @params none no parameters
     * @return object settings: an object full of the settings
     */
    public function settings_get_settings() {
        $this->init('settings');
        $oReturn = new stdClass();

        if ($this->username === false || !username_exists($this->username)) {
            return $this->error('settings', 0);
        }

        $oUser = get_user_by('login', $this->username);

        if (!is_user_logged_in() || get_current_user_id() != $oUser->data->ID)
            return $this->error('base', 0);

        $oReturn->user->mail = $oUser->data->user_email;

        $sNewMention = bp_get_user_meta($oUser->data->ID, 'notification_activity_new_mention', true);
        $sNewReply = bp_get_user_meta($oUser->data->ID, 'notification_activity_new_reply', true);
        $sSendRequests = bp_get_user_meta($oUser->data->ID, 'notification_friends_friendship_request', true);
        $sAcceptRequests = bp_get_user_meta($oUser->data->ID, 'notification_friends_friendship_accepted', true);
        $sGroupInvite = bp_get_user_meta($oUser->data->ID, 'notification_groups_invite', true);
        $sGroupUpdate = bp_get_user_meta($oUser->data->ID, 'notification_groups_group_updated', true);
        $sGroupPromo = bp_get_user_meta($oUser->data->ID, 'notification_groups_admin_promotion', true);
        $sGroupRequest = bp_get_user_meta($oUser->data->ID, 'notification_groups_membership_request', true);
        $sNewMessages = bp_get_user_meta($oUser->data->ID, 'notification_messages_new_message', true);
        $sNewNotices = bp_get_user_meta($oUser->data->ID, 'notification_messages_new_notice', true);

        $oReturn->settings->new_mention = $sNewMention == 'yes' ? true : false;
        $oReturn->settings->new_reply = $sNewReply == 'yes' ? true : false;
        $oReturn->settings->send_requests = $sSendRequests == 'yes' ? true : false;
        $oReturn->settings->accept_requests = $sAcceptRequests == 'yes' ? true : false;
        $oReturn->settings->group_invite = $sGroupInvite == 'yes' ? true : false;
        $oReturn->settings->group_update = $sGroupUpdate == 'yes' ? true : false;
        $oReturn->settings->group_promo = $sGroupPromo == 'yes' ? true : false;
        $oReturn->settings->group_request = $sGroupRequest == 'yes' ? true : false;
        $oReturn->settings->new_message = $sNewMessages == 'yes' ? true : false;
        $oReturn->settings->new_notice = $sNewNotices == 'yes' ? true : false;

        return $oReturn;
    }

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

    public function __get($sName) {
        return isset(JSON_API_FOR_BUDDYPRESS_FUNCTION::$sVars[$sName]) ? JSON_API_FOR_BUDDYPRESS_FUNCTION::$sVars[$sName] : NULL;
    }

}

?>