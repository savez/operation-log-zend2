<?php

namespace OperationLog;

use Zend\Http\PhpEnvironment\RemoteAddress;

use Zend\EventManager\StaticEventManager;
use Zend\Mvc\MvcEvent;
use Zend\Log\Writer\Db as DbWriter;
use Zend\Log\Writer\Stream as StreamWriter;
use Zend\Log\Logger;
use Zend\Log\Filter\Priority as PriorityFilter;

class Module
{
	public function getAutoloaderConfig()
	{
		return array(
			'Zend\Loader\ClassMapAutoloader' => array(
				__DIR__ . '/autoload_classmap.php'
			),
			'Zend\Loader\StandardAutoloader' => array(
				'namespaces' => array(
					__NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__
				)
			)
		);
	}

	public function onBootstrap(MvcEvent $e)
	{
		$events = StaticEventManager::getInstance();
		$serviceManager = $e->getApplication()->getServiceManager();
		$appConfig = $serviceManager->get('Config');

		$logger = new Logger();

		if(!isset($appConfig['logger'])) {
			throw new \RuntimeException("Logger not properly configured");
		}

		if(!isset($appConfig['logger']['priority_filter'])) {
			throw new \RuntimeException("You must specify a 'priority_filter' config param");
		}
		
		$logFilter = new PriorityFilter($appConfig['logger']['priority_filter']);
		
		if(!is_null($appConfig['logger']['db_adapter'])) {
		    if((empty($appConfig['logger']['logger_table']))) {
		    	throw new \RuntimeException("You must specify a 'logger_table' config param");
		    }
		    
		    $dbAdapter = $serviceManager->get($appConfig['logger']['db_adapter']);
		    if(!$dbAdapter instanceof \Zend\Db\Adapter\Adapter) {
		    	throw new \RuntimeException("Failed to load database adapter for logger");
		    }
		    
		    $tableMapping = array(
		    		'timestamp' => 'event_date',
		    		'priorityName' => 'priority',
		    		'message' => 'event',
		    		'extra' => array(
						'id_operation_log' => 'id_operation_log',
						'note' => 'note',
						'table' => 'table',
						'id_user' => 'id_user',
						'username' => 'username',
						'id_row' => 'id_row',
						'field' => 'field',
						'value_old' => 'value_old',
						'value_new' => 'value_new',
						'source' => 'source',
						'uri' => 'uri',
						'ip'  => 'ip',
						'session_id' => 'session_id'
		    		)
		    );
		    
		    $logWriter = new DbWriter($dbAdapter, $appConfig['logger']['logger_table'], $tableMapping);
		    
		    $logWriter->addFilter($logFilter);
		    $logger->addWriter($logWriter);
		}

		// nel caso si volgia fare un file LOG
		if(isset($appConfig['logger']['log_file']) && !is_null($appConfig['logger']['log_file'])) {
			$streamWriter = new StreamWriter($appConfig['logger']['log_file']);
			$streamWriter->addFilter($logFilter);
			$logger->addWriter($streamWriter);
		}
	
		$request = $e->getApplication()->getRequest();
		$remoteAddress = new RemoteAddress();

		Logger::registerErrorHandler($logger, true);
		Logger::registerExceptionHandler($logger);

		// Attacco evento per trigger LOG! (evento: operation-log)
		$events->attach("*",'operation-log', function(\Zend\EventManager\Event $e) use ($logger, $request, $remoteAddress,$serviceManager) {
			$targetClass = get_class($e->getTarget());
			$message = $e->getParam('message');
			$priority = $e->getParam('priority', Logger::INFO);

			$zfcAuthEvents = $serviceManager->get('zfcuser_auth_service');
			$idUser = $zfcAuthEvents->getIdentity()->getId();
			$displayName = $zfcAuthEvents->getIdentity()->getDisplayName();

			$extras = array(
				'id_operation_log' => null,
				'note' => (array_key_exists('note',$message)) ? $message['note'] : null,
				'table' => (array_key_exists('table',$message)) ? $message['table'] : null,
				'operation' => (array_key_exists('operation',$message)) ? $message['operation'] : null,
				'id_user' => $idUser,
				'username' => $displayName,
				'id_row' => (array_key_exists('id_row',$message)) ? $message['id_row'] : null,
				'field' => (array_key_exists('field',$message)) ? $message['field'] : null,
				'value_old' => (array_key_exists('value_old',$message)) ? $message['value_old'] : null,
				'value_new' => (array_key_exists('value_new',$message)) ? $message['value_new'] : null,
				'source' => $targetClass,
				'uri' => $request->getUriString(),
				'ip' => $remoteAddress->getIpAddress(),
				'session_id' => session_id(),
			);

			$logger->log($priority, $message['message'], $extras);
		});
	}

	public function getConfig()
	{
		return include __DIR__ . '/config/module.config.php';
	}

	public function getServiceConfig()
	{
		return array();
	}
}