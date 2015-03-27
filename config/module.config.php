<?php

use Zend\Log\Logger;
use Zend\Log\Writer\Db;

return array(
	'logger' => array(
		'db_adapter' => 'Zend\Db\Adapter\Adapter',
		'logger_table' => 'operation_log',
		'priority_filter' => Logger::INFO,
		'log_file' => null
	)
);
