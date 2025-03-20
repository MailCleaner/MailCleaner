<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright (C) 2004-2014 Olivier Diserens <olivier@diserens.ch>; 2023, John Mertz
 *                2015-2017 Mentor Reka <reka.mentor@gmail.com>
 *                2015-2017 Florian Billebault <florian.billebault@gmail.com>
 */
class MCSoap_Config
{

    /**
     * This function simply copy temporary interfaces file to system one
     *
     * @return string
     */
    static public function Config_saveInterfaceConfig()
    {
        $tmpfile = "/tmp/mc_initerfaces.tmp";

        if (!file_exists($tmpfile)) {
            return 'NOK notempfile';
        }
        $cmd = "/bin/cp $tmpfile /etc/network/interfaces";
        $res = `$cmd`;
        if ($res == "") {
            return 'OK settingsaved';
        } else {
            return 'NOK ' . $res;
        }
    }

    /**
     * This function restart networking services
     *
     * @return string
     */
    static public function Config_applyNetworkSettings()
    {

        ## first check run directory
        $rundir = "/etc/network/run";
        if (is_link($rundir) || is_file($rundir)) {
            unlink($rundir);
        }
        if (!is_dir($rundir)) {
            mkdir($rundir);
        }

        ## shut down all existing interfaces
        $ifconfig = `/sbin/ifconfig`;
        foreach (preg_split("/\n/", $ifconfig) as $line) {
            if (preg_match('/^(\S+)/', $line, $matches)) {
                $ifname = $matches[1];
                if ($ifname == 'lo') {
                    continue;
                }
                $resetcmd = "/sbin/ifconfig 0.0.0.0 " . $ifname;
                $resetres = `$resetcmd >/dev/null 2>&1`;
                $downcmd = "/sbin/ifconfig " . $ifname . " down";
                $downres = `$downcmd >/dev/null 2>&1`;
                echo $downcmd . "<br />";
            }
        }

        $cmd = '/usr/bin/systemctl restart networking 2>/dev/null && /usr/bin/systemctl restart ssh 2> /dev/null && echo done.';
        $res = `$cmd`;
        $status = 'OK networkingrestarted';

        $res = preg_replace('/\n/', '', $res);
        if (!preg_match('/^$/', $res)) {
            return "NOK $res";
        }

        require_once('NetworkInterface.php');
        require_once('NetworkInterfaceMapper.php');
        $ifs = new Default_Model_NetworkInterface();
        foreach ($ifs->fetchAll() as $i) {
            if ($i->getIPv4Param('mode') != 'disabled' || $i->getIPv6Param('mode') != 'disabled') {
                $upcmd = "/sbin/ifconfig " . $i->getName() . " up";
                $upres = `$upcmd >/dev/null 2>&1`;
            }
        }

        // TODO: Upgrade firewall
        require_once('MailCleaner/Config.php');
        $sysconf = MailCleaner_Config::getInstance();
        $cmd = $sysconf->getOption('SRCDIR') . "/etc/init.d/firewall restart";
        `$cmd >/dev/null 2>&1`;
        return $status;
    }


    /**
     * This function simply copy temporary resolv.conf file to system one
     *
     * @return string
     */
    static public function Config_saveDnsConfig()
    {
        $tmpfile = "/tmp/mc_resolv.tmp";
        $status = 'OK';

        if (!file_exists($tmpfile)) {
            return 'NOK notempfile';
        }
        $cmd = "/bin/cp $tmpfile /etc/resolv.conf";
        $res = `$cmd`;
        if ($res == "") {
            $status = 'OK settingsaved';
        } else {
            $status = 'NOK ' . $res;
        }

        // TODO: Upgrade caching name server
        if (file_exists('/etc/init.d/nscd')) {
            $cmd = '/etc/init.d/nscd restart';
            $res = `$cmd`;
            $res = preg_replace('/\n/', '', $res);
            if (!preg_match('/nscd\./', $res)) {
                return "NOK $res";
            } else {
                $status = 'OK settingapplied';
            }
        }

        return $status;
    }

    /**
     * This function set up the time zone
     *
     * @return string
     */
    static public function Config_saveTimeZone($zone)
    {
        $timezonefile = '/etc/timezone';
        $zoneinfodir = '/usr/share/zoneinfo';
        $localtimefile = '/etc/localtime';

        $data = preg_split('/\//', $zone);
        if (!isset($data[0]) || !isset($data[1])) {
            return 'NOK bad locale format';
        }

        $fullfile = $zoneinfodir . "/" . $data[0] . "/" . $data[1];
        if (!file_exists($fullfile)) {
            return 'NOK unknown locale ';
        }

        $written = file_put_contents($timezonefile, $zone);
        if (!$written) {
            return 'NOK could not same timezone';
        }

        unlink($localtimefile);
        `ln -s $fullfile $localtimefile`;
        putenv("TZ=" . $zone);
        return 'OK saved';
    }


    /**
     * This function apply the ntp config
     *
     * @param  boolean  sync
     * @return string
     */
    // TODO: NTP -> SystemD
    static public function Config_saveNTPConfig($sync = false)
    {
        $tmpconfigfile = '/tmp/mc_ntp.tmp';
        $configfile = '/etc/ntp.conf';
        $starter = '/etc/init.d/ntp';
        $full = '';

        if (is_array($sync) && defined($sync['sync'])) {
            $sync = $sync['sync'];
        }
        if (!file_exists($tmpconfigfile)) {
            return 'NOK notempfile';
        }
        $cmd = "/bin/cp $tmpconfigfile $configfile";
        $res = `$cmd`;
        $full .= preg_replace('/\n/', '', $res) . "<br />";
        if ($res == "") {
            $status = 'OK settingsaved';
        } else {
            $status = 'NOK ' . $res;
        }


        if (file_exists($starter)) {
            $cmd = "$starter stop";
            $res = `$cmd`;
            $full .= preg_replace('/\n/', '', $res) . "<br />";
            # typical command output:
            # "Stopping ntp (via systemctl): ntp.service"
            if (!preg_match('/ntp/', $res)) {
                return 'NOK cannotstopntp ';
            }

            if ($sync) {
                # fetch server to sync
                $content = file($configfile);
                $servers = [];
                foreach ($content as $line) {
                    if (preg_match('/^\s*server\s+(\S+)/', $line, $matches)) {
                        $servers[] = $matches[1];
                    }
                }
                if (count($servers) < 1) {
                    return 'NOK not server to sync with';
                }

                $cmd = '/usr/sbin/ntpdate ' . $servers[0] . " 2>&1";
                $res = `$cmd`;
                $full .= preg_replace('/\n/', '', $res) . "<br />";
                $res2 = preg_replace('/\n/', '', $res);
                if (!preg_match('/offset/', $res)) {
                    $res = preg_replace('/\n/', '', $res);
                    return "NOK could not sync <br />($res)";
                }

                $cmd = "$starter start";
                $res = `$cmd`;
                $full .= preg_replace('/\n/', '', $res) . "<br />";
                if (!preg_match('/ntp/', $res)) {
                    return 'NOK cannotstartntp ';
                }
                return 'OK ntp started and synced';
            }
            return 'OK ntp disabled';
        } else {
            if ($sync) {
                return 'NOK nontpclient';
            }
        }
        return 'OK saved';
    }


    /**
     * This function apply the provided time and date
     *
     * @param  string  date and time
     * @return string
     */
    static public function Config_saveDateTime($string)
    {
        $cmd = '/bin/date ' . escapeshellcmd($string);
        $res = `$cmd`;
        $res = preg_replace('/\n/', '', $res);
        return 'OK saved';
    }

    /**
     * This function will save some mailcleaner config option
     *
     * @param  array  options
     * @return string
     */
    static public function Config_saveMCConfigOption($options)
    {
        $configfile = '/etc/mailcleaner.conf';

        $txt = '';
        $found = [];
        if (file_exists($configfile)) {
            $content = file($configfile);
            foreach ($content as $line) {
                foreach ($options as $okey => $oval) {
                    if (preg_match("/^\s*" . $okey . "\s*=/", $line, $matches)) {
                        $line = $okey . " = " . $oval . "\n";
                        $found[$okey] = 1;
                    }
                }
                $txt .= $line;
            }
        }
        foreach ($options as $okey => $oval) {
            if (!isset($found[$okey])) {
                $txt .= $okey . " = " . $oval . "\n";
            }
        }

        $written = file_put_contents($configfile, $txt);
        if (!$written) {
            return 'NOK could not save config file';
        }
        return 'OK saved';
    }


    /**
     * This function will save and validate registration number
     *
     * @param  string  serial number
     * @return string
     */
    static public function Config_saveRegistration($serial)
    {
        return 'OK registered';
    }

    /**
     * This function will register this host
     *
     * @param  array   registration data
     * @return string
     */
    static public function Config_register($data)
    {
        if (!isset($data['clientid']) || !is_numeric($data['clientid'])) {
            return 'NOK bad clientid';
        }
        if (!isset($data['resellerid']) || !is_numeric($data['resellerid'])) {
            return 'NOK bad resellerid';
        }
        if (!isset($data['resellerpwd']) || $data['resellerpwd'] == '') {
            return 'NOK bad reseller password';
        }
        $pass = preg_replace('/[^a-zA-Z0-9]/', '', $data['resellerpwd']);

        if ($pass == '') {
            return 'NOK bad reseller password';
        }
        require_once('MailCleaner/Config.php');
        $sysconf = MailCleaner_Config::getInstance();
        $cmd = $sysconf->getOption('SRCDIR') . "/bin/register_mailcleaner.sh " . $data['resellerid'] . " " . $pass . " " . $data['clientid'] . " -b ";
        $res = `$cmd`;

        if (preg_match('/SUCCESS/', $res)) {
            return 'OK registered ' . $res;
        }
        return 'NOK ' . $res;
    }

    /**
     * This function will register this host as community edition
     *
     * @param  array   registration data
     * @return string
     */
    static public function Config_register_ce($data)
    {
        if (!isset($data['first_name']) || $data['first_name'] == '') {
            return 'NOK bad first_name';
        }
        if (!isset($data['last_name']) || $data['last_name'] == '') {
            return 'NOK bad last_name';
        }
        if (!isset($data['email']) || $data['email'] == '') {
            return 'NOK bad email';
        }
        require_once('MailCleaner/Config.php');
        $sysconf = MailCleaner_Config::getInstance();

        // Create file with data
        $DATA_FILE = "/tmp/mc_registerce.data";
        $rdata = fopen($DATA_FILE, "w") or die("NOK ERROR FILE CREATION");
        foreach ($data as $k => $v) {
            $txt = strtoupper($k) . "=" . $v . "\n";
            fwrite($rdata, $txt);
        }
        fclose($rdata);

        // Call the register_ce.sh script
        $cmd = $sysconf->getOption('SRCDIR') . "/bin/register_ce.sh -b";
        $res = `$cmd`;
        if (preg_match('/SUCCESS/', $res)) {
            return 'OK registered ' . $res;
        }
        return 'NOK ' . $res;
    }

    /**
     * This function will unregister this host
     *
     * @param  array of data for unregister this host
     * @return string
     */
    static public function Config_unregister($data)
    {
        require_once('MailCleaner/Config.php');
        $sysconf = MailCleaner_Config::getInstance();

        if (!isset($data['rsp'])) {
            $data['rsp'] = "--no-rsp";
        }

        $cmd = $sysconf->getOption('SRCDIR') . "/bin/unregister_mailcleaner.sh " . $data['rsp'] . " -b";
        $res = `$cmd`;
        if (preg_match('/SUCCESS/', $res)) {
            return 'OK registered ' . $res;
        }
        return 'NOK ' . $res;
    }


    /**
     * This function will set the host id
     *
     * @param  array of data for changing the host id
     * @return string
     */
    static public function Config_hostid($data)
    {
        require_once('MailCleaner/Config.php');
        $sysconf = MailCleaner_Config::getInstance();

        if (!isset($data['host_id']) || !preg_match('/^\d+$/', $data['host_id'])) {
            return "NOK You have to specify an integer for host_id";
        }

        $cmd = $sysconf->getOption('SRCDIR') . "/bin/change_hostid.sh " . $data['host_id'] . " -f";
        $res = `$cmd`;
        if (preg_match('/SUCCESS/', $res)) {
            return 'OK registered ' . $res;
        }
        return 'NOK ' . $res;
    }

    /**
     * This function will enable auto-configuration
     *
     * @param  array of data with a single boolean variable autoconfenabled
     * @return string
     */
    static public function Config_autoconfiguration($data)
    {
        require_once('MailCleaner/Config.php');
        $sysconf = MailCleaner_Config::getInstance();
        $mc_autoconf = $sysconf->getOption('VARDIR') . '/spool/mailcleaner/mc-autoconf';
        $msg = 'OK';
        if (isset($data['autoconfenabled']) && $data['autoconfenabled']) {
            if (!file_exists($mc_autoconf)) {
                $result = fopen($mc_autoconf, "w");
                if ($result) {
                    $msg = 'OK Auto-configuration enabled';
                }
                fclose($result);
            }
        } else {
            if (file_exists($mc_autoconf)) {
                $result = unlink($mc_autoconf);
                if ($result) {
                    $msg = 'OK Auto-configuration disabled';
                }
            }
        }

        return $msg;
    }


    /**
     * This function will download and set in one shot the reference configuration
     *
     * @param  array of data for download the auto-configuration
     * @return string
     */
    static public function Config_autoconfigurationDownload($data)
    {
        if (isset($data['download']) && $data['download']) {
            require_once('MailCleaner/Config.php');
            $sysconf = MailCleaner_Config::getInstance();
            $cmd_autoconf = $sysconf->getOption('SRCDIR') . '/bin/fetch_autoconf.sh';
            $res = `$cmd_autoconf`;

            // Check if the autoconf is downloaded then run prepare_sqlconf.sh
            if (file_exists($sysconf->getOption('SRCDIR') . '/etc/autoconf/prepare_sqlconf.sh')) {
                $cmd_prepare  = $sysconf->getOption('SRCDIR') . '/etc/autoconf/prepare_sqlconf.sh';
                $res = `$cmd_prepare`;
                // When updating the mc_config, need to restart MC
                $cmd = "touch " . $sysconf->getOption('VARDIR') . "/run/exim_stage1.rn";
                $cmd2 = "touch " . $sysconf->getOption('VARDIR') . "/run/mailscanner.rn";
                `$cmd`;
                `$cmd2`;
                return 'OK Configuration downloaded and set';
            }
        }

        return 'NOK Internal error';
    }
}
