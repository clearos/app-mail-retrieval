<?php

/**
 * Mail Retrieval daemon controller.
 *
 * @category   apps
 * @package    mail-retrieval
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2013 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mail_retrieval/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require clearos_app_base('base') . '/controllers/daemon.php';

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Mail Retrieval daemon controller.
 *
 * @category   apps
 * @package    mail-retrieval
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2013 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mail_retrieval/
 */

class Server extends Daemon
{
    /**
     * Mail retrieval daemon constructor.
     */

    function __construct()
    {
        parent::__construct('fetchmail', 'mail_retrieval');
    }

    /**
     * Daemon status.
     *
     * @return view
     */

    function status()
    {
        // Load libraries
        //---------------

        $this->load->library('mail_retrieval/Fetchmail');

        // Load view data
        //---------------

        try {
            $enabled_count = $this->fetchmail->get_entries_count(TRUE);
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        if ($enabled_count === 0) {
            $status['status'] = 'no_entries';
        } else {
            $this->load->library('base/Daemon', $this->daemon_name);
            $status['status'] = $this->daemon->get_status();
        }

        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');

        echo json_encode($status);
    }
}
