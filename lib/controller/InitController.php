<?php


class InitController extends Controller
{
    public function actionIndex($params)
    {
        echo "Lütfen size verilen izin anahtarını yazınız: ";
        again:
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        $key = trim($line);
        $curl = Curl::post("check-key", ['key' => $key]);
        if (!$curl->success) {
            echo "\nGeçersiz anahtar, lütfen tekrar deneyin: ";
            goto again;
        }
        if (file_put_contents($this::getPath('temp') . '/comer.pem', $key)) {
            echo "Teşekkürler, anahtarınız başarıyla oluşturuldu.\n";
        } else {
            echo "Hata aldık, yazma izinlerini kontrol edin!\n";
        }
        fclose($handle);
        echo "\n";
    }

}