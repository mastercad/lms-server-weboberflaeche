<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class NetworkScanner
{
    private $range;
    private $port;
    private $subNet;
    private $logger;
    private $localIp;

    public function __construct(LoggerInterface $logger)
    {
        $this->subNet = $_ENV['SCAN_SUBNET'];
        $this->range = $_ENV['SCAN_RANGE'];
        $this->port = $_ENV['SCAN_PORT'];
        $this->logger = $logger;
    }

    public function scan()
    {
        $scanCommand = "/usr/bin/nmap ".$this->subNet.".".$this->range." -p".$this->port." -oG - | awk '/.*".$this->port."\/open/{print $2}'";
        $this->localIp = exec($scanCommand, $knownHosts, $result);

        if ($result) {
            $this->logger->error("IP Adressen konnten nicht gescannt werden! FehlerCode: ".$result);
            return [];
        }
        
        return $this->extendKnownHostsInformation($knownHosts);
    }

    private function extendKnownHostsInformation($knownHosts)
    {
        $hosts = [];
        foreach ($knownHosts as $host) {
            if ($host != $this->localIp) {
                $hosts[] = [
                    'IP' => $host,
                    'PORT' => $this->port
                ];
            }
        }

        return $hosts;
    }
}