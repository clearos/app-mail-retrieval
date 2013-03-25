<?php

/**
 * Mail retrieval entries controller.
 *
 * @category   Apps
 * @package    Mail_Retrieval
 * @subpackage Controllers
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
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \Exception as Exception;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Mail retrieval entries controller.
 *
 * @category   Apps
 * @package    Mail_Retrieval
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2013 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mail_retrieval/
 */

class Entries extends ClearOS_Controller
{
    /**
     * Mail retrieval entries summary view.
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->lang->load('mail_retrieval');
        $this->load->library('mail_retrieval/Fetchmail');

        // Load view data
        //---------------

        try {
            $data['protocols'] = $this->fetchmail->get_protocols();
            $data['entries'] = $this->fetchmail->get_mail_entries();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
 
        // Load views
        //-----------

        $this->page->view_form('summary', $data, lang('mail_retrieval_app_name'));
    }

    /**
     * Add entry view.
     *
     * @return view
     */

    function add()
    {
        $this->_item('add');
    }

    /**
     * Delete view.
     *
     * @param string $key encoded key value
     *
     * @return view
     */

    function delete($key)
    {
        $key_decoded = base64_decode(strtr($key, '-_.', '+/='));
        $params = preg_split('/\|/', $key_decoded);

        $confirm_uri = '/app/mail_retrieval/entries/destroy/' . $key;
        $cancel_uri = '/app/mail_retrieval/entries';
        $items = array($params[1] . ' - ' . $params[2]);

        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, $items);
    }

    /**
     * Destroys view.
     *
     * @param string $key encoded key value
     *
     * @return view
     */

    function destroy($key)
    {
        $key_decoded = base64_decode(strtr($key, '-_.', '+/='));
        $params = preg_split('/\|/', $key_decoded);

        // Load libraries
        //---------------

        $this->load->library('mail_retrieval/Fetchmail');

        // Handle delete
        //--------------

        try {
            $this->fetchmail->delete_mail_entry($params[0]);

            $this->page->set_status_deleted();
            redirect('/mail_retrieval/entries');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * Edit view.
     *
     * @param string $key encoded key value
     *
     * @return view
     */

    function edit($key)
    {
        $key_decoded = base64_decode(strtr($key, '-_.', '+/='));
        $params = preg_split('/\|/', $key_decoded);

        $this->_item('edit', $params[0]);
    }

    /**
     * View view.
     *
     * @param string $key encoded key value
     *
     * @return view
     */

    function view($key)
    {
        $key_decoded = base64_decode(strtr($key, '-_.', '+/='));
        $params = preg_split('/\|/', $key_decoded);

        $this->_item('view', $params[0]);
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Common add/edit form handler.
     *
     * @param string $form_type form type
     * @param string $id        entry ID
     *
     * @return view
     */

    function _item($form_type, $id = NULL)
    {
        // Load libraries
        //---------------

        $this->load->library('mail_retrieval/Fetchmail');
        $this->lang->load('mail_retrieval');
        $this->lang->load('network');

        // Set validation rules
        //---------------------

        $check_exists = ($form_type === 'add') ? TRUE : FALSE;

        $this->form_validation->set_policy('server', 'mail_retrieval/Fetchmail', 'validate_server', TRUE);
        $this->form_validation->set_policy('protocol', 'mail_retrieval/Fetchmail', 'validate_protocol', TRUE);
        $this->form_validation->set_policy('ssl', 'mail_retrieval/Fetchmail', 'validate_state', TRUE);
        $this->form_validation->set_policy('username', 'mail_retrieval/Fetchmail', 'validate_username', TRUE);
        $this->form_validation->set_policy('password', 'mail_retrieval/Fetchmail', 'validate_password', TRUE);
        $this->form_validation->set_policy('local_username', 'mail_retrieval/Fetchmail', 'validate_local_username', TRUE);
        $this->form_validation->set_policy('keep', 'mail_retrieval/Fetchmail', 'validate_state', TRUE);
        $this->form_validation->set_policy('state', 'mail_retrieval/Fetchmail', 'validate_state', TRUE);

        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && ($form_ok === TRUE)) {
            try {
                $enabled_count_before = $this->fetchmail->get_entries_count(TRUE);

                if ($form_type === 'edit') {
                    $this->fetchmail->update_mail_entry(
                        $id,
                        1,
                        $this->input->post('server'),
                        $this->input->post('protocol'),
                        $this->input->post('ssl'),
                        $this->input->post('username'),
                        $this->input->post('password'),
                        $this->input->post('local_username'),
                        $this->input->post('keep'),
                        $this->input->post('state')
                    );

                    $this->page->set_status_updated();
                } else {
                    $this->fetchmail->add_mail_entry(
                        $this->input->post('server'),
                        $this->input->post('protocol'),
                        $this->input->post('ssl'),
                        $this->input->post('username'),
                        $this->input->post('password'),
                        $this->input->post('local_username'),
                        $this->input->post('keep'),
                        $this->input->post('state')
                    );

                    $this->page->set_status_added();
                }

                // Be nice and neable fetchmail when the first active entry is enabled
                $enabled_count_after = $this->fetchmail->get_entries_count(TRUE);

                if (($enabled_count_before === 0) && ($enabled_count_after === 1))
                    $this->fetchmail->set_running_state(TRUE);

                redirect('/mail_retrieval/entries');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load the view data 
        //------------------- 

        try {
            if ($form_type === 'edit') {
                $data['entry'] = $this->fetchmail->get_mail_entry($id);
            } else {
                $data['entry'] = array(
                    'state' => TRUE,
                    'keep' => FALSE,
                    'ssl' => FALSE,
                );
            }

            $data['id'] = $id;
            $data['form_type'] = $form_type;
            $data['protocols'] = $this->fetchmail->get_protocols();
            $data['entries'] = $this->fetchmail->get_mail_entries();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load the views
        //---------------

        $this->page->view_form('item', $data, lang('mail_retrieval_mail_entry'));
    }
}
