<?php
namespace App\Aggregation;

/**
 * DeviceLog Aggregator
 *
 * Bundles the devicelogs in blocks when the device was available
 *
 * @author Tim de Pater <code@trafex.nl>
 */
class DeviceLogs
{
    /**
     * Aggregate device logs
     *
     * @param array $deviceLogs
     * @param int $offlineGap
     * @return array
     */
    public function aggregate($deviceLogs, $offlineGap)
    {
        if (0 === count($deviceLogs)) {
            return array();
        }
        $results = array();
        $lastDate = array();
        $curDevice = null;
        $i = null;
        foreach ($deviceLogs as $deviceLog) {
            if ($curDevice !== $deviceLog->getDevice()->getMacAddress()) {
                // New device found

                if (null !== $i && !isset($results[$curDevice][$i]['end'])) {
                    // Set the endtime of the previous device
                    $results[$curDevice][$i]['end'] = $lastDate;
                }

                $curDevice = $deviceLog->getDevice()->getMacAddress();
                $lastDate = $deviceLog->getDate();
                $i = 0;
                $results[$curDevice][$i] = array();
                $results[$curDevice][$i]['start'] = $deviceLog->getDate();
                $results[$curDevice][$i]['ip'] = $deviceLog->getIp();
                $results[$curDevice][$i]['device'] = sprintf(
                    '%s (%s)',
                    $deviceLog->getDevice()->getMacAddress(),
                    $deviceLog->getDevice()->getVendor()
                );
            }

            $timeDiff = strtotime($deviceLog->getDate()->format('Y-m-d H:i:s'))
                - strtotime($lastDate->format('Y-m-d H:i:s'));

            if ($timeDiff > $offlineGap) {
                $results[$curDevice][$i]['end'] = $lastDate;
                // This time block is ended, increase $i to start a new one
                $i++;
                $results[$curDevice][$i]['start'] = $deviceLog->getDate();
                $results[$curDevice][$i]['device'] = sprintf(
                    '%s (%s)',
                    $deviceLog->getDevice()->getMacAddress(),
                    $deviceLog->getDevice()->getVendor()
                );
                $results[$curDevice][$i]['ip'] = $deviceLog->getIp();
            }
            $lastDate = $deviceLog->getDate();
        }
        if (!isset($results[$curDevice][$i]['end'])) {
            $results[$curDevice][$i]['end'] = $lastDate;
        }
        return $results;
    }
}
