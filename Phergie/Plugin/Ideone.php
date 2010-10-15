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
 * @package   Phergie_Plugin_Ideone
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Ideone
 */

/**
 * Interfaces with ideone.com to execute code and return the result.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Ideone
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Ideone
 * @uses     extension soap
 * @uses     Phergie_Plugin_Command pear.phergie.org
 */
class Phergie_Plugin_Ideone extends Phergie_Plugin_Abstract
{
    /**
     * Checks for dependencies.
     *
     * @return void
     */
    public function onLoad()
    {
        if (!extension_loaded('soap')) {
            $this->fail('soap extension is required');
        }

        $this->plugins->getPlugin('Command');
    }

    /**
     * Checks a service response for an error, sends a notice to the event
     * source if an error has occurred, and returns whether an error was found.
     *
     * @param array $result Associative array representing the service response
     *
     * @return boolean TRUE if an error is found, FALSE otherwise
     */
    protected function isError($result)
    {
        if ($result['error'] != 'OK') {
            $noticemsg = 'ideone error: ' . $result['error'];
            $this->doNotice($this->event->getNick(), $noticemsg);
            return true;
        }
        return false;
    }

    /**
     * Executes a source code sequence in a specified language and returns
     * the result.
     *
     * @param string $language Programming language the source code is in
     * @param string $code     Source code to execute
     *
     * @return void
     */
    public function onCommandIdeone($language, $code)
    {
        $source = $this->event->getSource();
        $nick = $this->event->getNick();

        // Get authentication credentials
        $user = $this->getConfig('ideone.user', 'test');
        $pass = $this->getConfig('ideone.pass', 'test');

        // Normalize the command parameters
        $language = strtolower($language);

        // Massage PHP code to allow for convenient shorthand
        if ($language == 'php') {
            if (!preg_match('/^<\?(?:php)?/', $code)) {
                $code = '<?php ' . $code;
            }
            switch (substr($code, -1)) {
                case '}':
                case ';':
                    break;
                default:
                    $code .= ';';
                    break;
            }
        }

        // Identify the language to use
        $client = new SoapClient('http://ideone.com/api/1/service.wsdl');
        $response = $client->getLanguages($user, $pass);
        if ($this->isError($response)) {
            return;
        }
        $languageLength = strlen($language);
        foreach ($response['languages'] as $languageId => $languageName) {
            if (strncasecmp($language, $languageName, $languageLength) == 0) {
                break;
            }
        }

        // Send the paste data
        $response = $client->createSubmission(
            $user,
            $pass,
            $code,
            $languageId,
            null, // string input - data from stdin
            true, // boolean run - TRUE to execute the code
            false // boolean private - FALSE to make the paste public
        );
        if ($this->isError($response)) {
            return;
        }
        $link = $response['link'];

        // Wait until the paste data is processed or the service fails
        $attempts = $this->getConfig('ideone.attempts', 10);
        foreach (range(1, $attempts) as $attempt) {
            $response = $client->getSubmissionStatus($user, $pass, $link);
            if ($this->isError($response)) {
                return;
            }
            if ($response['status'] == 0) {
                $result = $response['result'];
                break;
            } else {
                $result = null;
                sleep(1);
            }
        }
        if ($result == null) {
            $this->doNotice($nick, 'ideone error: Timed out');
            return;
        }
        if ($result != 15) {
            $this->doNotice($nick, 'ideone error: Status code ' . $result);
            return;
        }

        // Get details for the created paste
        $response = $client->getSubmissionDetails(
            $user,
            $pass,
            $link,
            false, // boolean withSource - FALSE to not return the source code
            false, // boolean withInput - FALSE to not return stdin data
            true,  // boolean withOutput - TRUE to include output
            true,  // boolean withStderr - TRUE to return stderr data
            false  // boolean withCmpinfo - TRUE to return compilation info
        );
        if ($this->isError($response)) {
            return;
        }

        // Replace the output if it exceeds a specified maximum length
        $outputLimit = $this->getConfig('ideone.output_limit', 100);
        var_dump($response);
        if ($outputLimit && strlen($response['output']) > $outputLimit) {
            $response['output'] = 'Output is too long to post';
        }

        // Format the message
        $msg = $this->getConfig('ideone.format', '%nick%: [ %link% ] %output%');
        $response['nick'] = $nick;
        $response['link'] = 'http://ideone.com/' . $link;
        foreach ($response as $key => $value) {
            $msg = str_replace('%' . $key . '%', $value, $msg);
        }
        $this->doPrivmsg($source, $msg);
    }
}
