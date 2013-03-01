<?php

/**
 * Fetchmail class.
 *
 * @category   Apps
 * @package    Mail_Retrieval
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2013 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mail_retrieval/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\mail_retrieval;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('base');
clearos_load_language('mail_retrieval');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\File as File;
use \clearos\apps\network\Network_Utils as Network_Utils;

clearos_load_library('base/Daemon');
clearos_load_library('base/File');
clearos_load_library('network/Network_Utils');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/Validation_Exception');

//////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Fetchmail class.
 *
 * @category   Apps
 * @package    Mail_Retrieval
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2013 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mail_retrieval/
 */

class Fetchmail extends Daemon
{
    //////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CONFIG = '/etc/fetchmail';
    const CONSTANT_WHITESPACE = '[\s,;:=]';
    const CONSTANT_NON_WHITESPACE = '[^\s,;:=\']';
    const CONSTANT_POLL_INTERVAL = 'set daemon ';
    const DEFAULT_POLL_INTERVAL = 300;

    //////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $protocols = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Fetchmail constructor.
     *
     * @return  void
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->protocols = array(
            'pop3' => lang('mail_retrieval_pop3'),
            'imap' => lang('mail_retrieval_imap'),
            'apop' => lang('mail_retrieval_apop'),
            'auto' => lang('mail_retrieval_auto_detect'),
        );

        parent::__construct('fetchmail');
    }

    /**
     * Adds a configuration entry.
     *
     * @param string  $server   server
     * @param string  $protocol protocol
     * @param string  $ssl      SSL flag
     * @param string  $username username
     * @param string  $password password
     * @param string  $local    local user
     * @param boolean $keep     keep mail on server flag
     * @param boolean $state    state of account
     *
     * @return void
     * @throws Engine_Exception
     */

    public function add_mail_entry($server, $protocol, $ssl, $username, $password, $local, $keep, $state = TRUE)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        Validation_Exception::is_valid($this->validate_server($server));
        Validation_Exception::is_valid($this->validate_protocol($protocol));
        Validation_Exception::is_valid($this->validate_state($ssl));
        Validation_Exception::is_valid($this->validate_username($username));
        Validation_Exception::is_valid($this->validate_password($password));
        Validation_Exception::is_valid($this->validate_local_username($local));
        Validation_Exception::is_valid($this->validate_state($keep));
        Validation_Exception::is_valid($this->validate_state($state));

        $file = new File(self::FILE_CONFIG);
        $contents = $file->get_contents_as_array();

        $entry = $this->_convert_mail_entry($server, $protocol, $ssl, $username, $password, $local, $keep, $state);

        if (in_array(trim($entry), $contents))
            throw new Engine_Exception(lang('base_entry_already_exists'));

        // Add entry
        //----------

        array_splice($contents, -1, 0, array($entry));

        $file->dump_contents_from_array($contents);
    }

    /**
     * Deletes a mail entry.
     *
     * @param integer $start  starting line
     * @param integer $length number of lines
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete_mail_entry($start, $length = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_CONFIG);
        $contents = $file->get_contents_as_array();

        array_splice($contents, $start, $length);
        $file->dump_contents_from_array($contents);
    }

    /**
     * Returns mail entry.
     *
     * @param integer $start starting line
     *
     * @return array mail entry information
     * @throws Engine_Exception
     */

    public function get_mail_entry($start)
    {
        clearos_profile(__METHOD__, __LINE__);

        $entries = $this->get_mail_entries();

        return $entries[$start];
    }

    /**
     * Returns mail entries.
     *
     * @return array array of mail entries
     * @throws Engine_Exception
     */

    public function get_mail_entries()
    {
        clearos_profile(__METHOD__, __LINE__);

        $data = array();

        $W = self::CONSTANT_WHITESPACE;

        $file = new File(self::FILE_CONFIG);
        $contents = $file->get_contents_as_array();

        // Merge lines (inelegantly), then send to be parsed
        // If comments divide a multi-line entry, buggage results

        $entry = '';
        $length = 0;
        $start = '';
        
        foreach ($contents as $line_num => $line) {
            $length++;

            if (preg_match("/^$W*#/", $line) || preg_match("/^$W*$/", $line) || preg_match("/set$W+daemon$W+[0-9]+/", $line)) {
                $length--;
                continue;
            } else if (preg_match("/((poll$W)|(skip$W))/", $line)) {
                if ($entry == '') {
                    $entry = $line;
                    $start = $line_num;
                    $length = 0;
                } else {
                    $fields = $this->_parse_config_entry($entry);

                    if ($fields['poll']) {
                        $fields['start'] = $start;
                        $fields['length'] = $length;
                        $data[] = $fields;
                    }

                    $entry = $line;
                    $start = $line_num;
                    $length = 0;
                }
            } else {
                $entry .= ' ' . $line;
            }
        }

        if ($entry != '') {
            $fields = $this->_parse_config_entry($entry);
            $fields['start'] = $start;
            $fields['length'] = ++$length;

            if ($fields['poll'])
                $data[] = $fields;
        }

        return $data;
    }

    /**
     * Returns the poll interval.
     *
     * @return integer poll interval in seconds
     * @throws Engine_Exception
     */

    public function get_poll_interval()
    {
        clearos_profile(__METHOD__, __LINE__);

        $reg = array();
        $kill_lines = array();
        $found = FALSE;
        $touched = FALSE;
        $interval = 0;

        $file = new File(self::FILE_CONFIG);
        $W = self::CONSTANT_WHITESPACE;
        $contents = $file->get_contents_as_array();

        foreach ($contents as $line_num => $line) {
            if (preg_match("/set$W+daemon$W+([0-9]+)/", $line, $reg)) {
                if (!$found) {
                    $interval = (int)$reg[1];

                    if ($interval > 0) {
                        $found = TRUE;
                    } else {
                        $kill_lines[] = $line_num;
                    }
                } else {
                    // Duplicate
                    $kill_lines[] = $line_num;
                }
            }
        }

        foreach ($kill_lines as $line_num) {
            unset($contents[$line_num]);
            $touched = TRUE;
        }

        if (!$found) {
            array_splice($contents, 0, 0, array(self::CONSTANT_POLL_INTERVAL . self::DEFAULT_POLL_INTERVAL));
            $touched = TRUE;
            $interval = self::DEFAULT_POLL_INTERVAL;
        }

        if ($touched)
            $file->dump_contents_from_array($contents);

        return $interval;
    }

    /**
     * Returns list of supported protocols.
     *
     * @return array list of supported protocols
     * @throws Engine_Exception
     */

    public function get_protocols()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->protocols;
    }

    /**
     * Replaces a mail entry.
     *
     * @param integer $start    starting line
     * @param integer $length   number of lines
     * @param string  $server   server
     * @param string  $protocol protocol
     * @param string  $ssl      use SSL flag
     * @param string  $username username
     * @param string  $password password
     * @param string  $local    local user
     * @param boolean $keep     keep mail on server flag
     * @param boolean $state    state of account
     *
     * @return void
     * @throws Engine_Exception
     */

    public function update_mail_entry($start, $length, $server, $protocol, $ssl, $username, $password, $local, $keep, $state)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        Validation_Exception::is_valid($this->validate_server($server));
        Validation_Exception::is_valid($this->validate_protocol($protocol));
        Validation_Exception::is_valid($this->validate_state($ssl));
        Validation_Exception::is_valid($this->validate_username($username));
        Validation_Exception::is_valid($this->validate_password($password));
        Validation_Exception::is_valid($this->validate_local_username($local));
        Validation_Exception::is_valid($this->validate_state($keep));
        Validation_Exception::is_valid($this->validate_state($state));

        // Update
        //-------

        $file = new File(self::FILE_CONFIG);

        $entry = $this->_convert_mail_entry($server, $protocol, $ssl, $username, $password, $local, $keep, $state);
        $contents = $file->get_contents_as_array();

        array_splice($contents, $start, $length, array($entry));
        $file->dump_contents_from_array($contents);
    }

    /**
     * Sets the poll interval.
     *
     * @param string $interval poll interval
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_poll_interval($interval)
    {
        clearos_profile(__METHOD__, __LINE__);

        $W = self::CONSTANT_WHITESPACE;

        // FIXME: validate

        $file = new File(self::FILE_CONFIG);
        $contents = $file->get_contents_as_array();

        // Kill any pre-existing
        $kill_lines = array();

        foreach ($contents as $line_num => $line) {
            if (preg_match("/set$W+daemon$W+([0-9]+)/", $line))
                $kill_lines[] = $line_num;
        }

        foreach ($kill_lines as $line_num)
            unset($contents[$line_num]);

        // Addd new one
        array_splice($contents, 0, 0, array(self::CONSTANT_POLL_INTERVAL . $interval));

        $file->dump_contents_from_array($contents);
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validates local username.
     *
     * @param string $username username
     *
     * @return string error message if username is invalid
     */

    public function validate_local_username($username)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match("/^[a-zA-Z0-9\._\-@]+$/", $username))
            return lang('mail_retrieval_local_username_invalid');
    }

    /**
     * Validates password.
     *
     * @param string $password password
     *
     * @return string error message if password is invalid
     */

    public function validate_password($password)
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Validates protocol.
     *
     * @param string $protocol protocol
     *
     * @return string error message if protocol is invalid
     */

    public function validate_protocol($protocol)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! array_key_exists($protocol, $this->protocols))
            return lang('mail_retrieval_protocol_invalid');
    }

    /**
     * Validates username.
     *
     * @param string $username username
     *
     * @return string error message if username is invalid
     */

    public function validate_username($username)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match("/^[a-zA-Z0-9\._\-@]*$/", $username))
            return lang('base_username_invalid');
    }

    /**
     * Validates server.
     *
     * @param string $server server
     *
     * @return string error message if server is invalid
     */

    public function validate_server($server)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_hostname($server))
            return lang('mail_retrieval_server_invalid');
    }

    /**
     * Validates state.
     *
     * @param string $state state
     *
     * @return string error message if keep state is invalid
     */

    public function validate_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! clearos_is_valid_boolean($state))
            return lang('base_state_invalid');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Converts fields into a formatted mail entry.
     *
     * @param string  $server   server
     * @param string  $protocol protocol
     * @param string  $ssl      SSL flag
     * @param string  $username username
     * @param string  $password password
     * @param string  $local    local user
     * @param boolean $keep     keep mail on server flag
     * @param boolean $state    state of account
     *
     * @return string a formatted mail entry
     * @throws Validation_Exception
     */

    protected function _convert_mail_entry($server, $protocol, $ssl, $username, $password, $local, $keep, $state)
    {
        clearos_profile(__METHOD__, __LINE__);

        $W = self::CONSTANT_WHITESPACE;

        $line = ($state) ? "poll $server " : "skip $server ";

        if ($protocol)
            $line .= "protocol $protocol ";

        if ($username)
            $line .= "username \"$username\" ";

        if ($ssl)
            $line .= "ssl ";

        if ($password)
            $line .= "password \"$password\" ";

        if ($local)
            $line .= "is \"$local@localhost.\" here ";

        if ($keep)
            $line .= "keep ";

        return $line;
    }

    /**
     * Breaks a mail entry into fields.
     *
     * @param integer $line line
     *
     * @return array list of config fields
     * @throws Engine_Exception
     */

    protected function _parse_config_entry($line)
    {
        clearos_profile(__METHOD__, __LINE__);

        $fields = array();
        $reg = array();

        $W = self::CONSTANT_WHITESPACE;
        $NW = self::CONSTANT_NON_WHITESPACE;

        $matches = array();

        if (preg_match("/(poll|skip)$W((\"[^\"]+\")|($NW+))/", $line, $matches)) {
            $fields['state'] = ($matches[1] == 'poll');
            $fields['poll'] = trim($matches[2], "\"");
        }

        if (preg_match("/protocol$W((\"[^\"]+\")|($NW+))/", $line, $matches))
            $fields['protocol'] = trim($matches[1], "\"");

        if (preg_match("/no dns/", $line, $matches))
            $fields['nodns'] = TRUE;

        if (preg_match("/$W(ssl password)$W/", $line, $matches))
            $fields['ssl'] = TRUE;

        if (preg_match("/localdomains$W((\"[^\"]+\")|($NW+))/", $line, $matches))
            $fields['localdomains'] = trim($matches[1], "\"");

        if (preg_match("/user(name)?$W((\"[^\"]+\")|($NW+))/", $line, $matches))
            $fields['username'] = trim($matches[2], "\"");

        if (preg_match("/pass(word)?$W((\"[^\"]+\")|($NW+))/", $line, $matches))
            $fields['password'] = trim($matches[2], "\"");

        if (preg_match("/is$W((\"[^\"]+\")|($NW+))$W" . 'here/', $line, $matches))
            $fields['is'] = preg_replace('/@localhost.*$/', '', trim($matches[1], "\""));

        $fields['keep'] = (preg_match("/[^n][^o]$W+keep/", $line, $matches) == TRUE);

        return $fields;
    }
}
