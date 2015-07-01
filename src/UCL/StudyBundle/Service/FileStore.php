<?php

namespace UCL\StudyBundle\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\DependencyInjection\ContainerAware;

class FileStore extends ContainerAware
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
  protected $translator;

  public function __construct($store = "/tmp/store/", $translator)
  {
    $this->fs = new Filesystem();
    $this->store = $store;
    $this->translator = $translator;
    date_default_timezone_set('Europe/London'); //FIXME use global config for that
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
      throw new IOException($this->translator->trans('The \'%name%\' storage directory is missing.', array('%name%', $this->store)), 0, null, $this->store);
    }
    $filepath = $this->store.'/'.$filename;
    return $filepath;
  }

  protected function makeArchivePath($filename)
  {
    if (!$this->fs->exists($this->store))
    {
      throw new IOException($this->translator->trans('The \'%name%\' storage directory is missing.', array('%name%', $this->store)), 0, null, $this->store);
    }
    $archiveFolder = $this->store.'/archive';
    if (!$this->fs->exists($archiveFolder))
    {
      throw new IOException($this->translator->trans('The \'%name%\' archive storage directory is missing.', array('%name%', $this->store)), 0, null, $archiveFolder);
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
    
    $verifyContents = true; /* FIXME this might turn ugly */

    $filename = $this->makeFileName($participantEmail, $extension);
    $filepath = $this->makeFullPath($filename);

    $buffer = "";
    $fp = fopen($filepath, 'wb');
    $length = 0;
    while (!feof($handle))
    {
        $contents = fread($handle, 8192);
        fwrite($fp, $contents);
        $length += 8192; // grossly overestimate for now
        
        if ($verifyContents && $length < FileStore::MAX_BUFFER_LENGTH_CONTENT_CHECK)
          $buffer .= $contents;
    }
    $length = ftell($fp);
    fclose($handle);
    fclose($fp);

    if ($verifyContents && $length < FileStore::MAX_BUFFER_LENGTH_CONTENT_CHECK)
    {
      if ($extension == "json")
      {
        if (!json_decode($buffer, true))
          throw new UnexpectedValueException($this->translator->trans('The uploaded data does not appear to be using the JSON format.'));
      }
    }
    
    return array ($filename, $length);
  }
  
  function moveFile($currentPath, $newPath)
  {
    $this->fs->rename($currentPath, $newPath);
    //TODO check it worked!
    return $newPath;
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
      throw new IOException($this->translator->trans('Cannot delete file \'%name%\' as it was not found in the store.', array('%name%', $filename)), 0, null, $filename);
    }
    
    $this->fs->remove($filename);
  }
  
  function archiveFile($filename)
  {
    $filepath =$this->makeFullPath($filename);
    if (!$this->fs->exists($filepath))
    {
      throw new IOException($this->translator->trans('Cannot archive file \'%name%\' as it was not found in the store.', array('%name%', $filename)), 0, null, $filepath);
    }

    $archivepath = $this->makeArchivePath($filename);
    return $this->moveFile($filepath, $archivepath);
  }
  
  function getHandle($filename)
  {
    $filepath =$this->makeFullPath($filename);
    if (!$this->fs->exists($filepath))
    {
      throw new IOException($this->translator->trans('Cannot given handle to file \'%name%\' as it was not found in the store.', array('%name%', $filename)), 0, null, $filename);
    }
    
    return fopen($filepath, 'ab');
  }
}
?>
