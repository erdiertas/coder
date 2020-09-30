<?php


class PushController extends Controller
{
    public function actionIndex()
    {
        $list = $this->getCacheAllowList();
        $noChanges = true;

        foreach ($list as $file) {
            $old_version = self::PATH_TEMP_PROJECTS . $file;
            $new_version = self::PATH_PROJECTS . $file;
            if (file_exists($new_version)) {
                if (@hash_file('md5', $old_version) != @hash_file('md5', $new_version)) {
                    $noChanges = false;
                    echo $new_version . " dosyası gönderiliyor... \n";
                    $content = file_get_contents($new_version);
                    $push = Curl::post("push-file", ['path' => $file, 'content' => $content]);
                    $this->putTemp($file, $content);
                    if ($push->success) {
                        echo "$file gönderildi.\n";
                    } else {
                        echo " gönderme başarısız!\n";
                    }
                }
            }
        }

        if ($noChanges) {
            echo "\nHerhangi bir değişiklik algılanmadı! \n";
        } else {
            echo "\nAktarma tamamlandı! \n";
        }
        echo "\n";
    }
}