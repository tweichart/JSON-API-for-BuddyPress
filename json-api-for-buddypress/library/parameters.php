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
    )
);