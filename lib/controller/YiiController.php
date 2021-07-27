<?php


class YiiController extends Controller
{

    public function actionIndex($params)
    {
        $this->login();

        echo "\n";
        $command = '';
        $projectName = null;
        if (isset($params[0])) {
            $projectName = $params[0];
            unset($params[0]);
        } else {
            echo "İlk parametre olarak proje adını giriniz! \n\n";
            exit();
        }
        $command = implode(" ", $params);
        echo $this->showWelcomeMessage();

        echo "$projectName projesine bağlanılıyor...\n\n";


        if ($projectName) {
            $response = Curl::post("yii", [
                'token' => $this->token,
                'projectName' => $projectName,
                'command' => $command
            ]);
            echo $response['message'];
            echo "\n\n";
        } else {
            echo "İlk parametre olarak proje adını giriniz!\n\n";
        }


    }


}