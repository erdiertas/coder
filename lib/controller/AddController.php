<?php


class AddController extends Controller
{
    public function actionIndex($params)
    {
        $pwd = $_SERVER["PWD"];
        $addFiles = [
            $pwd . '/' . $params['2']
        ];
        foreach ($addFiles as $addFile) {
            if (file_exists($addFile)) {
                echo $addFile . " dosyası ekleniyor... \n";
                $content = file_get_contents($addFile);
                $coderFile = FileHelper::pathToCoderPath($addFile);
                $add = Curl::post("add-file", ['path' => $coderFile, 'content' => $content]);
                if ($add->success) {
                    echo "$coderFile eklendi.\n";
                } else {
                    echo " ekleme başarısız!\n";
                }
            } else {
                echo "$addFile dosyası bulunamadı!";
            }
        }
    }

}