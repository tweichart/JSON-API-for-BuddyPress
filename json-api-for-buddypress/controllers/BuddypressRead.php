<?php

/*
  Controller name: Buddypress Read
  Controller description: Buddypress controller for reading actions
 */

require_once JSON_API_FOR_BUDDYPRESS_HOME . '/library/functions.class.php';

class JSON_API_BuddypressRead_Controller {

    //tbd: check results for type
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
            return $this->error('friend', 0);
        }

        $oUser = get_user_by('login', $this->username);

        $sFriends = bp_get_friend_ids($oUser->data->ID);
        $aFriends = explode(",", $sFriends);
        if ($aFriends[0] == "")
            return $this->error('friend', 1);
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
            return $this->error('friend', 0);
        }
        $oUser = get_user_by('login', $this->username);

        $sFriends = bp_get_friendship_requests($oUser->data->ID);
        $aFriends = explode(",", $sFriends);
        if ($aFriends[0] == "0")
            return $this->error('friend', 2);
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
            return $this->error('friend', 0);
        }

        if ($this->friendname === false || !username_exists($this->friendname)) {
            return $this->error('friend', 3);
        }

        $oUser = get_user_by('login', $this->username);
        $oUserFriend = get_user_by('login', $this->friendname);

        $oReturn->friendshipstatus = friends_check_friendship_status($oUser->data->ID, $oUserFriend->data->ID);
        return $oReturn;
    }
    
    //tbd: slug check

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
            return $this->error('group', 0);

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
            return $this->error('group', 1);
        }
        $oUser = get_user_by('login', $this->username);

        $mGroupName = $this->get_group_from_params();

        if ($mGroupName !== true)
            return $this->error('group', $mGroupName);

        if ($this->type === false || $this->type != "sent" || $this->type != "all")
            $this->type = 'sent';

        $oReturn->is_invited = groups_check_user_has_invite((int) $oUser->data->ID, $this->groupid, $this->type);
        $oReturn->is_invited = is_null($oReturn->is_invited) ? false : true;

        return $oReturn;
    }

    /**
     * Returns a boolean with the result of the match
     * @return boolean membership_requested
     */
    public function check_user_membership_request_to_group() {
        /* Possible parameters:
         * String username: the username you want information from (required)
         * int groupid: the groupid you are searching for (if not set, groupslug is searched; groupid or groupslug required)
         * String groupslug: the slug to search for (just used if groupid is not set; groupid or groupslug required)
         */
        $this->initVars('groups');

        $oReturn = new stdClass();

        if ($this->username === false || !username_exists($this->username)) {
            return $this->error('group', 1);
        }
        $oUser = get_user_by('login', $this->username);

        $mGroupName = $this->get_group_from_params();

        if ($mGroupName !== true)
            return $this->error('group', $mGroupName);

        $oReturn->membership_requested = groups_check_for_membership_request((int) $oUser->data->ID, $this->groupid);
        $oReturn->membership_requested = is_null($oReturn->membership_requested) ? false : true;

        return $oReturn;
    }

    /**
     * Returns an array containing all admins for the given group
     * @return Array group_admins
     */
    public function get_group_admins() {
        /* Possible parameters:
         * int groupid: the groupid you are searching for (if not set, groupslug is searched; groupid or groupslug required)
         * String groupslug: the slug to search for (just used if groupid is not set; groupid or groupslug required)
         */
        $this->initVars('groups');

        $oReturn = new stdClass();

        $mGroupName = $this->get_group_from_params();

        if ($mGroupName !== true)
            return $this->error('group', $mGroupName);

        $oReturn->group_admins = groups_get_group_admins($this->groupid);
        return $oReturn;
    }

    /**
     * Returns an array containing all mods for the given group
     * @return Array group_mods
     */
    public function get_group_mods() {
        /* Possible parameters:
         * int groupid: the groupid you are searching for (if not set, groupslug is searched; groupid or groupslug required)
         * String groupslug: the slug to search for (just used if groupid is not set; groupid or groupslug required)
         */
        $this->initVars('groups');

        $oReturn = new stdClass();

        $mGroupName = $this->get_group_from_params();

        if ($mGroupName !== true)
            return $this->error('group', $mGroupName);

        $oReturn->group_mods = groups_get_group_mods($this->groupid);
        return $oReturn;
    }

    /**
     * Returns an array containing all members for the given group
     * @return Array group_members
     */
    public function get_group_members() {
        /* Possible parameters:
         * int groupid: the groupid you are searching for (if not set, groupslug is searched; groupid or groupslug required)
         * String groupslug: the slug to search for (just used if groupid is not set; groupid or groupslug required)
         * int limit: maximum members displayed
         */
        $this->initVars('groups');

        $oReturn = new stdClass();

        $mGroupName = $this->get_group_from_params();

        if ($mGroupName !== true)
            return $this->error('group', $mGroupName);
        $aMembers = groups_get_group_members($this->groupid, $this->limit);
        if ($aMembers === false) {
            $oReturn->group_members = array();
            $oReturn->count = 0;
            return $oReturn;
        }

        foreach ($aMembers['members'] as $aMember) {
            $oReturn->group_members[] = $aMember;
        }
        $oReturn->count = $aMembers['count'];

        return $oReturn;
    }
    
    /**
     * Returns an object containing info about the forum
     * @return Object Forum
     */
    public function groupforum_get_forum(){
        /* Possible parameters:
         * int forumid: the forumid you are searching for (if not set, forumslug is searched; forumid or forumslug required)
         * String forumslug: the slug to search for (just used if forumid is not set; forumid or forumslug required)
         */
        $this->initVars('forums');
        
        $oReturn = new stdClass();
        
        $mForumExists = $this->groupforum_check_forum_existence();

        if ($mForumExists !== true)
            return $this->error('forum', $mForumExists );
        $oForum = bp_forums_get_forum((int) $this->forumid);
        
        $oReturn->forum->id = (int) $oForum->forum_id;
        $oReturn->forum->name = $oForum->forum_name;
        $oReturn->forum->slug = $oForum->forum_slug;
        $oReturn->forum->description = $oForum->forum_desc;
        $oReturn->forum->topics_count = (int) $oForum->topics;
        $oReturn->forum->post_count = (int) $oForum->posts;
        return $oReturn;
    }
    
    /**
     * Returns an object containing info about the forum
     * @return Object Forum
     */
    public function sitewideforum_get_forum(){
        /* Possible parameters:
         * int forumid: the forumid you are searching for (if not set, forumslug is searched; forumid or forumslug required)
         * String forumslug: the slug to search for (just used if forumid is not set; forumid or forumslug required)
         */
        $this->initVars('forums');
        
        $oReturn = new stdClass();
        
        $mForumExists = $this->sitewideforum_check_forum_existence();

        if ($mForumExists !== true)
            return $this->error('forum', $mForumExists );
        foreach ($this->forumid as $iId){
            $oForum = bbp_get_forum((int) $iId);
            $oReturn->forum[$iId]->id = (int) $oForum->ID;
            $oReturn->forum[$iId]->title = $oForum->post_title;
            $oReturn->forum[$iId]->name = $oForum->post_name;
            $oReturn->forum[$iId]->author = $oForum->post_author;
            $oReturn->forum[$iId]->date = $oForum->post_date;
            $oReturn->forum[$iId]->last_change = $oForum->post_modified;
            $oReturn->forum[$iId]->status = $oForum->post_status;
            $oReturn->forum[$iId]->name = $oForum->post_name;
            $iTopicCount = bbp_get_forum_topic_count((int)$this->forumid);
            $oReturn->forum[$iId]->topics_count = is_null($iTopicCount) ? 0 : $iTopicCount;
            $iPostCount = bbp_get_forum_post_count((int)$this->forumid);
            $oReturn->forum[$iId]->post_count = is_null($iPostCount) ? 0 : $iPostCount;
        }
        
        return $oReturn;
    }
    
    /**
     * Returns an object containing all forums
     * @return Object Forums
     */
    public function sitewideforum_get_all_forums(){
        /* Possible parameters:
         * int parentid: all children of the given id (default 0 = all)
         */
        $this->initVars('forums');
        
        $oReturn = new stdClass();
        global $wpdb;
        $sParentQuery = $this->parentid === false ? "" : " AND post_parent=".(int)$this->parentid;
        $aForums = $wpdb->get_results($wpdb->prepare(
                "SELECT ID, post_parent, post_author, post_title, post_date, post_modified
                 FROM   $wpdb->posts
                 WHERE  post_type='forum'".$sParentQuery
                ));
        
        if (empty($aForums))
            return $this->error('forum', 9);
        
        foreach ($aForums as $aForum){
            $iId = (int)$aForum->ID;
            $oReturn->forums[$iId]['author'] = (int)$aForum->post_author;
            $oReturn->forums[$iId]['date'] = $aForum->post_date;
            $oReturn->forums[$iId]['last_changes'] = $aForum->post_modified;
            $oReturn->forums[$iId]['title'] = $aForum->post_title;
            $oReturn->forums[$iId]['parent'] = (int) $aForum->post_parent;
        }
        return $oReturn;
    }
    
    /**
     * Returns an object containing info about the forum
     * @return Object Forum
     */
    public function groupforum_get_forum_by_group(){
        /* Possible parameters:
         * int groupid: the groupid you are searching for (if not set, groupslug is searched; groupid or groupslug required)
         * String groupslug: the slug to search for (just used if groupid is not set; groupid or groupslug required)
         */
        $this->initVars('forums');
        
        $oReturn = new stdClass();

        $mGroupName = $this->get_group_from_params();
        if ($mGroupName !== true)
            return $this->error('forum', $mGroupName);
        
        $oGroup = groups_get_group(array('group_id' => $this->groupid));
        if ($oGroup->enable_forum == "0")
            return $this->error('forum', 0);
        $iForumId = groups_get_groupmeta($oGroup->id, 'forum_id');
        if ($iForumId == "0")
            return $this->error('forum', 1);
        $oForum = bp_forums_get_forum((int) $iForumId);
        
        $oReturn->forum->id = (int) $oForum->forum_id;
        $oReturn->forum->name = $oForum->forum_name;
        $oReturn->forum->slug = $oForum->forum_slug;
        $oReturn->forum->description = $oForum->forum_desc;
        $oReturn->forum->topics_count = (int) $oForum->topics;
        $oReturn->forum->post_count = (int) $oForum->posts;
        return $oReturn;
    }
    
    /**
     * Returns an array containing the topics
     * @return Array topics
     */
    public function groupforum_get_forum_topics(){
        /* Possible parameters:
         * int forumid: the groupid you are searching for (if not set, groupslug is searched; groupid or groupslug required)
         * String forumslug: the slug to search for (just used if groupid is not set; groupid or groupslug required)
         * int page: the page number you want to display (default 1)
         * int per_page: the number of results you want per page (default 15)
         * String type: newest, popular, unreplied, tag (default newest)
         * String tagname: just used if type = tag
         */
        $this->initVars('forums');
        
        $oReturn = new stdClass();
        
        $mForumExists = $this->groupforum_check_forum_existence();

        if ($mForumExists !== true)
            return $this->error('forum', $mForumExists );

        $aConfig = array();
        $aConfig['type'] = $this->type;
        $aConfig['filter'] = $this->type == 'tag' ? $this->tagname : false;
        $aConfig['forum_id'] = $this->forumid;
        $aConfig['page'] = $this->page;
        $aConfig['per_page'] = $this->per_page;
        
        $aTopics = bp_forums_get_forum_topics($aConfig);
        if (is_null($aTopics))
            $this->error('forum', 7);
        foreach ($aTopics as $key=>$aTopic){
            $oReturn->topics[$key]->id = (int) $aTopic->topic_id;
            $oReturn->topics[$key]->title = $aTopic->topic_title;
            $oReturn->topics[$key]->slug = $aTopic->topic_slug;
            $oReturn->topics[$key]->poster = $aTopic->topic_poster;
            $oReturn->topics[$key]->post_count = $aTopic->topic_posts;
            
        }
        $oReturn->count = count($aTopics);
        return $oReturn;
    }
    
    /**
     * Returns an array containing all topics of a forum
     * @return Array Topics
     */
    public function sitewideforum_get_forum_topics(){
        /* Possible parameters:
         * int forumid: the forumid you are searching for (if not set, forumslug is searched; forumid or forumslug required)
         * String forumslug: the slug to search for (just used if forumid is not set; forumid or forumslug required)
         * boolean display_content: set this to true if you want the content to be displayed too (default false)
         */
        $this->initVars('forums');
        
        $oReturn = new stdClass();
        
        $mForumExists = $this->sitewideforum_check_forum_existence();

        if ($mForumExists !== true)
            return $this->error('forum', $mForumExists );
        global $wpdb;
        foreach ($this->forumid as $iId){
            $aTopics = $wpdb->get_results($wpdb->prepare(
                    "SELECT ID, post_parent, post_author, post_title, post_date, post_modified, post_content
                     FROM   $wpdb->posts
                     WHERE  post_type='topic'
                     AND post_parent='".$iId."'"
                    ));
            if (empty($aTopics)){
                $oReturn->forums[(int)$iId]->topics = "";
                continue;
            }
            foreach ($aTopics as $aTopic){
                $oReturn->forums[(int)$iId]->topics[(int)$aTopic->ID]['author'] = (int)$aTopic->post_author;
                $oReturn->forums[(int)$iId]->topics[(int)$aTopic->ID]['date'] = $aTopic->post_date;
                if ($this->display_content !== false)
                    $oReturn->forums[(int)$iId]->topics[(int)$aTopic->ID]['content'] = $aTopic->post_content;
                $oReturn->forums[(int)$iId]->topics[(int)$aTopic->ID]['last_changes'] = $aTopic->post_modified;
                $oReturn->forums[(int)$iId]->topics[(int)$aTopic->ID]['title'] = $aTopic->post_title;
                $oReturn->forums[(int)$iId]->topics[(int)$aTopic->ID]['parent'] = (int) $aTopic->post_parent;
            }
            $oReturn->forums[[(int)$iId]]->count = count($aTopics);
        }
        return $oReturn;
    }
    
    /**
     * Returns an object containing the topic with it's details
     * @return Object topic
     */
    public function groupforum_get_topic_details(){
        /* Possible parameters:
         * int topicid: the topicid you are searching for (if not set, topicslug is searched; topicid or topicslug required)
         * String topicslug: the slug to search for (just used if topicid is not set; topicid or topicslugs required)
         */
        $this->initVars('forums');
        
        $oReturn = new stdClass();
        
        $mTopicExists = $this->groupforum_check_topic_existence();
        if ($mTopicExists !== true)
            $this->error('forum', $mTopicExists);
        
        $oTopic = bp_forums_get_topic_details($this->topicid);
        
        $oReturn->topic->id = (int) $oTopic->topic_id;
        $oReturn->topic->title = $oTopic->topic_title;
        $oReturn->topic->slug = $oTopic->topic_slug;
        $oReturn->topic->poster[]->id = (int) $oTopic->topic_poster;
        $oReturn->topic->poster[]->name = $oTopic->topic_poster_name;
        $oReturn->topic->last_poster[]->id = (int) $oTopic->topic_last_poster;
        $oReturn->topic->last_poster[]->name = $oTopic->topic_last_poster_name;
        $oReturn->topic->start_time = $oTopic->topic_start_time;
        $oReturn->topic->forum_id = (int) $oTopic->forum_id;
        $oReturn->topic->topic_status = $oTopic->topic_status;
        $oReturn->topic->is_open = (int) $oTopic->topic_open === 1 ? true : false;
        $oReturn->topic->is_sticky = (int) $oTopic->topic_sticky === 1 ? true : false;
        $oReturn->topic->count_posts = (int) $oTopic->topic_posts;
        
        return $oReturn;
    }
    
    /**
     * Returns an arary containing the posts
     * @return Array posts
     */
    public function groupforum_get_topic_posts(){
        /* Possible parameters:
         * int topicid: the topicid you are searching for (if not set, topicslug is searched; topicid or topicslug required)
         * String topicslug: the slug to search for (just used if topicid is not set; topicid or topicslugs required)
         * int page: the page number you want to display (default 1)
         * int per_page: the number of results you want per page (default 15)
         * String order: desc for descending or asc for ascending (default asc)
         */
        $this->initVars('forums');
        
        $oReturn = new stdClass();
        
        $mTopicExists = $this->groupforum_check_topic_existence();
        if ($mTopicExists !== true)
            $this->error('forum', $mTopicExists);
        
        $aConfig = array();
        $aConfig['topic_id'] = $this->topicid;
        $aConfig['page'] = $this->page;
        $aConfig['per_page'] = $this->per_page;
        $aConfig['order'] = $this->order;
        $aPosts = bp_forums_get_topic_posts($aConfig);
        
        $oReturn->post = array();
        
        foreach ($aPosts as $key=>$oPost){
            $oReturn->post[$key]->id = new stdClass();
            $oReturn->post[$key]->id = (int) $oPost->post_id;
            $oReturn->post[$key]->topicid = (int) $oPost->topic_id;
            $oReturn->post[$key]->poster[]->id = (int) $oPost->poster_id;
            $oReturn->post[$key]->poster[]->username = $oPost->poster_login;
            $oReturn->post[$key]->poster[]->name = $oPost->poster_name;
            $oReturn->post[$key]->post_text = $oPost->post_text;
            $oReturn->post[$key]->post_time = $oPost->post_time;
            $oReturn->post[$key]->post_position = (int) $oPost->post_position;
        }
        $oReturn->count = count($aPosts);
        
        return $oReturn;
    }
    
    /**
     * Returns an array containing all replies to a topic
     * @return Array replies
     */
    public function sitewideforum_get_topic_replies(){
        /* Possible parameters:
         * int topicid: the topicid you are searching for (if not set, topicslug is searched; topicid or topicsslug required)
         * String topicslug: the slug to search for (just used if topicid is not set; topicid or topicslug required)
         * boolean display_content: set this to true if you want the content to be displayed too (default false)
         */
        $this->initVars('forums');
        
        $oReturn = new stdClass();
        
        $mForumExists = $this->sitewideforum_check_topic_existence();

        if ($mForumExists !== true)
            return $this->error('forum', $mForumExists );
        foreach ($this->topicid as $iId){
            global $wpdb;
            $aReplies = $wpdb->get_results($wpdb->prepare(
                    "SELECT ID, post_parent, post_author, post_title, post_date, post_modified, post_content
                     FROM   $wpdb->posts
                     WHERE  post_type='reply'
                     AND post_parent='".$iId."'"
                    ));
            
            if (empty($aReplies)){
                $oReturn->topics[$iId]->replies = "";
                $oReturn->topics[$iId]->count = 0;
                continue;
            }
            foreach ($aReplies as $oReply){
                $oReturn->topics[$iId]->replies[(int)$oReply->ID]['author'] = (int)$oReply->post_author;
                $oReturn->topics[$iId]->replies[(int)$oReply->ID]['date'] = $oReply->post_date;
                if ($this->display_content !== false)
                    $oReturn->topics[$iId]->replies[(int)$oReply->ID]['content'] = $oReply->post_content;
                $oReturn->topics[$iId]->replies[(int)$oReply->ID]['last_changes'] = $oReply->post_modified;
                $oReturn->topics[$iId]->replies[(int)$oReply->ID]['title'] = $oReply->post_title;
                $oReturn->topics[$iId]->replies[(int)$oReply->ID]['parent'] = (int) $oReply->post_parent;
            }
            $oReturn->topics[$iId]->count = count($aReplies);
        }
        
        return $oReturn;
    }    

    /**
     * Method to handle calls for the library
     * @param String $sName name of the static method to call
     * @param Array $aArguments arguments for the method
     * @return return value of static library function, otherwise null
     */
    public function __call($sName, $aArguments) {
        //tbd check if module active
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