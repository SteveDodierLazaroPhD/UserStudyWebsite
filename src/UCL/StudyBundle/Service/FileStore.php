<?php

namespace UCL\StudyBundle\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpKernel\Exception\HttpException;

use UCL\StudyBundle\Entity\Participant;


class FileStore
{
  /**
   * @brief MAX_BUFFER_LENGTH_CONTENT_CHECK: Arbitrary value beyond which the contents of a binary stream will not be examed for syntactic correctness
   */
  const MAX_BUFFER_LENGTH_CONTENT_CHECK = 2097152;
  
  /**
   * @brief VERIFY_CONTENTS: Whether to verify that a binary stream represents syntactically correct content
   */
  const VERIFY_CONTENTS = true;

  protected $fs;
  protected $store;

  public function __construct($store = "/tmp/store/")
  {
    $this->fs = new Filesystem();
    $this->store = $store;
    date_default_timezone_set('Europe/London');
  }

  protected function makeFileName($participantEmail, $extension)
  {
    $time = date('Y-m-d_h:i:s');
    return $time.'_'.$participantEmail.'_'.uniqid('', false).'.'.$extension;
  }

  function makeFullPath($filename)
  {
    if (!$this->fs->exists($this->store))
    {
      throw new IOException('The \''.$this->store.'\' storage directory is missing.', 0, null, $this->store);
    }
    $filepath = $this->store.'/'.$filename;
    return $filepath;
  }

  protected function makeArchivePath($filename)
  {
    if (!$this->fs->exists($this->store))
    {
      throw new IOException('The \''.$this->store.'\' storage directory is missing.', 0, null, $this->store);
    }
    $archiveFolder = $this->store.'/archive';
    if (!$this->fs->exists($archiveFolder))
    {
      throw new IOException('The \''.$this->store.'\' storage archive directory is missing.', 0, null, $archiveFolder);
    }
    $filepath = $archiveFolder.'/'.$filename;
    return $filepath;
  }
  
  function makeFile($content, $extension, $participantEmail)
  {
    $filename = $this->makeFileName($participantEmail, $extension);
    $filepath = $this->makeFullPath($filename);
    $this->fs->dumpFile($filepath, $content);
    return $filename;
  }
  
  function makeFileFromBinary($handle, $extension, $participantEmail)
  {
    if (empty($extension))
      $extension = "dat";

    $filename = $this->makeFileName($participantEmail, $extension);
    $filepath = makeFullPath($filename);

    $buffer = "";
    $fp = fopen($filepath, 'wb');
    while (!feof($handle))
    {
        $contents = fread($handle, 8192);
        fwrite($fp, $contents);
        
        if ($verifyContents and $length < MAX_BUFFER_LENGTH_CONTENT_CHECK)
          $buffer .= $contents;
    }
    $length = ftell($fp);
    fclose($handle);
    fclose($fp);

    if ($verifyContents and $length < MAX_BUFFER_LENGTH_CONTENT_CHECK)
    {
      if ($type == "json")
      {
        if (!json_decode($buffer, true))
          throw new UnexpectedValueException("The uploaded data does not appear to be using the JSON format.");
      }
    }
    
    return array ($filename, $length);
  }
  
  function moveFile($currentPath, $newPath)
  {
    $this->fs->rename($currentPath, $newPath);
    return $filename;
  }
  
  function storeExistingFile($currentPath, $extension, $participantEmail)
  {
    $filename = $this->makeFileName($participantEmail, $extension);
    $newPath = $this->makeFullPath($filename);
    return $this->moveFile($currentPath, $newPath);
  }
  
  function deleteFile($filename)
  {
    $filepath =$this->makeFullPath($filename);
    if (!$this->fs->exists($filepath))
    {
      throw new IOException('Cannot delete file \''.$filename.'\' as it was not found in the store.', 0, null, $filename);
    }
    
    $this->fs->remove($filename);
  }
  
  function archiveFile($filename)
  {
    $filepath =$this->makeFullPath($filename);
    if (!$this->fs->exists($filepath))
    {
      throw new IOException('Cannot archive file \''.$filename.'\' as it was not found in the store.', 0, null, $filename);
    }
    
    $archivepath = makeArchivePath($filename);
    if (!$this->fs->exists($filepath))
    {
      throw new IOException('Cannot archive file \''.$filename.'\' as it was not found in the store.', 0, null, $filename);
    }
    
    return $this->moveFile($currentPath, $newPath);
  }
  
  function getHandle($filename)
  {
    $filepath =$this->makeFullPath($filename);
    if (!$this->fs->exists($filepath))
    {
      throw new IOException('Cannot give handle to file \''.$filename.'\' as it was not found in the store.', 0, null, $filename);
    }
    
    return fopen($filepath, 'ab');
  }
}
?>
