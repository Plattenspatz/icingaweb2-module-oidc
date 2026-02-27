<?php

namespace Icinga\Module\Oidc;

class FileHelper
{
    protected $path = '';
    protected $allowSymlinks = true;

    public function __construct($path, $allowSymlinks = true)
    {
        $this->path=$path;
        $this->allowSymlinks = (bool) $allowSymlinks;
    }

    public function fetchFileList(){
        $directory = $this->path;
        if(! file_exists($directory)){
            return [];
        }
        $files  = scandir($directory);

        $files = array_diff($files, array('.', '..'));

        $files = array_filter($files, function($file) use ($directory) {
            return is_file($directory .DIRECTORY_SEPARATOR.$file) || is_link($directory .DIRECTORY_SEPARATOR.$file);
        });

        return $files;
    }
    public function filelistAsSelect(){
        $result =[];
        $files= $this->fetchFileList();
        foreach ($files as $file){
            $result[$file]=$file;
        }
        return $result;
    }

    public function getFile($fileToGet)
    {
        $allFiles = $this->fetchFileList();

        if (! is_string($fileToGet) || $fileToGet === '' || strpos($fileToGet, "\0") !== false) {
            return false;
        }
        if (strpos($fileToGet, DIRECTORY_SEPARATOR) !== false || strpos($fileToGet, '/') !== false || strpos($fileToGet, '\\') !== false) {
            return false;
        }

        if (! in_array($fileToGet, $allFiles, true)) {
            return false;
        }

        $baseDir = $this->getBaseDirRealPath();
        if ($baseDir === false) {
            return false;
        }

        $entryPath = $baseDir . DIRECTORY_SEPARATOR . $fileToGet;

        if (is_link($entryPath) && ! $this->allowSymlinks) {
            return false;
        }

        $realPath = realpath($entryPath);
        if ($realPath === false) {
            return false;
        }

        if (! $this->isPathInsideBaseDir($realPath, $baseDir)) {
            return false;
        }

        return [
            'path'     => $entryPath,
            'realPath' => $realPath,
            'size'     => filesize($realPath),
            'name'     => $fileToGet,
            'isLink'   => is_link($entryPath),
        ];
    }

    private function getBaseDirRealPath()
    {
        $base = realpath($this->path);
        if ($base === false) {
            return false;
        }

        return rtrim($base, DIRECTORY_SEPARATOR);
    }

    private function isPathInsideBaseDir($path, $baseDir)
    {
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $path = rtrim($path, DIRECTORY_SEPARATOR);

        return strpos($path . DIRECTORY_SEPARATOR, $baseDir) === 0;
    }
}
