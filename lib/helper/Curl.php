<?php

/**
 * Class Curl
 * @property boolean $success
 * @property array $list
 */
class Curl
{
    const API = 'http://coder.comer.pw/coder/api/';

    /**
     * @param $action
     * @param array $data
     * @return Curl
     */
    public static function post($action, $data = [], $dataType = 'json')
    {
//        if ($data) {
//            print_r($data);exit;
//        }
        if (!isset($data['key'])) {
            $pemPath = Controller::getPath('temp') . '/comer.pem';
            if (file_exists($pemPath)) {
                $data['key'] = file_get_contents($pemPath);
            }
        }
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, self::API . $action);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $server_output = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if ($info["http_code"] == 403) {

            $tempFolder = __DIR__ . '/../temp';
            $tokenFile = $tempFolder . '/.token';
            unlink($tokenFile);
            echo "Oturmunuz sonlanmış, lütfen tekrar çalıştırıp giriş yapın.\n";
            exit();
        }
        if ($info["http_code"] !== 200) {
            echo "Server response: \n";
            print_r($server_output);
            echo "\n";
            echo "\n";
            exit("Bağlantınızı kontrol edin.");
        }

        if ($dataType === 'json') {
            return json_decode($server_output, true);
        }

        return $server_output;
    }

}