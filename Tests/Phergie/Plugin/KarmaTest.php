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
 * @package   Phergie_Tests
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2011 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Tests
 */

/**
 * Unit test suite for Phergie_Plugin_Karma.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_Plugin_KarmaTest extends Phergie_Plugin_TestCase
{
    /**
     * Skips tests if the SQLite PDO driver is not available.
     *
     * @return void
     */
    public function setUp()
    {
        if (!extension_loaded('PDO') || !extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('PDO or pdo_sqlite extension is required');
        }

        parent::setUp();
    }

    /**
     * Configures the plugin to use a temporary copy of the database.
     *
     * @return PDO Connection to the temporary database
     */
    private function createMockDatabase()
    {
        $dbPath = $this->getPluginsPath('Karma/karma.db');
        $db = $this->getMockDatabase($dbPath);
        $this->plugin->setDb($db);
        return $db;
    }

    /**
     * Tests the requirement of the Command plugin.
     *
     * @return void
     */
    public function testRequiresCommandPlugin()
    {
        $this->assertRequiresPlugin('Command');
        $this->plugin->onLoad();
    }

    /**
     * Initiates a karma event with a specified term.
     *
     * @param string $term Karma term
     *
     * @return Phergie_Event_Request Initiated mock event
     */
    private function initiateKarmaEvent($term)
    {
        $args = array(
            'receiver' => $this->source,
            'text' => 'karma ' . $term
        );
        $event = $this->getMockEvent('privmsg', $args);
        $this->plugin->setEvent($event);
        return $event;
    }

    /**
     * Checks for an expected karma response.
     *
     * @param Phergie_Event_Request $event    Event containing the karma
     *                                        request
     * @param string                $term     Karma term
     * @param string                $response Portion of the response
     *                                        message following the term
     *                                        from the original event
     *
     * @return void
     */
    private function checkForKarmaResponse($event, $term, $response)
    {
        $text = $event->getNick() . ': ' . $response;
        $this->assertEmitsEvent('privmsg', array($event->getSource(), $text));
        $this->plugin->onCommandKarma($term);
    }

    /**
     * Tests that a default database is used when none is specified.
     *
     * @return void
     */
    public function testGetDb()
    {
        $db = $this->plugin->getDb();
        $this->assertInstanceOf('PDO', $db);
    }

    /**
     * Tests specifying a custom database for the plugin to use.
     *
     * @return void
     */
    public function testSetDb()
    {
        $db = $this->createMockDatabase();
        $this->assertSame($db, $this->plugin->getDb());
    }

    /**
     * Tests that issuing the karma command with an unknown term returns a
     * neutral rating.
     *
     * @return void
     */
    public function testKarmaCommandOnUnknownTerm()
    {
        $term = 'foo';
        $this->createMockDatabase();
        $event = $this->initiateKarmaEvent($term);
        $this->checkForKarmaResponse($event, $term, $term . ' has neutral karma.');
    }

    /**
     * Tests that issuing the karma command with the term "me" returns the
     * the karma rating for the initiating user.
     *
     * @return void
     */
    public function testKarmaCommandOnUser()
    {
        $term = 'me';
        $this->createMockDatabase();
        $event = $this->initiateKarmaEvent($term);
        $this->checkForKarmaResponse($event, $term, 'You have neutral karma.');
    }

    /**
     * Tests that issuing the karma command with a term that has a fixed
     * karma rating results in that rating being returned.
     *
     * @return void
     */
    public function testKarmaCommandWithFixedKarmaTerm()
    {
        $term = 'phergie';
        $this->createMockDatabase();
        $event = $this->initiateKarmaEvent($term);
        $this->checkForKarmaResponse($event, $term, 'phergie has karma of awesome');
    }

    /**
     * Supporting method that tests the result of a karma term rating change.
     *
     * @param string $term      Karma term for which the rating is being
     *                          changed
     * @param string $operation ++ or --
     * @param int    $karma     Expected karma rating after the change is
     *                          applied
     *
     * @return void
     */
    private function checkForKarmaRatingChange($term, $operation, $karma)
    {
        $args = array(
            'receiver' => $this->source,
            'text' => $term . $operation
        );
        $event = $this->getMockEvent('privmsg', $args);
        $this->plugin->setEvent($event);
        $this->plugin->onPrivmsg();
        $event = $this->initiateKarmaEvent($term);
        $this->checkForKarmaResponse(
            $event, $term, $term . ' has karma of ' . $karma . '.'
        );
    }

    /**
     * Tests incrementing the karma rating of a new term.
     *
     * @return void
     */
    public function testIncrementingKarmaRating()
    {
        $this->createMockDatabase();
        $this->checkForKarmaRatingChange('foo', '++', 1);
    }

    /**
     * Tests decrementing the karma rating of a new term.
     *
     * @return void
     */
    public function testDecrementingKarmaRating()
    {
        $this->createMockDatabase();
        $this->checkForKarmaRatingChange('foo', '--', -1);
    }

    /**
     * Tests modifying the karma rating of an existing term.
     *
     * @return void
     */
    public function testChangingExistingKarmaRating()
    {
        $term = 'foo';
        $this->createMockDatabase();
        $this->checkForKarmaRatingChange($term, '++', 1);
        $this->checkForKarmaRatingChange($term, '++', 2);
    }

    /**
     * Tests resetting the karma rating of an existing term to 0.
     *
     * @return void
     */
    public function testResettingExistingKarmaRating()
    {
        $term = 'foo';
        $this->createMockDatabase();
        $this->checkForKarmaRatingChange($term, '++', 1);
        $this->plugin->onCommandReincarnate($term);
        $event = $this->initiateKarmaEvent($term);
        $this->checkForKarmaResponse($event, $term, $term . ' has neutral karma.');
    }

    /**
     * Data provider for testKarmaComparisons().
     *
     * @return array Enumerated array of enumerated arrays each containing a
     *               set of parameter values for a single call to
     *               testKarmaComparisons()
     */
    public function dataProviderTestKarmaComparisons()
    {
        $term1 = 'foo';
        $term2 = 'bar';

        $positive = 'True that.';
        $negative = 'No sir, not at all.';

        return array(
            array($term1, $term2, 1, 0, '>', $positive),
            array($term1, $term2, 0, 1, '>', $negative),
            array($term1, $term2, 1, 1, '>', $negative),
            array($term1, $term2, 1, 0, '<', $negative),
            array($term1, $term2, 0, 1, '<', $positive),
            array($term1, $term2, 1, 1, '<', $negative),
            array($term1, 'phergie', 1, 0, '>', $positive),
            array('phergie', $term2, 0, 1, '<', $positive),
            array($term1, 'everything', 0, 0, '>', $positive),
            array('everything', $term2, 0, 0, '>', $positive),
        );
    }

    /**
     * Tests comparing the karma ratings of two terms.
     *
     * @param string $term1    First term
     * @param string $term2    Second term
     * @param int    $karma1   Karma rating of the first time, 0 or 1
     * @param int    $karma2   Karma rating of the second term, 0 or 1
     * @param string $operator Comparison operator, > or <
     * @param string $response Response to check for
     *
     * @return void
     * @dataProvider dataProviderTestKarmaComparisons
     */
    public function testKarmaComparisons($term1, $term2, $karma1, $karma2,
        $operator, $response
    ) {
        $db = $this->createMockDatabase();

        // Reduce answer tables to expected response
        $stmt = $db->prepare('DELETE FROM positive_answers WHERE answer != ?');
        $stmt->execute(array($response));
        $stmt = $db->prepare('DELETE FROM negative_answers WHERE answer != ?');
        $stmt->execute(array($response));

        if ($karma1) {
            $this->checkForKarmaRatingChange($term1, '++', 1);
        }

        if ($karma2) {
            $this->checkForKarmaRatingChange($term2, '++', 1);
        }

        $args = array(
            'receiver' => $this->source,
            'text' => $term1 . ' ' . $operator . ' ' . $term2
        );
        $event = $this->getMockEvent('privmsg', $args);
        $this->plugin->setEvent($event);

        // Test lack of a response for terms with fixed karma ratings
        if ($term1 == 'phergie' || $term2 == 'phergie') {
            $callback = 'assertDoesNotEmitEvent';
        } else {
            $callback = 'assertEmitsEvent';
        }

        $this->$callback('privmsg', array($event->getSource(), $response));
        $this->plugin->onPrivmsg();

        // Test for karma changes when one term is "everything"
        if ($term1 == 'everything' || $term2 == 'everything') {
            if ($term1 == 'everything') {
                $term = $term2;
                $karma = ($operator == '>') ? -1 : 1;
            } else {
                $term = $term1;
                $karma = ($operator == '>') ? 1 : -1;
            }
            $event = $this->initiateKarmaEvent($term);
            $this->checkForKarmaResponse(
                $event, $term, $term . ' has karma of ' . $karma . '.'
            );
        }
    }
}
