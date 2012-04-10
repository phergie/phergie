<?php
/**
 * Phergie
 *
 * PHP version 5
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * http://phergie.org/license
 *
 * @category  Phergie
 * @package   Phergie
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2011 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie
 */

/**
 * Phergie Log Viewer ... Currently designed as a single PHP file in order to make
 * it easy to 'install' this.  Just drop the index.php (or whatever name you wish
 * to rename it to) wherever you wish, and it will simply work.  Sure, it would be
 * nice to structure some of this stuff into various include files/etc.  But right
 * now this is simple enough of a quick log viewer, that it's just one file.
 *
 */

/********** SETUP **********/

// (Change any of these if/as needed for your setup)
ini_set('default_charset', 'UTF-8');
date_default_timezone_set('UTC');
$log = "/PATH/AND/FILENAME/TO/YOUR/LOGFILE/PLEASE.db";


/********** PREPARATION **********/

$db = new PDO('sqlite:' . $log);
if (!is_object($db)) {
    // Failure, can't access Phergie Log.
    // Bail with an error message, not pretty, but works:
    echo "ERROR: Cannot access Phergie Log File, "
        . "please check the configuration & access privileges";
    exit();
}


/********** DETECTION **********/

// Determine the mode of the application and call the appropriate handler function
$mode = empty($_GET['m']) ? '' : $_GET['m'];
switch ($mode) {
case 'channel':
    show_days($db);
    break;
case 'day':
    show_log($db);
    break;
default:
    show_channels($db);
}

// Exit not really needed here,
// but reminds us that everything below is support functions:
exit();

/********** MODES **********/

/**
 * show_channels
 *
 * Provide a list of all channel's that we are logging information for:
 *
 * @param PDO $db A PDO object referring to the database
 *
 * @return void
 * @author Eli White <eli@eliw.com>
 **/
function show_channels(PDO $db)
{
    // Begin the HTML page:
    template_header('Channels');
    echo "\nChannels:\n<ul>\n";

    // Loop through the database reading in each channel,
    // and echoing out a <li> for it.
    // only grab actual channels that start with # ... also pre-lowercase everything.
    // this allows us to 'deal' with variable caps in how the channels were logged.
    $channels = $db->query(
        "select distinct lower(chan) as c
        from logs
        where chan like '#%'"
    );
    foreach ($channels as $row) {
        $html = utf8specialchars($row['c']);
        $url = urlencode($row['c']);
        echo "<li><a href=\"?m=channel&w={$url}\">{$html}</a></li>\n";
    }

    // Finish off the page:
    echo "\n</ul>\n";
    template_footer();
}

/**
 * show_days
 *
 * Create a calendar view of all days available for this particular channel
 *
 * NOTE: May get unwieldy if large log files.  Perhaps consider in the future
 *  making a paginated version of this?  by year?  Or a separate 'which year' page
 *  before this?  Not to worry about now.
 *
 * @param PDO $db A PDO object referring to the database
 *
 * @return void
 * @author Eli White <eli@eliw.com>
 **/
function show_days(PDO $db)
{
    $channel = $_GET['w'];
    $url = urlencode($channel);

    // Begin the HTML page:
    template_header('Daily Logs for Channel: ' . utf8specialchars($channel));
    echo "\n<ul>\n";

    // Query the database to discover all days that are available for this channel:
    $data = array();
    $prepared = $db->prepare(
        "select distinct date(tstamp) as day
        from logs
        where lower(chan) = :chan"
    );
    $prepared->execute(array(':chan' => $channel));
    foreach ($prepared as $row) {
        list($y, $m, $d) = explode('-', $row['day']);
        $data[$y][$m][$d] = "{$y}-{$m}-{$d}";
    }

    // For now, just loop over them all and provide a list:
    ksort($data);
    foreach ($data as $year => $months) {
        ksort($months);
        foreach ($months as $month => $days) {
            // Figure out a few facts about this month:
            $stamp = mktime(0, 0, 0, $month, 1, $year);
            $first_weekday = idate('w', $stamp);
            $days_in_month = idate('t', $stamp);
            $name = date('F', $stamp);

            // We have a month ... start a new table:
            echo <<<EOTABLE
<div class="month">
  <table>
    <caption>{$name} {$year}</caption>
    <tr><th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th>
        <th>Thu</th><th>Fri</th><th>Sat</th>
    </tr>
EOTABLE;
            // Now we need to start looping through the days in this month:
            echo '<tr>';
            $rowmod = 0;
            // Loop through all day entries, no matter how many blanks we need:
            for ($d = (-$first_weekday + 1); $d < $days_in_month + 1; $d++) {
                if (!($rowmod++ % 7)) {
                    // Stop/start a new row:
                    echo '</tr><tr>';
                }
                echo '<td>';
                // If this day is pre or post actual month days, make it blank:
                if (($d < 1) || ($d > $days_in_month)) {
                    echo '&nbsp;';
                } elseif (isset($days[$d])) {
                    // Make a link to the day's log:
                    echo "<a href=\"?m=day&w={$url}&d={$days[$d]}\">{$d}</a>";
                } else {
                    // Just a dead number:
                    echo $d;
                }
                echo '</td>';
            }
            // Finish off any blanks needed for a complete table row:
            while ($rowmod++ % 7) {
                echo '<td>&nbsp;</td>';
            }
            echo "</tr></table></div>\n";
        }
    }

    // Finish off the page:
    echo "\n</ul>\n";
    template_footer();
}

/**
 * show_log
 *
 * Actually show the log for this specific day
 *
 * @param PDO $db A PDO object referring to the database
 *
 * @return void
 * @author Eli White <eli@eliw.com>
 **/
function show_log(PDO $db)
{
    $channel = $_GET['w'];
    $day = $_GET['d'];
    $parts = explode('-', $day);
    $formatted_date = "{$parts[0]}-{$parts[1]}-{$parts[2]}";

    // Begin the HTML page:
    template_header(
        'Date: ' . utf8specialchars($formatted_date) .
        ' - Channel: ' . utf8specialchars($channel)
    );

    // Query the database to get all log lines for this date:
    $prepared = $db->prepare(
        "select time(tstamp) as t, type, nick, message
        from logs
        where lower(chan) = :chan and date(tstamp) = :day
        order by tstamp asc"
    );
    $prepared->execute(
        array(
            ':chan' => $channel,
            ':day' => $day,
        )
    );

    // Loop through each line,
    foreach ($prepared as $row) {
        // Prepare some basic details for output:
        $color = nick_color($row['nick']);
        $time = utf8specialchars($row['t']);
        $msg = utf8specialchars($row['message']);
        $nick = utf8specialchars($row['nick']);
        $type = false;

        // Now change the format of the line based upon the type:
        switch ($row['type']) {
        case 4: // PRIVMSG (A Regular Message)
            echo "[$time] <span style=\"color:#{$color};\">"
                . "&lt;{$nick}&gt;</span> {$msg}<br />\n";
            break;
        case 5: // ACTION (emote)
            echo "[$time] <span style=\"color:#{$color};\">"
                . "*{$nick} {$msg}</span><br />\n";
            break;
        case 1: // JOIN
            echo "[$time] -> {$nick} joined the room.<br />\n";
            break;
        case 2: // PART (leaves channel)
            echo "[$time] -> {$nick} left the room: {$msg}<br />\n";
            break;
        case 3: // QUIT (quits the server)
            echo "[$time] -> {$nick} left the server: {$msg}<br />\n";
            break;
        case 6: // NICK (changes their nickname)
            echo "[$time] -> {$nick} is now known as: {$msg}<br />\n";
            break;
        case 7: // KICK (booted)
            echo "[$time] -> {$nick} boots {$msg} from the room.<br />\n";
            break;
        case 8: // MODE (changed their mode)
            $type = 'MODE';
        case 9: // TOPIC (changed the topic)
            $type = $type ? $type : 'TOPIC';
            echo "[$time] -> {$nick}: :{$type}: {$msg}<br />\n";
        }
    }

    // Finish up the page:
    template_footer();
}

/**
 * nick_color
 *
 * Uses a silly little algorithm to pick a consistent but unique(ish) color for
 *  any given username.  NOTE: Augment this in the future to make it not generate
 *  'close to white' ones, also maybe to ensure uniqueness?  (Not allow two to have
 *  colors that are close to each other?)
 *
 * @param String $user TODO username to operate on
 *
 * @return string A CSS valid hex color string
 * @author Eli White <eli@eliw.com>
 **/
function nick_color($user)
{
    static $colors = array();

    if (!isset($colors[$user])) {
        $colors[$user] = substr(md5($user), 0, 6);
    }

    return $colors[$user];
}

/**
 * utf8specialchars
 *
 * Just a quick wrapper around htmlspecialchars
 *
 * @param string $string The UTF-8 string to escape
 *
 * @return string An escaped and ready for HTML use string
 * @author Eli White <eli@eliw.com>
 **/
function utf8specialchars($string)
{
    return htmlspecialchars($string, ENT_COMPAT, 'UTF-8');
}


/********** TEMPLATES **********/

/**
 * template_header
 *
 * Echo out the header for each HTML page
 *
 * @param String $title The title to be used for this page.
 *
 * @return void
 * @author Eli White <eli@eliw.com>
 **/
function template_header($title)
{
    $css = template_css();
    echo <<<EOHTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Phergie LogViewer - {$title}</title>
    <style type="text/css" media="all">{$css}</style>
  </head>
  <body>
    <h2>Phergie LogViewer - {$title}</h2>
EOHTML;
}

/**
 * template_footer
 *
 * Echo out the bottom of each HTML page
 *
 * @return void
 * @author Eli White <eli@eliw.com>
 **/
function template_footer()
{
    echo <<<EOHTML
  </body>
</html>
EOHTML;
}

/**
 * template_css
 *
 * Generate the CSS used by these HTML pages & return it.
 *
 * @return string The CSS in question:
 * @author Eli White <eli@eliw.com>
 **/
function template_css()
{
    return <<<EOCSS
    div.month {
        float: left;
        height: 15em;
    }

    div.month table {
        border-collapse: collapse;
        border: 2px solid black;
        margin-right: 2em;
    }

    div.month td, div.month th {
        text-align: center;
        vertical-align: bottom;
        border: 1px solid gray;
        width: 2em;
        height: 1.7em;
        padding: 1px;
        margin: 0px;
    }

    div.month th {
        text-decoration: bold;
        border: 2px solid black;
    }

    div.month a {
        text-decoration: none;
    }

    a:visited, a:link {
        color: blue;
    }

    a:active, a:hover {
        color: red;
    }
EOCSS;
}
