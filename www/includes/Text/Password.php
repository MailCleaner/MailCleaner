<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Class to create passwords
 *
 * PHP version 5
 *
 * LICENSE:
 *
 * Copyright (c) 2004-2016 Martin Jansen, Olivier Vanhoucke, Michael Gauthier
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @category   Text
 * @package    Text_Password
 * @author     Martin Jansen <mj@php.net>
 * @author     Olivier Vanhoucke <olivier@php.net>
 * @copyright  2004-2016 Martin Jansen, Olivier Vanhoucke, Michael Gauthier
 * @license    http://www.opensource.org/licenses/mit-license.html MIT License
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/Text_Password
 */

/**
 * Number of possible characters in the password
 */
$GLOBALS['_Text_Password_NumberOfPossibleCharacters'] = 0;

/**
 * Main class for the Text_Password package
 *
 * @category   Text
 * @package    Text_Password
 * @author     Martin Jansen <mj@php.net>
 * @author     Olivier Vanhoucke <olivier@php.net>
 * @copyright  2004-2016 Martin Jansen, Olivier Vanhoucke, Michael Gauthier
 * @license    http://www.opensource.org/licenses/mit-license.html MIT License
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Password
 */
class Text_Password
{
    /**
     * Create a single password.
     *
     * @param  integer Length of the password.
     * @param  string  Type of password (pronounceable, unpronounceable)
     * @param  string  Character which could be use in the
     *                 unpronounceable password ex : 'ABCDEFG'
     *                 or numeric, alphabetical or alphanumeric.
     * @return string  Returns the generated password.
     */
    public static function create(
        $length = 10,
        $type = 'pronounceable',
        $chars = ''
    ) {
        switch ($type) {
            case 'unpronounceable':
                return self::_createUnpronounceable($length, $chars);

            case 'pronounceable':
            default:
                return self::_createPronounceable($length);
        }
    }

    /**
     * Create multiple, different passwords
     *
     * Method to create a list of different passwords which are
     * all different.
     *
     * @param  integer Number of different password
     * @param  integer Length of the password
     * @param  string  Type of password (pronounceable, unpronounceable)
     * @param  string  Character which could be use in the
     *                 unpronounceable password ex : 'A,B,C,D,E,F,G'
     *                 or numeric, alphabetical or alphanumeric.
     * @return array   Array containing the passwords
     */
    public static function createMultiple(
        $number,
        $length = 10,
        $type = 'pronounceable',
        $chars = ''
    ) {
        $passwords = [];

        while ($number > 0) {
            while (true) {
                $password = self::create($length, $type, $chars);
                if (!in_array($password, $passwords)) {
                    $passwords[] = $password;
                    break;
                }
            }
            $number--;
        }
        return $passwords;
    }

    /**
     * Create password from login
     *
     * Method to create password from login
     *
     * @param  string  Login
     * @param  string  Type
     * @param  integer Key
     * @return string
     */
    public static function createFromLogin($login, $type, $key = 0)
    {
        switch ($type) {
            case 'reverse':
                return strrev($login);

            case 'shuffle':
                return self::_shuffle($login);

            case 'xor':
                return self::_xor($login, $key);

            case 'rot13':
                return str_rot13($login);

            case 'rotx':
                return self::_rotx($login, $key);

            case 'rotx++':
                return self::_rotxpp($login, $key);

            case 'rotx--':
                return self::_rotxmm($login, $key);

            case 'ascii_rotx':
                return self::_asciiRotx($login, $key);

            case 'ascii_rotx++':
                return self::_asciiRotxpp($login, $key);

            case 'ascii_rotx--':
                return self::_asciiRotxmm($login, $key);
        }
    }

    /**
     * Create multiple, different passwords from an array of login
     *
     * Method to create a list of different password from login
     *
     * @param  array   Login
     * @param  string  Type
     * @param  integer Key
     * @return array   Array containing the passwords
     */
    public static function createMultipleFromLogin($login, $type, $key = 0)
    {
        $passwords = [];
        $number    = count($login);
        $save      = $number;

        while ($number > 0) {
            while (true) {
                $password = self::createFromLogin($login[$save - $number], $type, $key);
                if (!in_array($password, $passwords)) {
                    $passwords[] = $password;
                    break;
                }
            }
            $number--;
        }
        return $passwords;
    }

    /**
     * Helper method to create password
     *
     * Method to create a password from a login
     *
     * @param  string  Login
     * @param  integer Key
     * @return string
     */
    protected static function _xor($login, $key)
    {
        $tmp = '';

        for ($i = 0; $i < strlen($login); $i++) {
            $next = ord($login[$i]) ^ $key;
            if ($next > 255) {
                $next -= 255;
            } elseif ($next < 0) {
                $next += 255;
            }
            $tmp .= chr($next);
        }

        return $tmp;
    }

    /**
     * Helper method to create password
     *
     * Method to create a password from a login
     * lowercase only
     *
     * @param  string  Login
     * @param  integer Key
     * @return string
     */
    protected static function _rotx($login, $key)
    {
        $tmp = '';
        $login = strtolower($login);

        for ($i = 0; $i < strlen($login); $i++) {
            if ((ord($login[$i]) >= 97) && (ord($login[$i]) <= 122)) { // 65, 90 for uppercase
                $next = ord($login[$i]) + $key;
                if ($next > 122) {
                    $next -= 26;
                } elseif ($next < 97) {
                    $next += 26;
                }
                $tmp .= chr($next);
            } else {
                $tmp .= $login[$i];
            }
        }

        return $tmp;
    }

    /**
     * Helper method to create password
     *
     * Method to create a password from a login
     * lowercase only
     *
     * @param  string  Login
     * @param  integer Key
     * @return string
     */
    protected static function _rotxpp($login, $key)
    {
        $tmp = '';
        $login = strtolower($login);

        for ($i = 0; $i < strlen($login); $i++, $key++) {
            if ((ord($login[$i]) >= 97) && (ord($login[$i]) <= 122)) { // 65, 90 for uppercase
                $next = ord($login[$i]) + $key;
                if ($next > 122) {
                    $next -= 26;
                } elseif ($next < 97) {
                    $next += 26;
                }
                $tmp .= chr($next);
            } else {
                $tmp .= $login[$i];
            }
        }

        return $tmp;
    }

    /**
     * Helper method to create password
     *
     * Method to create a password from a login
     * lowercase only
     *
     * @param  string  Login
     * @param  integer Key
     * @return string
     */
    protected static function _rotxmm($login, $key)
    {
        $tmp = '';
        $login = strtolower($login);

        for ($i = 0; $i < strlen($login); $i++, $key--) {
            if ((ord($login[$i]) >= 97) && (ord($login[$i]) <= 122)) { // 65, 90 for uppercase
                $next = ord($login[$i]) + $key;
                if ($next > 122) {
                    $next -= 26;
                } elseif ($next < 97) {
                    $next += 26;
                }
                $tmp .= chr($next);
            } else {
                $tmp .= $login[$i];
            }
        }

        return $tmp;
    }

    /**
     * Helper method to create password
     *
     * Method to create a password from a login
     *
     * @param  string  Login
     * @param  integer Key
     * @return string
     */
    protected static function _asciiRotx($login, $key)
    {
        $tmp = '';

        for ($i = 0; $i < strlen($login); $i++) {
            $next = ord($login[$i]) + $key;
            if ($next > 255) {
                $next -= 255;
            } elseif ($next < 0) {
                $next += 255;
            }
            switch ($next) { // delete white space
                case 0x09:
                case 0x20:
                case 0x0A:
                case 0x0D:
                    $next++;
            }
            $tmp .= chr($next);
        }

        return $tmp;
    }

    /**
     * Helper method to create password
     *
     * Method to create a password from a login
     *
     * @param  string  Login
     * @param  integer Key
     * @return string
     */
    protected static function _asciiRotxpp($login, $key)
    {
        $tmp = '';

        for ($i = 0; $i < strlen($login); $i++, $key++) {
            $next = ord($login[$i]) + $key;
            if ($next > 255) {
                $next -= 255;
            } elseif ($next < 0) {
                $next += 255;
            }
            switch ($next) { // delete white space
                case 0x09:
                case 0x20:
                case 0x0A:
                case 0x0D:
                    $next++;
            }
            $tmp .= chr($next);
        }

        return $tmp;
    }

    /**
     * Helper method to create password
     *
     * Method to create a password from a login
     *
     * @param  string  Login
     * @param  integer Key
     * @return string
     */
    protected static function _asciiRotxmm($login, $key)
    {
        $tmp = '';

        for ($i = 0; $i < strlen($login); $i++, $key--) {
            $next = ord($login[$i]) + $key;
            if ($next > 255) {
                $next -= 255;
            } elseif ($next < 0) {
                $next += 255;
            }
            switch ($next) { // delete white space
                case 0x09:
                case 0x20:
                case 0x0A:
                case 0x0D:
                    $next++;
            }
            $tmp .= chr($next);
        }

        return $tmp;
    }

    /**
     * Helper method to create password
     *
     * Method to create a password from a login
     *
     * @param  string  Login
     * @return string
     */
    protected static function _shuffle($login)
    {
        $tmp = [];

        for ($i = 0; $i < strlen($login); $i++) {
            $tmp[] = $login[$i];
        }

        shuffle($tmp);

        return implode($tmp, '');
    }

    /**
     * Create pronounceable password
     *
     * This method creates a string that consists of
     * vowels and consonats.
     *
     * @param  integer Length of the password
     * @return string  Returns the password
     */
    protected static function _createPronounceable($length)
    {
        $retVal = '';

        /**
         * List of vowels and vowel sounds
         */
        $v = [
            'a', 'e', 'i', 'o', 'u', 'ae', 'ou', 'io',
            'ea', 'ou', 'ia', 'ai'
        ];

        /**
         * List of consonants and consonant sounds
         */
        $c = [
            'b', 'c', 'd', 'g', 'h', 'j', 'k', 'l', 'm',
            'n', 'p', 'r', 's', 't', 'u', 'v', 'w',
            'tr', 'cr', 'fr', 'dr', 'wr', 'pr', 'th',
            'ch', 'ph', 'st', 'sl', 'cl'
        ];

        $v_count = 12;
        $c_count = 29;

        $GLOBALS['_Text_Password_NumberOfPossibleCharacters'] = $v_count + $c_count;

        for ($i = 0; $i < $length; $i++) {
            $retVal .= $c[self::_rand(0, $c_count - 1)] . $v[self::_rand(0, $v_count - 1)];
        }

        return substr($retVal, 0, $length);
    }

    /**
     * Create unpronounceable password
     *
     * This method creates a random unpronounceable password
     *
     * @param  integer Length of the password
     * @param  string  Character which could be use in the
     *                 unpronounceable password ex : 'ABCDEFG'
     *                 or numeric, alphabetical or alphanumeric.
     * @return string  Returns the password
     */
    protected static function _createUnpronounceable($length, $chars)
    {
        $password = '';

        // Claases of characters which could be use in the password
        $lower = 'abcdefghijklmnopqrstuvwxyz';
        $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $decimal = '0123456789';
        $special = '_#@%&';

        switch ($chars) {

            case 'alphanumeric':
                $chars = [$lower, $upper, $decimal];
                break;

            case 'alphabetical':
                $chars = [$lower, $upper];
                break;

            case 'numeric':
                $chars = [$decimal];
                break;

            case '':
                $chars = [$lower, $upper, $decimal, $special];
                break;

            default:
                // Some characters shouldn't be used; filter them out of the
                // possible password characters that were passed in. The comma
                // character was used in the past to separate input characters and
                // remains in the block list for backwards compatibility. Other
                // block list characters may no longer be necessary now that
                // password generation does not use preg functions, but they also
                // remain for backwards compatibility.
                $chars = [trim($chars)];
                $chars = str_replace(['+', '|', '$', '^', '/', '\\', ','], '', $chars);
        }

        $GLOBALS['_Text_Password_NumberOfPossibleCharacters'] = 0;
        foreach ($chars as $charsItem) {
            $GLOBALS['_Text_Password_NumberOfPossibleCharacters'] += strlen($charsItem);
        }

        // Randomize the order of character classes. This ensures for short
        // passwords--less chars than classes--the character classes are
        // still random.
        shuffle($chars);

        // Loop over each character class to ensure the generated password
        // contains at least 1 character from each class.
        foreach ($chars as $possibleChars) {
            // Get a random character from the character class.
            $randomCharIndex = self::_rand(0, strlen($possibleChars) - 1);
            $randomChar = $possibleChars[$randomCharIndex];

            // Get a random insertion position in the current password
            // value.
            $randomPosition = self::_rand(0, max(strlen($password) - 1, 0));

            // Insert the new character in the current password value.
            $password = substr($password, 0, $randomPosition)
                . $randomChar
                . substr($password, $randomPosition);
        }

        // Join all the character classes together to form the rest of the
        // password value. This prevents small character classes from getting
        // weighted unfairly in the final password.
        $allPossibleChars = implode('', $chars);

        // Insert random chars until the password is long enough.
        while (strlen($password) < $length) {
            // Get a random character from the possible characters.
            $randomCharIndex = self::_rand(0, strlen($allPossibleChars) - 1);
            $randomChar = $allPossibleChars[$randomCharIndex];

            // Get a random insertion position in the current password
            // value.
            $randomPosition = self::_rand(0, max(strlen($password) - 1, 0));

            // Insert the new character in the current password value.
            $password = substr($password, 0, $randomPosition)
                . $randomChar
                . substr($password, $randomPosition);
        }

        // Truncate the password if it is too long. This can happen when the
        // desired length is shorter than the number of character classes.
        if (strlen($password) > $length) {
            $password = substr($password, 0, $length);
        }

        return $password;
    }

    /**
     * Gets a random integer between min and max
     *
     * On PHP 7, this uses random_int(). On older systems it uses mt_rand().
     *
     * @param integer $min
     * @param integer $max
     *
     * @return integer
     */
    protected static function _rand($min, $max)
    {
        if (version_compare(PHP_VERSION, '7.0.0', 'ge')) {
            $value = random_int($min, $max);
        } else {
            $value = mt_rand($min, $max);
        }

        return $value;
    }
}
