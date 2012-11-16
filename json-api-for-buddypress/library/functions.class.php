<?php

class JSON_API_FOR_BUDDYPRESS_FUNCTION extends JSON_API_BuddypressRead_Controller {

    protected static $sVars = array();

    /**
     * Load the Parameters defined in parameters.php
     * @param String $sModule the module to load
     * @throws Exception if parameters for module aren't defined
     */
    protected static function initVars($sModule) {
        require_once (JSON_API_FOR_BUDDYPRESS_HOME . '/library/parameters.php');

        if (!isset($aParams [$sModule]))
            throw new Exception("Parameters for module not defined.");

        foreach ($aParams [$sModule] as $sType => $aParameters) {
            foreach ($aParameters as $sValName => $sVal) {
                self::$sVars [$sValName] = self::getVar($sValName, $sVal, $sType);
            }
        }
    }

    private static function getVar($sValName, $sVal, $sType) {
        global $json_api;
        $mReturnVal = is_null($json_api->query->$sValName) ? $sVal : $json_api->query->$sValName;
        return self::sanitize($mReturnVal, $sType);
    }

    /**
     * Method to sanitize the values given
     * @param mixed $mValue Value to sanitize
     * @param String $sType type of the Value given by parameters array
     * @return mixed sanitized value
     */
    private static function sanitize($mValue, $sType) {
        switch ($sType) {
            case "int":
                if ($mValue !== false)
                    $mValue = (int) $mValue;
                break;
            case "boolean":
                $mValue = (boolean) $mValue;
            case "string":
            default:
                switch (gettype($mValue)) {
                    case 'string':
                        $mValue = strip_tags($mValue);
                        break;
                    case 'boolean':
                    default:
                        break;
                }
                break;
        }
        return $mValue;
    }

    protected static function get_group_from_params() {
        if (self::$sVars ['groupid'] === false && self::$sVars ['groupslug'] === false)
            return 2;
        $oGroup = groups_get_group(array('group_id' => self::$sVars ['groupid']));
        if (is_null($oGroup->id)){
            self::$sVars ['groupid'] = groups_get_id(sanitize_title(self::$sVars ['groupslug']));
            if (self::$sVars ['groupid'] === 0)
                return 3;
        }
        else{
            self::$sVars['groupslug'] = $oGroup->slug;
        }
        return true;
    }
    
    protected static function groupforum_check_forum_existence(){
        if (self::$sVars['forumid'] === false && self::$sVars['forumslug'] === false)
            return 4;
        $oForum = bp_forums_get_forum(self::$sVars['forumid'] === false );
        if (is_null($oForum) || $oForum === false){
            $iForumId = bb_get_id_from_slug('forum', sanitize_title(self::$sVars['forumslug']));
            if ($iForumId === 0)
                    return 5;
            else{
                self::$sVars['forumid'] = $iForumId;
            }
        }
        else{
            self::$sVars['forumid'] = $oGroup->id;
            self::$sVars['forumslug'] = $oGroup->slug;
        }
        return true;
    }
    protected static function groupforum_check_topic_existence(){
        if (self::$sVars['topicid'] === false && self::$sVars['topicslug'] === false)
            return 6;
        $oTopic = bp_forums_get_topic_details(self::$sVars['topicid']);
        if (is_null($oTopic) || (int) $oTopic->topic_id != self::$sVars['topicid']){
            $iTopicId = bb_get_id_from_slug('topic', sanitize_title(self::$sVars['topicslug']));
            if ($iTopicId === 0)
                return 8;
            else
                self::$sVars['topicid'] = $iTopicId;
        }
        return true;
    }

    /**
     * Returns a String containing an error message
     * @param String $sModule Modules name
     * @param type $iCode Errorcode
     */
    protected static function error($sModule, $iCode = "") {
        $oReturn = new stdClass();
        $oReturn->status = "error";
        switch ($sModule) {
            case "activity":
                $oReturn->msg = __('No Activities found.');
                break;
            case "profile":
                switch ($iCode) {
                    case 0:
                        $oReturn->msg = __('No Profile found.');
                        break;
                    case 1:
                        $oReturn->msg = __('Username not found.');
                        break;
                }
                break;
            case "message":
                $oReturn->msg = __('No messages found.');
                break;
            case "notification":
                $oReturn->msg = __('No notifications found.');
                break;
            case "friend":
                switch ($iCode) {
                    case 0:
                        $oReturn->msg = __('Username not found.');
                        break;
                    case 1:
                        $oReturn->msg = __('No friends found.');
                        break;
                    case 2:
                        $oReturn->msg = __('No friendship requests found.');
                        break;
                    case 3:
                        $oReturn->msg = __('Friendname not found.');
                        break;
                }
                break;
            case "group":
                switch ($iCode) {
                    case 0:
                        $oReturn->msg = __('No groups found.');
                        break;
                    case 1:
                        $oReturn->msg = __('Username not found.');
                        break;
                    case 2:
                        $oReturn->msg = __('Neither groupid nor groupslug are set.');
                        break;
                    case 3:
                        $oReturn->msg = __('Group not found.');
                        break;
                    case 4:
                        $oReturn->msg = __('No Members in Group');
                        break;
                }
                break;
            case "forum":
                switch ($iCode) {
                    case 0:
                        $oReturn->msg = __('Forums are disabled for this group.');
                        break;
                    case 1:
                        $oReturn->msg = __('No forum assigned to this group.');
                        break;
                    case 2:
                        $oReturn->msg = __('Neither groupid nor groupslug are set.');
                        break;
                    case 3:
                        $oReturn->msg = __('Group not found.');
                        break;
                    case 4:
                        $oReturn->msg = __('Neither forumid nor forumslug are set.');
                        break;
                    case 5:
                        $oReturn->msg = __('Forum not found.');
                        break;
                    case 6:
                        $oReturn->msg = __('Neither topicid nor topicslug are set.');
                        break;
                    case 7:
                        $oReturn->msg = __('No topics in this forum.');
                        break;
                    case 8:
                        $oReturn->msg = __('No topics found.');
                        break;
                }
                break;
            default:
                $oReturn->msg = __('An undefined error occured.');
        }
        return $oReturn;
    }

}