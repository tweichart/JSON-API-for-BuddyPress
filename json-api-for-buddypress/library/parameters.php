<?php

$aParams = array (
    'activity' => array (
        'string' => array (
            'sort' => 'DESC',
            'comments' => false,
            'component' => false,
            'type' => false
        ),
        'int' => array (
            'pages' => 1,
            'offset' => 10,
            'limit' => false,
            'userid' => false,
            'itemid' => false,
            'secondaryitemid' => false
        )
    ),
    'profile' => array (
        'string' => array (
            'username' => false
        )
    ),
    'message' => array (
        'string' => array (
            'box' => 'inbox'
        ),
        'int' => array (
            'per_page' => 10
        ),
        'boolean' => array (
            'limit' => false
        )
    ),
    'friend' => array(
        'string' => array(
            'username' => 0
        ),
        'int' => array(
            'per_page' => 10,
            'pages' => 1,
            'limit' => 0
        ),
        'boolean' => array(
            'only_requests' => false
        )
    )
);