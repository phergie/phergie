<?php

return array(

    'connections' => array(

        array(
            'hostname' => 'irc.freenode.net',
            'username' => 'Elazar',
            'realname' => 'Matthew Turland',
            'nick' => 'Phergie2'
        )

    ),

    'plugins' => array(
        'AltNick',
        'AutoJoin',
        'Invisible',
        'Ping',
        'Pong',
        'Prioritize',
        'Quit'
    ),

    'plugins.autoload' => true,

    'altnick.nicks' => array('Phergie2_'),

    'autojoin.channels' => '#phergie',

    'ping.event' => 600,

    'ping.ping' => 10

);
