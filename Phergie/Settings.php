<?php

return array(

    'connections' => array(
        array(
            'host' => 'irc.freenode.net',
            'port' => 6667,
            'username' => 'brewbot2',
            'realname' => 'brewbot2',
            'nick' => 'brewbot2',
            'ssl' => false
        )
    ),

    'plugins' => array(
        'AltNick',
        'AutoJoin',
        'Invisible',
        'Join',
        'Part',
        'Ping',
        'Pong',
        'Prioritize',
        'Quit'
    ),

    'console' => true,

    'plugins.autoload' => true,

    'altnick.nicks' => array(
        'brewbot2_'
    ),

    'autojoin.channels' => '#seantest',

    'ping.event' => 600,

    'ping.ping' => 10,

    'command.prefix' => '!'

);
