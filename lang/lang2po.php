<?php

class GenPo
{    
    protected $enable_translate = false;
    protected $new_translate_dirname = 'cn';
    
    public function __construct()
    {
        date_default_timezone_set('UTC');
    }
    
    /*
     *获取默认语言的语言包文件列表
     */
    protected function getDefaultFileList()
    {
        $file_list = array();

        // 循环获取 language/en/ 目录里所有 *.ini 文件
        $it = new DirectoryIterator("glob://language/en/*.ini");
        foreach($it as $f) {
            $file_path = $f->getPathname();
            $file_list[] = $file_path;
        }
        $it = new DirectoryIterator("glob://language/en/admin/*.ini");
        foreach($it as $f) {
            $file_path = $f->getPathname();
            $file_list[] = $file_path;
        }
        
        return $file_list;
    }
    
    public function genEnglishPoFile()
    {        
        $file_list = $this->getDefaultFileList();
        print_r($file_list);
        foreach ($file_list as $file_path) {
            $this->genPoFile($file_path);
        }
    }
    
    public function genChinesePoFile()
    {    
        //标记这是从默认语言文件翻译而成
        $this->genTranslatePoFile('cn', 'zh_CN', 'Chinese');
            
        $file_list = $this->getDefaultFileList();
        //print_r($file_list);
        foreach ($file_list as $file_path) {
            $this->genPoFile($file_path, 'unreal', 'zh_CN', 'Chinese');
        }
    }
    
    protected function genTranslatePoFile($path = 'cn', $lang = 'zh_CN', $lang_name = 'Chinese')
    {
        $this->enable_translate = true;
        $this->new_translate_dirname = trim($path);
    }

    protected function genPoFile($file_path, $project_name = 'unreal', $lang = 'en_US', $lang_name = 'English')
    {
        $vars = parse_ini_file($file_path);
        
        if ($this->enable_translate == true) {
            $file_path = str_replace('/en/', '/'.$this->new_translate_dirname.'/', $file_path);
            $vars_translate = parse_ini_file($file_path);
        }
    
        $time = date('Y-m-d h:i:s+0000');
        $po_header = 'msgid ""'."\n".
        'msgstr ""'."\n".
        '"Project-Id-Version: '.$project_name.'\n"'."\n".
        '"Content-Type: text/plain; charset=UTF-8\n"'."\n".
        '"Language-Team: '.$lang_name.'\n"'."\n".
        '"Language: '.$lang.'\n"'."\n".
        '"Plural-Forms: nplurals=1; plural=0;\n"'."\n".
        '"X-Generator: xssworm.com\n"'."\n".
        '"Last-Translator: xssworm <translate@xssworm.com>\n"'."\n".
        '"PO-Revision-Date: '.$time.'\n"'."\n";
    
        $text = '';
        foreach($vars as $msgctxt => $msgid) {
        
            $msgid = str_replace('"', '\"', $msgid);
            $msgid = str_replace('\\"', '\"', $msgid);
            $msgid = str_replace(array("\r\n", "\n", "\r"), '', $msgid);
            
            $msgstr = '';
            
            if ($this->enable_translate == true) {
                if (isset($vars_translate[$msgctxt]) &&!empty($vars_translate[$msgctxt])) {
                    $msgstr = $vars_translate[$msgctxt];
                } else {
                    $msgstr = $msgid;
                }
            } else {
                $msgstr = $msgid;
            }    
        
            $text .= "\n".'msgctxt "'.$msgctxt.'"'."\n".'msgid "'.$msgid.'"'."\n".'msgstr "'.$msgstr.'"'."\n";
        
        }

        $text = $po_header.$text;

        //获取后缀
        $slice_file_path = explode('.', $file_path);
        $new_file_path = substr($file_path, 0, -strlen(end($slice_file_path)));
        $new_file_path = $new_file_path.'po';
        //echo $new_file_path;
    
        $fp = fopen($new_file_path, 'w+');
        fwrite($fp, $text);
        fclose($fp);
    
    }
}


$a = new GenPo();
//$a->genEnglishPoFile();
$a->genChinesePoFile();