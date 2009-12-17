<?php

return array(

    'connections' => array(
        array(
            'host' => 'irc.freenode.net',
            'port' => 6667,
            'username' => 'Elazar',
            'realname' => 'Matthew Turland',
            'nick' => 'Phergie2',
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
        'Phergie2_'
    ),

    'autojoin.channels' => '#phergie',

    'ping.event' => 600,

    'ping.ping' => 10,

    'command.prefix' => 'Phergie2: '

);
