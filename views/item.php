<?php

/**
 * Mail retrieval item view.
 *
 * @category   apps
 * @package    mail-retrieval
 * @subpackage views
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
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->load->language('mail_retrieval');
$this->load->language('network');
$this->load->language('base');

///////////////////////////////////////////////////////////////////////////////
// Form handler
///////////////////////////////////////////////////////////////////////////////

$key_encoded = strtr(base64_encode($entry['start'] . '|' . $entry['username'] . '|' . $entry['poll']),  '+/=', '-_.');

if ($form_type === 'edit') {
    $read_only = FALSE;
    $form_path = '/mail_retrieval/entries/edit/' . $key_encoded;
    $buttons = array(
        form_submit_update('submit'),
        anchor_cancel('/app/mail_retrieval/entries'),
        anchor_delete('/app/mail_retrieval/entries/delete/' . $key_encoded)
    );
} else if ($form_type === 'add') {
    $read_only = FALSE;
    $form_path = '/mail_retrieval/entries/add';
    $buttons = array(
        form_submit_add('submit'),
        anchor_cancel('/app/mail_retrieval/entries'),
    );
} else {
    $read_only = TRUE;
    $form_path = '/mail_retrieval/entries';
    $buttons = array(
        anchor_cancel('/app/mail_retrieval/entries')
    );
}

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open($form_path);
echo form_header(lang('mail_retrieval_mail_entry'));

echo field_toggle_enable_disable('state', $entry['state'], lang('base_state'), $read_only);
echo field_input('server', $entry['poll'], lang('mail_retrieval_mail_server'), $read_only);
echo field_input('username', $entry['username'], lang('base_username'), $read_only);
echo field_password('password', $entry['password'], lang('base_password'), $read_only);
echo field_dropdown('protocol', $protocols, $entry['protocol'], lang('network_protocol'), $read_only);
echo field_toggle_enable_disable('ssl', $entry['ssl'], lang('mail_retrieval_ssl_mode'), $read_only);
echo field_input('local_username', $entry['is'], lang('mail_retrieval_local_username'), $read_only);
echo field_toggle_enable_disable('keep', $entry['keep'], lang('mail_retrieval_keep_on_remote_server'), $read_only);

echo field_button_set($buttons);

echo form_footer();
echo form_close();
