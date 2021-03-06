<?php

/**
 * Mail Retrieval controller.
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
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Mail Retrieval controller.
 *
 * @category   apps
 * @package    mail-retrieval
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2013 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mail_retrieval/
 */

class Mail_Retrieval extends ClearOS_Controller
{
    /**
     * Mail Retrieval summary view.
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->lang->load('mail_retrieval');

        // Load views
        //-----------

        $views = array('mail_retrieval/server', 'mail_retrieval/settings', 'mail_retrieval/entries');

        $this->page->view_forms($views, lang('mail_retrieval_app_name'));
    }
}
