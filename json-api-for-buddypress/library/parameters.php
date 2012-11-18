<?php

$aParams = array(
    'activity' => array(
        'string' => array(
            'sort' => 'DESC',
            'comments' => false,
            'component' => false,
            'type' => false
        ),
        'int' => array(
            'pages' => 1,
            'offset' => 10,
            'limit' => false,
            'userid' => false,
            'itemid' => false,
            'secondaryitemid' => false
        )
    ),
    'xprofile' => array(
        'string' => array(
            'username' => false
        )
    ),
    'messages' => array(
        'string' => array(
            'box' => 'inbox'
        ),
        'int' => array(
            'per_page' => 10
        ),
        'boolean' => array(
            'limit' => false
        )
    ),
    'friends' => array(
        'string' => array(
            'username' => false,
            'friendname' => false
        )
    ),
    'groups' => array(
        'string' => array(
            'username' => false,
            'type' => false,
            'groupname' => false,
            'groupslug' => false
        ),
        'boolean' => array(
            'show_hidden' => false
        ),
        'int' => array(
            'per_page' => '',
            'page' => 1,
            'groupid' => false,
            'limit' => false
        )
    ),
    'forums' => array(
        'string' => array(
            'groupslug' => false,
            'forumslug' => false,
            'type' => 'newest',
            'tagname' => false,
            'topicslug' => false,
            'order' => 'asc'
        ),
        'int' => array(
            'groupid' => false,
            'forumid' => false,
            'page' => 1,
            'per_page' => 15,
            'topicid' => 0,
            'parentid' => false
        ),
        'boolean' => array(
            'display_content' => false,
            'detailed' => false
        )
    ),
    'settings' => array(
        'string' => array(
            'username' => false
        )
    ),
    'notifications' => array()
);