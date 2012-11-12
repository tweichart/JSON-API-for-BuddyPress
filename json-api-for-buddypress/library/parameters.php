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
    'profile' => array(
        'string' => array(
            'username' => false
        )
    ),
    'message' => array(
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
            'groupid' => false
        )
    )
);