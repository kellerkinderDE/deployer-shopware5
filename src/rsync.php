<?php

namespace Deployer;

set('rsync',[
    'exclude'       => [''],
    'exclude-file'  => '',
    'include'       => [],
    'include-file'  => false,
    'filter'        => [],
    'filter-file'   => false,
    'filter-perdir' => false,
    'flags'         => 'rzcE',
    'options'       => ['delete', 'delete-after', 'force'],
    'timeout'       => 3600,
]);
