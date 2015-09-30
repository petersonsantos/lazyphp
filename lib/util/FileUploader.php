<?php
class FileUploader {

    public $name;
    public $path;
    private $file = NULL;
    public $erro = NULL;

   /**
    * 
    * @param array $file $_POST do arquivo
    * @param type $maxsize tamanho máximo em bytes
    * @param array $extensions extesões permitidas
    * @throws Exception
    */    
    public function __construct($file, $maxsize = NULL, Array $extensions = NULL) {
        if (is_uploaded_file($file['tmp_name'])) {
            if (!is_null($maxsize) && $file["size"] > $maxsize) {
                throw new Exception(__('O arquivo é muito grande.'));
                return;
            }

            if (!is_null($extensions)) { 
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $valid = false;
                foreach ($extensions as $mime) {
                    if ($ext == $mime) {
                        $valid = TRUE;
                        break;
                    }
                }
                if (!$valid){
                    throw new Exception(__('Tipo de arquivo não permitido.'));
                    return;
                }
            }
            $this->file = $file;
        }
    }

    /**
     * Salva o arquivo.
     * 
     * @param String $filename novo nome
     * @param String $path nome do diretório onde irá salvar (pasta Upload)
     * @return boolean
     * @throws Exception
     */
    function save($filename, $path) {
        if (is_null($this->file)){
            throw new Exception(__('Arquivo não enviado.'));
            return false;
        }

        $this->path = 'uploads/' . $path;
        $this->name = $filename;
        if (!is_dir($this->path) && !file_exists($this->path))
            mkdir($this->path, 0, true);
        
        if (!move_uploaded_file($this->file['tmp_name'], $this->path . '/' . $filename)) {
            throw new Exception(__('Arquivo não enviado.'));
            return false;
        }
        return true;
    }

}

?>
