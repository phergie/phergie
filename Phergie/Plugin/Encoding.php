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
 * @package   Phergie_Plugin_Encoding
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Encoding
 */

/**
 * Handles decoding markup entities and converting text between character
 * encodings.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Encoding
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Encoding
 */
class Phergie_Plugin_Encoding extends Phergie_Plugin_Abstract
{
    /**
     * Lookup table for entity conversions not supported by
     * html_entity_decode()
     *
     * @var array
     * @link http://php.net/get_html_translation_table#73409
     * @link http://php.net/get_html_translation_table#73410
     */
    protected static $entities = array(
        '&alpha;' => 913,
        '&apos;' => 39,
        '&beta;' => 914,
        '&bull;' => 149,
        '&chi;' => 935,
        '&circ;' => 94,
        '&delta;' => 916,
        '&epsilon;' => 917,
        '&eta;' => 919,
        '&fnof;' => 402,
        '&gamma;' => 915,
        '&iota;' => 921,
        '&kappa;' => 922,
        '&lambda;' => 923,
        '&ldquo;' => 147,
        '&lsaquo;' => 139,
        '&lsquo;' => 145,
        '&mdash;' => 151,
        '&minus;' => 45,
        '&mu;' => 924,
        '&ndash;' => 150,
        '&nu;' => 925,
        '&oelig;' => 140,
        '&omega;' => 937,
        '&omicron;' => 927,
        '&phi;' => 934,
        '&pi;' => 928,
        '&piv;' => 982,
        '&psi;' => 936,
        '&rdquo;' => 148,
        '&rho;' => 929,
        '&rsaquo;' => 155,
        '&rsquo;' => 146,
        '&scaron;' => 138,
        '&sigma;' => 931,
        '&sigmaf;' => 962,
        '&tau;' => 932,
        '&theta;' => 920,
        '&thetasym;' => 977,
        '&tilde;' => 126,
        '&trade;' => 153,
        '&upsih;' => 978,
        '&upsilon;' => 933,
        '&xi;' => 926,
        '&yuml;' => 159,
        '&zeta;' => 918,
    );

    /**
     * Decodes markup entities in a given string.
     *
     * @param string $string  String containing markup entities
     * @param string $charset Optional character set name to use in decoding
     *        entities, defaults to UTF-8
     *
     * @return string String with markup entities decoded
     */
    public function decodeEntities($string, $charset = 'UTF-8')
    {
        $string = str_ireplace(
            array_keys(self::$entities),
            array_map('chr', self::$entities),
            $string
        );
        $string = html_entity_decode($string, ENT_QUOTES, $charset);
        $string = preg_replace(
            array('/&#0*([0-9]+);/me', '/&#x0*([a-f0-9]+);/mei'),
            array('$this->codeToUtf(\\1)', '$this->codeToUtf(hexdec(\\1))'),
            $string
        );
        return $string;
    }

    /**
     * Converts a given unicode to its UTF-8 equivalent.
     *
     * @param int $code Code to convert
     *
     * @return string Character corresponding to code
     */
    public function codeToUtf8($code)
    {
        $code = (int) $code;
        switch ($code) {
            // 1 byte, 7 bits
            case 0:
                return chr(0);
            case ($code & 0x7F):
                return chr($code);

            // 2 bytes, 11 bits
            case ($code & 0x7FF):
                return chr(0xC0 | (($code >> 6) & 0x1F)) .
                       chr(0x80 | ($code & 0x3F));

            // 3 bytes, 16 bits
            case ($code & 0xFFFF):
                return chr(0xE0 | (($code >> 12) & 0x0F)) .
                       chr(0x80 | (($code >> 6) & 0x3F)) .
                       chr(0x80 | ($code & 0x3F));

            // 4 bytes, 21 bits
            case ($code & 0x1FFFFF):
                return chr(0xF0 | ($code >> 18)) .
                       chr(0x80 | (($code >> 12) & 0x3F)) .
                       chr(0x80 | (($code >> 6) & 0x3F)) .
                       chr(0x80 | ($code & 0x3F));
        }
    }

    /**
     * Transliterates characters in a given string where possible.
     *
     * @param string $string      String containing characters to
     *        transliterate
     * @param string $charsetFrom Optional character set of the string,
     *        defaults to UTF-8
     * @param string $charsetTo   Optional character set to which the string
     *        should be converted, defaults to ISO-8859-1
     *
     * @return string String with characters transliterated or the original
     *         string if transliteration was not possible
     */
    public function transliterate($string, $charsetFrom = 'UTF-8', $charsetTo = 'ISO-8859-1')
    {
        // @link http://pecl.php.net/package/translit
        if (function_exists('transliterate')) {
            $string = transliterate($string, array('han_transliterate', 'diacritical_remove'), $charsetFrom, $charsetTo);
        } elseif (function_exists('iconv')) {
            $string = iconv($charsetFrom, $charsetTo . '//TRANSLIT', $string);
        } else {
            // @link http://stackoverflow.com/questions/1284535/php-transliteration/1285491#1285491
            $string = preg_replace(
                '~&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i',
                '$1',
                htmlentities($string, ENT_COMPAT, $charsetFrom)
            );
        }
        return $string;
    }
}
