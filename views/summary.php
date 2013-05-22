<?php

/**
 * Mail retrieval summary view.
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

$this->lang->load('mail_retrieval');
$this->lang->load('network');

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
    lang('mail_retrieval_server'),
    lang('mail_retrieval_remote_username'),
    lang('mail_retrieval_local_username'),
);

///////////////////////////////////////////////////////////////////////////////
// Anchors
///////////////////////////////////////////////////////////////////////////////

$anchors = array(anchor_add('/app/mail_retrieval/entries/add'));

///////////////////////////////////////////////////////////////////////////////
// Items
///////////////////////////////////////////////////////////////////////////////

foreach ($entries as $id => $details) {
    $key_encoded = strtr(base64_encode($details['start'] . '|' . $details['username'] . '|' . $details['poll']),  '+/=', '-_.');
    $username = (strlen($details['username']) > 18) ? substr($details['username'], 0, 18) . '...' : $details['username'];
    $poll = (strlen($details['poll']) > 18) ? substr($details['poll'], 0, 18) . '...' : $details['poll'];
    $is = (strlen($details['is']) > 18) ? substr($details['is'], 0, 18) . '...' : $details['is'];

    $detail_buttons = button_set(
        array(
            anchor_edit('/app/mail_retrieval/entries/edit/' . $key_encoded),
            anchor_delete('/app/mail_retrieval/entries/delete/' . $key_encoded)
        )
    );

    $item['title'] = $details['username'] . ' - ' . $details['poll'];
    $item['action'] = '/app/mail_retrieval/entries/edit/' . $key_encoded;
    $item['anchors'] = $detail_buttons;
    $item['details'] = array(
        $poll,
        $username,
        $is
    );

    $items[] = $item;
}

///////////////////////////////////////////////////////////////////////////////
// Summary table
///////////////////////////////////////////////////////////////////////////////

echo summary_table(
    lang('mail_retrieval_mail_entries'),
    $anchors,
    $headers,
    $items
);
