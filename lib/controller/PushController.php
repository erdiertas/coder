<?php


class PushController extends Controller
{
    public function actionIndex()
    {
        $list = $this->getCacheAllowList();

        foreach ($list as $file) {
            $old_version = $this::getPath('/temp/projects' . $file);
            $new_version = $this::getPath('../projects' . $file);
            if (@hash_file('md5', $old_version) != @hash_file('md5', $new_version)) {
                echo $new_version . " dosyası gönderiliyor... \n";
                $content = file_get_contents($new_version);
                $push = Curl::post("push-file", ['path' => $file, 'content' => $content]);
                $this->putTemp('/projects' . $file, $content);
                if ($push->success) {
                    echo "$file gönderildi.\n";
                }else{
                    echo " gönderme başarısız!\n";
                }
            }
        }
    }
}