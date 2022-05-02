<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright (C) 2004-2014 Olivier Diserens <olivier@diserens.ch>
 *                2015-2017 Mentor Reka <reka.mentor@gmail.com>
 *                2015-2017 Florian Billebault <florian.billebault@gmail.com>
 */

require_once('MCSoap/Status.php');
require_once('MCSoap/Config.php');
require_once('MCSoap/Services.php');
require_once('MCSoap/Content.php');
require_once('MCSoap/Quarantine.php');
require_once('MCSoap/Logs.php');
require_once('MCSoap/Stats.php');

class SoapInterface
{
  /**
   * *************
   * Test
   * *************
   */
  /**
   * This function simply answer with the question
   *
   * @param string  $question
   * @return string
   */
	public function Test_getResponse($question) {
		require_once('MCSoap/Test.php');
		return MCSoap_Test::Test_getResponse($question);
	}
	
	
  /**
   * *************
   * Status
   * *************
   */
  /**
   * This function will gather status
   * 
   * @param  array
   * @return array
   */
   static public function Status_getStatusValues($params) {
        return MCSoap_Status::Status_getStatusValues($params);
   }
   
  /**
   * This function simply answer with the question
   *
   * @return array
   */
	public function Status_getProcessesStatus() {
		return MCSoap_Status::Status_getProcessesStatus();
	}
  /**
   * This function simply answer with the question
   *
   * @return float
   */
	public function Status_getSystemLoad() {
		return MCSoap_Status::Status_getSystemLoad();
	}
   /**
    * This function return the current hardware status
    * 
    * @return array
    */
	static public function Status_getHardwareHealth() {
		return MCSoap_Status::Status_getHardwareHealth();
	}
	
	/**
	* This function will return all informational messages of the host
	*
	* @return array
	*/
	static public function Status_getInformationalMessages($params) {
		return MCSoap_Status::Status_getInformationalMessages($params);
	}
	
	/**
	 * Config
	 */
  /**
   * This function simply copy temporary interfaces file to system one
   *
   * @return string
   */
	static public function Config_saveInterfaceConfig() {
		return MCSoap_Config::Config_saveInterfaceConfig();
	}
	
  /**
   * This function restart networking services
   *
   * @return string
   */
   static public function Config_applyNetworkSettings() {
   	    return MCSoap_Config::Config_applyNetworkSettings();
   }
   
  /**
   * This function simply copy temporary resolv.conf file to system one
   *
   * @return string
   */
	static public function Config_saveDnsConfig() {
		return MCSoap_Config::Config_saveDnsConfig();
	}
	
  /**
   * This function set up the time zone
   *
   * @param  string  zone
   * @return string
   */
	static public function Config_saveTimeZone($zone) {
		return MCSoap_Config::Config_saveTimeZone($zone);
	}
	
  /**
   * This function apply the ntp config
   *
   * @param  boolean  sync
   * @return string
   */
	static public function Config_saveNTPConfig($sync = false) {
		return MCSoap_Config::Config_saveNTPConfig($sync);
	}
	
  /**
   * This function apply the provided time and date
   *
   * @param  string  date and time
   * @return string
   */
	static public function Config_saveDateTime($string) {
        return MCSoap_Config::Config_saveDateTime($string);
	}
	
 /**
   * This function will save some mailcleaner config option
   *
   * @param  array  options
   * @return string
   */
	static public function Config_saveMCConfigOption($options) {
	    return MCSoap_Config::Config_saveMCConfigOption($options);
	}
	
  /**
   * This function will save and validate registration number
   *
   * @param  string  serial number
   * @return string
   */
	static public function Config_saveRegistration($serial) {
		return MCSoap_Config::Config_saveRegistration($serial);
	}

 /**
   * This function will register this host
   *
   * @param  array   registration data
   * @return string
   */
        static public function Config_register($data) {
            return MCSoap_Config::Config_register($data);
       }


/**
   * This function will register this host
   * @param  array   registration data
   * @return string
   */
        static public function Config_register_ce($data) {
            return MCSoap_Config::Config_register_ce($data);
       }


/**
   * This function will register this host
   * @param  array   registration data
   * @return string
   */
        static public function Config_unregister($data) {
            return MCSoap_Config::Config_unregister($data);
       }


/**
   * This function will change the host id
   * @param  array   registration data
   * @return string
   */
        static public function Config_hostid($data) {
            return MCSoap_Config::Config_hostid($data);
       }

/**
   * This function will enable auto-configuration on MailCleaner
   * @param  array data
   * @return string
   */
	static public function Config_autoconfiguration($data) {
	    return MCSoap_Config::Config_autoconfiguration($data);
	}

/**
   * This function will download and set one time the MailCleaner reference configuration
   * @param  array data
   * @return string
   */
        static public function Config_autoconfigurationDownload($data) {
            return MCSoap_Config::Config_autoconfigurationDownload($data);
        }

  /**
   * This function restart syslog services
   *
   * @return string
   */
	static public function Services_restartSyslog() {
		return  MCSoap_Services::Services_restartSyslog();
	}
	
  /**
   * This function restart MTA (Exim) services
   *
   * @param  array  stages
   * @param  string command (stop|start|restart)
   * @return string
   */
	static public function Services_stopstartMTA($stages, $command) {
		return  MCSoap_Services::Services_stopstartMTA($stages, $command);
	}
	
  /**
   * This function return starter log
   *
   * @param  string  service
   * @return string
   */
	static public function Services_getStarterLog($service) {
		return  MCSoap_Services::Services_getStarterLog($service);
	}
	
   /**
    * This function will set one process's status to be restarted
    * 
    * @param  array  services
    * @return string
    */
	static public function Service_setServiceToRestart($services) {
	    return  MCSoap_Services::Service_setServiceToRestart($services);
	}
	
   /**
    * This function will silently stop a service
    * 
    * @param  array  params
    * @return array
    */
    static public function Service_silentStopStart($params) {
    	return  MCSoap_Services::Service_silentStopStart($params);
    }
    
    /**
    * This function will clear the callout cache
    *
    * @param  array  params
    * @return array
    */
    static public function Service_clearCalloutCache($params) {
    	return  MCSoap_Services::Service_clearCalloutCache($params);
    }

    /**
    * This function will clear the SMTP authentication cache for a domain
    *
    * @param  array  params
    * @return array
    */
    static public function Service_clearSMTPAutCache($params) {
    	return  MCSoap_Services::Service_clearSMTPAutCache($params);
    }
    
   /**
    * This function will silently dump a config file
    * 
    * @param  array  params
    * @return array
    */
    static public function Service_silentDump($params) {
        return  MCSoap_Services::Service_silentDump($params);
    }
    
   /**
    * This function will search for quarantined content
    * 
    * @param  array  params
    * @return array
    */
	static public function Content_fetchAll($params, $limit=0) {
	    return  MCSoap_Content::Content_fetchAll($params, $limit);
	}
	
   /**
    * This function will fetch information on quarantined content
    * 
    * @param  array  params
    * @return array
    */
	static public function Content_find($params) {
	    return  MCSoap_Content::Content_find($params);
	}
	
   /**
    * This function will release a quarantined message
    * 
    * @param  array  params
    * @return array
    */
	static public function Content_release($params) {
	    return  MCSoap_Content::Content_release($params);		
	}
	
   /**
    * This function will release a quarantined message
    *
    * @param  array  params
    * @return array
    */
        static public function Quarantine_findSpam($params) {
            return  MCSoap_Quarantine::Quarantine_findSpam($params);
        }

  /**
   * This function will start a messages tracing
   *
   * @param  array $params
   * @return array
   */
	static public function Logs_StartTrace($params) {
		return MCSoap_Logs::Logs_StartTrace($params);
	}
	
  /**
   * This function will fetch tracing results
   *
   * @param  array $params
   * @return array
   */
	static public function Logs_GetTraceResult($params, $limit = 0) {
		return MCSoap_Logs::Logs_GetTraceResult($params, $limit);
	}
  /**
   * This function will stop a messages tracing
   *
   * @param  array $params
   * @return array
   */
	static public function Logs_AbortTrace($params) {
	    return MCSoap_Logs::Logs_AbortTrace($params);
	}
	
  /**
   * This function will start a stats gathering
   *
   * @param  array $params
   * @return array
   */
	static public function Logs_StartGetStat($params) {
		return MCSoap_Stats::Logs_StartGetStat($params);
	}
	
  /**
   * This function will fetch stats results
   *
   * @param  array $params
   * @return array
   */
	static public function Logs_GetStatsResult($params, $limit = 0) {
		return MCSoap_Stats::Logs_GetStatsResult($params, $limit);
	}
	
  /**
   * This function will stop a stats tracing
   *
   * @param  array $params
   * @return array
   */
	static public function Logs_AbortStats($params) {
		return MCSoap_Stats::Logs_AbortStats($params);
	}
	
	/**
	 * This function will list logs files
	 *
	 * @param  array $params
	 * @return array
	 */
	static public function Logs_FindLogFiles($params) {
	    return MCSoap_Logs::Logs_FindLogFiles($params);
	}
	
	/**
	 * This function will fetch log lines
	 *
	 * @param  array $params
	 * @return array
	 */
	static public function Logs_GetLogLines($params) {
		return MCSoap_Logs::Logs_GetLogLines($params);
	}

        /**
         * This function will fetch a log extract for a specific message from a previous trace
         *
         * @param  array $params
         * @return array
         */
        static public function Logs_ExtractLog($params) {
                return MCSoap_Logs::Logs_ExtractLog($params);
        }	

	/**
     * This function will fetch today's stats
     * 
     * @param array $params
     * @return array
     */
    static public function Status_getTodayStats($params) {
    	return MCSoap_Status::Status_getTodayStats($params);
    }
    
    /**
     * This function will fetch messages in spool
     *
     * @param  array $params
     * @return array
     */
    static public function Status_getSpool($params) {
    	return MCSoap_Status::Status_getSpool($params);
    }
    
    /**
     * This function will delete messages in spool
     *
     * @param  array $params
     * @return array
     */
    static public function Status_spoolDelete($params) {
        return MCSoap_Status::Status_spoolDelete($params);
    }
    
    /**
     * This function will try to send messages in spool
     *
     * @param  array $params
     * @return array
     */
    static public function Status_spoolTry($params) {
        return MCSoap_Status::Status_spoolTry($params);
    }
}
?>
