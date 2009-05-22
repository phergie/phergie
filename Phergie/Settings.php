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
        'Join',
        'Part',
        'Ping',
        'Pong',
        'Prioritize',
        'Quit'
        'Php',
        'Daddy',
        'TerryChay',
    ),

    'plugins.autoload' => true,

    'altnick.nicks' => array(
        'Phergie2_'
    ),

    'autojoin.channels' => '#phergie',

    'ping.event' => 600,

    'ping.ping' => 10,

    'command.prefix' => 'Phergie2: '
    
    'daddy.curses' => true,

);
