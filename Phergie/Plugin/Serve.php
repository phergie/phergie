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
 * @package   Phergie_Plugin_Serve
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Serve
 */

/**
 * Processes requests to serve a user something from a database.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Serve
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Serve
 * @uses     extension pdo
 * @uses     extension pdo_sqlite
 */
class Phergie_Plugin_Serve extends Phergie_Plugin_Abstract
{
    /**
     * Checks for dependencies.
     *
     * @return void
     */
    public function onLoad()
    {
        if (!extension_loaded('PDO') || !extension_loaded('pdo_sqlite')) {
            $this->fail('PDO and pdo_sqlite extensions must be installed');
        }
    }

    /**
     * Retrieves a random item from the database table.
     *
     * @param string $database Path to the SQLite database file
     * @param string $table    Name of the database table
     * @param array  $request  Parsed request
     *
     * @return object Retrieved item
     */
    protected function getItem($database, $table, array $request)
    {
        $db = new PDO('sqlite:' . $database);
        if (!empty($request['suggestion'])) {
            $query = 'SELECT * FROM ' . $table
                . ' WHERE name LIKE ? ORDER BY RANDOM() LIMIT 1';
            $stmt = $db->prepare($query);
            $stmt->execute(array('%' . $request['suggestion'] . '%'));
            $item = $stmt->fetchObject();
            if (!$item) {
                $item = new stdClass;
                $item->name = $request['suggestion'];
                $item->link = null;
            }
        } else {
            $query = 'SELECT * FROM ' . $table . ' ORDER BY RANDOM() LIMIT 1';
            $stmt = $db->query($query);
            $item = $stmt->fetchObject();
        }
        return $item;
    }

    /**
     * Processes a request to serve a user something.
     *
     * @param string  $database Path to the SQLite database file
     * @param string  $table    Name of the database table
     * @param string  $format   Format of the response where %target%,
     *        %item%, %article%', and %link will be replaced with their
     *        respective data
     * @param string  $request  Request string including the target and an
     *        optional suggestion of the item to fetch
     * @param boolean $censor   TRUE to integrate with the Censor plugin,
     *        defaults to FALSE
     *
     * @return boolean TRUE if the request was processed successfully, FALSE
     *         otherwise
     */
    public function serve($database, $table, $format, $request, $censor = false)
    {
        // Parse the request
        $result = preg_match(
            '/(?P<target>[^\s]+)(\s+an?\s+)?(?P<suggestion>.*)?/',
            $request,
            $match
        );

        if (!$result) {
            return false;
        }

        // Resolve the target
        $target = $match['target'];
        if ($target == 'me') {
            $target = $this->event->getNick();
        }

        // Process the request
        $item = $this->getItem($database, $table, $match);

        // Reprocess the request for censorship if required
        if ($this->plugins->hasPlugin('Censor')) {
            $plugin = $this->plugins->getPlugin('Censor');
            $attempts = 0;
            while ($censor && $attempts < 3) {
                $clean = $plugin->cleanString($item->name);
                if ($item->name != $clean) {
                    $attempts++;
                    $item = $this->getItem($database, $table, $match);
                } else {
                    $censor = false;
                }
            }
            if ($censor && $attempts == 3) {
                $this->doAction($this->event->getSource(), 'shrugs.');
            }
        }

        // Derive the proper article for the item
        if (preg_match('/^[aeiou]/i', $item->name)) {
            $article = 'an';
        } else {
            $article = 'a';
        }

        // Format the message
        $replacements = array(
            'target' => $target,
            'item' => $item->name,
            'link' => $item->link,
            'article' => $article
        );

        $msg = $format;
        foreach ($replacements as $placeholder => $value) {
            $msg = str_replace(
                '%' . $placeholder . '%',
                $value,
                $msg
            );
        }

        // Send the message
        $this->doAction($this->event->getSource(), $msg);
    }
}
