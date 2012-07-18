<?php
/*
Controller name: Buddypress Read
Controller description: Buddypress controller for reading actions
*/

class JSON_API_BuddypressRead_Controller{    
        /**
	 *Returns an object with all activities
	 * @global Object $json_api
	 * @return Array Activities
	 */
	public function get_activities() {
            
                /* Possible parameters:
                 * int pages: number of pages to display (default unset)
                 * int offset: number of entries per page (default 10 if pages is set, otherwise unset)
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
		
		if (!bp_has_activities ())
			return $this->error('activity');
                
                if (!is_null($json_api->query->pages)){
                    $aParams ['max'] = true;
                    $aParams ['per_page'] = is_null($json_api->query->offset) ? 10: $json_api->query->offset;
                    $iPages = $json_api->query->pages;
                }
                
                $aParams ['display_comments'] = is_null($json_api->query->comments) ? false: $json_api->query->comments;
                $aParams ['sort'] = is_null($json_api->query->sort) ? 'DESC' : 'ASC';
                
                $aParams ['filter'] ['user_id'] = is_null($json_api->query->userid) ? false : $json_api->query->userid;
                $aParams ['filter'] ['object'] = is_null($json_api->query->component) ? false : $json_api->query->component;
                $aParams ['filter'] ['action'] = is_null($json_api->query->type) ? false : $json_api->query->type;
                $aParams ['filter'] ['primary_id'] = is_null($json_api->query->itemid) ? false : $json_api->query->itemid;
                $aParams ['filter'] ['secondary_id'] = is_null($json_api->query->secondaryitemid) ? false : $json_api->query->secondaryitemid;
                
                if (is_null($json_api->query->pages)){
                        $aParams ['page'] = 1;
						$aTempActivities = bp_activity_get($aParams);
                        if (!empty($aTempActivities['activities'])){
                            $oReturn->activities [0]  = $aTempActivities['activities'];
                            $oReturn->pages = 1;
                        }
                        else{
                            return $this->error('activity');
                        }
                        return $oReturn;
                }

		for ($i = 1; $i <= $iPages; $i++){
			$aParams ['page'] = $i;
			$aTempActivities = bp_activity_get($aParams);
			if (empty($aTempActivities['activities'])){
                            if ($i == 1)
                                return $this->error ('activity');
                            else
				break;
                        }
			else
				$oReturn->activities [$i]  = $aTempActivities['activities'];
		}
		
		$oReturn->pages = $i - 1;
		return $oReturn;
	}
        
        private function error($sModule){
            $oReturn = new stdClass();
            switch ($sModule){
                case "activity":
                    $oReturn->status = "error";
                    $oReturn->msg = __('No Activities found.');
                    return $oReturn;
            }
        }
}
?>
