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
    private $DefaultType = "";
    private $Destination = "";
    private $HasBeenInit = false;


    /**
     * Pass variables to allow SMLog to init. Required.
     *
     * @return void
     */
    public function __construct($appId, $appName, $appDisplayToConsole = false, $appShowError = false, $appIsDebug = false, $defaultType = self::ERROR, $destination = "")
    {
        $this->AppId = $appId;
        $this->AppName = $appName;
        $this->AppDisplayToConsole = $appDisplayToConsole;
        $this->AppShowError = $appShowError;
        $this->AppIsDebug = $appIsDebug;
        $this->DefaultType = $defaultType;
        if ($destination) {
            $this->Destination = $destination;
        } else {
            $this->Destination = self::URL;
        }
        $this->HasBeenInit = true;
    }

    private function checkForValidStatus($status)
    {
        if ($status != self::DEBUG && $status != self::ERROR
            && $status != self::INFO && $status != self::SUCCESS
            && $status != self::CRITICAL && $status != self::START
            && $status != self::PING) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * $var1 = Type, $var2 = Module, $var3 = Contents
     * if $var1 only, then $var1 will be Contents, Type will be default to ERROR and Module will be self generated from function call
     * if $var1 and $var2, then $var1 will be Type, $var2 will be Contents, Module will be self generated from function call
     * if all three exists, then it will be Type, Module, Contents in this order
     *
     * @param $var1 string
     * @param $var2 string
     * @param $var3 string
     *
     * @return void
     */
    public function add($var1, $var2 = "", $var3 = "") //type, module, contents
    {
        $content = "";
        $type = "";
        $status = "";
        $detailedInfo = "";

        if (!$this->HasBeenInit) {
            var_dump("Need to be init first.");
            return;
        }

        if (!$var1) {
            var_dump("GE:SMLOG - No content. Please check again.");
            return;
        }
        $dbt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = isset($dbt[1]['function']) ? $dbt[1]['function'] : null;

        if ($var1 && $var2 && $var3) {
            $type = $var1;
            $module = $var2;
            $content = $var3;
        } else if ($var1 && $var2) {
            $content = $var2;
            $type = $var1;
            $module = $caller;
        } else if ($var1) {
            $content = $var1;
            $type = $this->DefaultType;
            $module = $caller;
        }

        $detailedInfo = $this->GetCallingMethodName();

//
//        if (!$this->checkForValidStatus($var1) && $this->AppShowError) {
//            var_dump("GE:SMLOG - Invalid status" . $type . " Please check again.");
//        }


        if ($type === self::DEBUG && !$this->AppIsDebug) {
            return;
        }

        $json = [
            'contents' => $content,
            'module' => $module,
            'status' => $type,
            'package' => $detailedInfo,
            'platform'=> 'PHP '.phpversion()
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

    function GetCallingMethodName(){
        $e = new \Exception();
        $trace = $e->getTrace();
        //position 0 would be the line that called this function so we ignore it
        $last_call = $trace[1];
        return $last_call;
    }
}
