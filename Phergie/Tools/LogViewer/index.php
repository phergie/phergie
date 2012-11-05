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
 * @copyright 2008-2012 Phergie Development Team (http://phergie.org)
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
$settings_file = '/Users/eli/Projects/phergie-eliw/Settings.php';


/********** PREPARATION **********/

// Attempt to read the phergie settings.
$dsn = $user = $pass = $table = NULL;
if (is_readable($settings_file)) {
    $settings = include $settings_file;
    if (is_array($settings)) {
        $dsn = isset($settings['logging.dsn']) ? $settings['logging.dsn'] : NULL;
        $user = isset($settings['logging.user']) ? $settings['logging.user'] : NULL;
        $pass = isset($settings['logging.pass']) ? $settings['logging.pass'] : NULL;
        $table = isset($settings['logging.table']) ? $settings['logging.table'] : NULL;
    }
}

// Fail if we don't have a $dsn or $table
if (!$dsn || !$table) {
    exit("ERROR: No DSN or Table configuration can be read.");
}

$db = new PDO($dsn, $user, $pass);
if (!is_object($db)) {
    // Failure, can't access Phergie Log.
    exit("ERROR: Cannot access log, please check the configuration & access privileges");
}
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ); 


/********** DETECTION **********/

// Determine the mode of the application and call the appropriate handler function
$mode = empty($_GET['m']) ? '' : $_GET['m'];
switch ($mode) {
case 'channel':
    show_days($db, $table);
    break;
case 'day':
    show_log($db, $table);
    break;
default:
    show_channels($db, $table);
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
 * @param string $table The name of the logging table 
 * @return void
 * @author Eli White <eli@eliw.com>
 **/
function show_channels(PDO $db, $table)
{
    // Begin the HTML page:
    template_header('Channels');
    echo "\nChannels:\n<ul>\n";

    // Loop through the database reading in each server / channel
    $channels = $db->query("SELECT DISTINCT `host`, `channel` FROM {$table}");
    foreach ($channels as $c) {
        $html = utf8specialchars("{$c->channel} ({$c->host})");
        $h = urlencode($c->host);
        $c = urlencode($c->channel);
        echo "<li><a href=\"?m=channel&c={$c}&h={$h}\">{$html}</a></li>\n";
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
 * @param string $table The name of the logging table 
 * @return void
 * @author Eli White <eli@eliw.com>
 **/
function show_days(PDO $db, $table)
{
    $channel = $_GET['c'];
    $host = $_GET['h'];
    $url = 'c=' . urlencode($channel) . '&h=' . urlencode($host);

    // Begin the HTML page:
    template_header('Daily Logs for Channel: ' . utf8specialchars($channel) .
        ' (' . utf8specialchars($host) . ')');
    echo "\n<ul>\n";

    // Query the database to discover all days that are available for this channel:
    $data = array();
    $prepared = $db->prepare(
        "SELECT DISTINCT date(`created_on`) AS day 
         FROM {$table}
         WHERE `host` = ? AND `channel` = ?"
    );
    $prepared->execute(array($host, $channel));
    foreach ($prepared as $result) {
        list($y, $m, $d) = explode('-', $result->day);
        $data[(int)$y][(int)$m][(int)$d] = "{$y}-{$m}-{$d}";
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
                    echo "<a href=\"?m=day&{$url}&d={$days[$d]}\">{$d}</a>";
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
 * @param string $table The name of the logging table 
 * @return void
 * @author Eli White <eli@eliw.com>
 **/
function show_log(PDO $db, $table)
{
    $channel = $_GET['c'];
    $host = $_GET['h'];
    $day = $_GET['d'];
    $parts = explode('-', $day);
    $formatted_date = "{$parts[0]}-{$parts[1]}-{$parts[2]}";

    // Begin the HTML page:
    template_header(
        'Date: ' . utf8specialchars($formatted_date) .
        ' - Channel: ' . utf8specialchars($channel) . 
        ' (' . utf8specialchars($host) . ')'
    );

    // Query the database to get all log lines for this date:
    $prepared = $db->prepare(
        "SELECT time(`created_on`) AS t, type, nick, message
         FROM {$table}
         WHERE `host` = ? AND `channel` = ? AND date(`created_on`) = ?
         ORDER by `created_on` asc"
    );
    $prepared->execute(array($host, $channel, $day));

    // Loop through each line,
    foreach ($prepared as $result) {
        // Prepare some basic details for output:
        $color = nick_color($result->nick);
        $time = utf8specialchars($result->t);
        $msg = utf8specialchars($result->message);
        $nick = utf8specialchars($result->nick);
        $type = false;

        // Now change the format of the line based upon the type:
        switch ($result->type) {
        case 'privmsg': // PRIVMSG (A Regular Message)
            echo "[$time] <span style=\"color:#{$color};\">"
                . "&lt;{$nick}&gt;</span> {$msg}<br />\n";
            break;
        case 'action': // ACTION (emote)
            echo "[$time] <span style=\"color:#{$color};\">"
                . "*{$nick} {$msg}</span><br />\n";
            break;
        case 'join': // JOIN
            echo "[$time] -> {$nick} joined the room.<br />\n";
            break;
        case 'part': // PART (leaves channel)
            echo "[$time] -> {$nick} left the room: {$msg}<br />\n";
            break;
        /* Not currently logged 
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
        */
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
 * @param string $table The name of the logging table 
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
<html>
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
