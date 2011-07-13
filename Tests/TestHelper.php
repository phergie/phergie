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

error_reporting(E_ALL | E_STRICT);

// Phergie's test suite depends on PHPUnit 3.5+, because of assertInstanceOf.
$version = PHPUnit_Runner_Version::id();
if (version_compare($version, '3.5.0', '<')) {
    trigger_error("Requires PHPUnit 3.5+ to run the test suite", E_USER_ERROR);
}

// Phergie components require Phergie_Autoload to function correctly.
require_once dirname(__FILE__) . '/../Phergie/Autoload.php';
Phergie_Autoload::registerAutoloader();
