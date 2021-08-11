<?php


namespace GrowthElements;

class SMLog
{
    const URL = "https://logs.smoothorders.com";
    const ERROR = "ERROR";
    const INFO = "INFO";
    const DEBUG = "DEBUG";
    const SUCCESS = "SUCCESS";
    const CRITICAL = "CRITICAL";
    const START = "START";
    const PING = "PING";

    private $AppId = "";
    private $AppName = "";
    private $AppDisplayToConsole = false;
    private $AppShowError = false;
    private $AppIsDebug = false;

    public function __construct($appId, $appName, $appDisplayToConsole = false, $appShowError = false, $appIsDebug = false)
    {
        $this->AppId = $appId;
        $this->AppName = $appName;
        $this->AppDisplayToConsole = $appDisplayToConsole;
        $this->AppShowError = $appShowError;
        $this->AppIsDebug = $appIsDebug;
    }

    private function checkForValidStatus($status) {
        if ($status != self::DEBUG || $status != self::ERROR || $status != self::INFO || $status != self::SUCCESS || $status != self::CRITICAL || $status != self::START || $status != self::PING) {
            return false;
        } else {
            return true;
        }
    }

    public function add($type, $module, $contents)
    {
        if (!$this->checkForValidStatus($type) && $this->AppShowError) {
            var_dump("GE:SMLOG - Invalid status" . $type." Please check again.");
        }

        if ($type === self::DEBUG && !$this->AppIsDebug) {
            return true;
        }

        $json = [
            'contents' => $contents,
            'module' => $module,
            'type' => $type
        ];

        $ch = curl_init(self::URL);
        if ($this->AppShowError) {
            curl_setopt($ch, CURLOPT_VERBOSE, true);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json', 'X-Debug-Name:' . $this->AppName, 'X-Token:' . $this->AppId]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);

        //Curl Error
        if (curl_errno($ch) && $this->AppShowError) {
            var_dump(curl_error($ch));
        }

        $resJson = json_decode($result);
        //JSON Error
        if (json_last_error() != JSON_ERROR_NONE && $this->AppShowError) {
            var_dump($resJson);
        } else if ($this->AppDisplayToConsole) {
            var_dump($resJson);
        }
        curl_close($ch);

        return true;
    }

}
