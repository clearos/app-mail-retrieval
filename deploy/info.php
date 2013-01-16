<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'mail_retrieval';
$app['version'] = '1.3.1';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('mail_retrieval_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('mail_retrieval_app_name');
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_mail');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_requires'] = array(
    'app-network-core >= 1:1.1.1',
    'app-smtp-core >= 1:1.3.1',
    'fetchmail',
);

$app['core_file_manifest'] = array(
    'fetchmail.php'=> array('target' => '/var/clearos/base/daemon/fetchmail.php'),
    'fetchmail.init'=> array(
        'target' => '/etc/rc.d/init.d/fetchmail',
        'mode' => '0755',
    )
);

$app['core_directory_manifest'] = array(
    '/var/run/fetchmail' => array(
        'mode' => '0755',
        'owner' => 'fetchmail',
        'group' => 'fetchmail',
    )
);
