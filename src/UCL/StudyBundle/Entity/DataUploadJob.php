<?php
namespace UCL\StudyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="study_datauploadjob")
 * Uploaded files can be up to 2.147GB large on 32 bit servers.
 */
class DataUploadJob
{
  /**
   * @ORM\Column(type="string", length=255)
   * @ORM\Id
   * @ORM\ManyToOne(targetEntity="Participant")
   * @ORM\JoinColumn(name="participant_id", referencedColumnName="id")
   */
  protected $participant;

  /**
   * @ORM\Column(type="integer", name="part", nullable=false, options={"unsigned":true, "default":0})
   * @ORM\Id
   */
  protected $part;
  /**
   * @ORM\Column(type="string", name="step", length=255, nullable=false, options={"default":"invalid"})
   * @ORM\Id
   */
  protected $step;

  /**
   * @ORM\Column(type="string", length=255, options={"comment":"Name of the file in the store, if known"})
   */
  protected $filename;

  /**
   * @ORM\Column(type="integer", name="day_count", nullable=false, options={"unsigned":true, "default":0, "comment":"Number of days of collected data in the currently uploaded tarball"})
   */
  protected $dayCount;

  /**
   * @ORM\Column(type="integer", name="expected_size", nullable=false, options={"unsigned":true, "default":0, "comment":"Expected amount of bytes as advertised by the client"})
   */
  protected $expectedSize;

  /**
   * @ORM\Column(type="integer", name="obtained_size", nullable=false, options={"unsigned":true, "default":0, "comment":"Obtained amount of bytes by the server so far"})
   */
  protected $obtainedSize;
  
  /**
   * @ORM\Column(type="string", name="checksum", nullable=true, length=64, options={"comment":"A checksum of the complete file for a final verification"})
   */
  protected $checksum;
  

  protected function clearPreviousFile()
  {
    //TODO
  }

  function reset (Participant $participant, $part, $step, $dayCount)
  {
    $this->participant = $participant->getId();
    $this->part = $part;
    $this->step = $step;
    $this->setFilename(null);
    $this->dayCount = $dayCount;
    $this->expectedSize = 0;
    $this->obtainedSize = 0;
    $this->checksum = null;
  }
  function __construct ($participant, $part, $step, $dayCount = 0)
  {
    //$this->file  = null;
    $this->reset($participant, $part, $step, $dayCount);
  }

  public function getParticipant()
  {
      return $this->participant;
  }

  public function setParticipant($participant)
  {
      $this->participant = $participant;
  }

  public function getPart()
  {
      return $this->part;
  }

  public function setPart($part)
  {
      $this->part = $part;
  }

  public function getStep()
  {
      return $this->step;
  }

  public function setStep($step)
  {
      $this->step = $step;
  }

  public function getFilename()
  {
      return $this->filename;
  }

  public function setFilename($filename)
  {
      $this->clearPreviousFile();
      $this->filename = $filename;
  }

  public function getDayCount()
  {
      return $this->dayCount;
  }

  public function setDayCount($dayCount)
  {
      $this->dayCount = $dayCount;
  }

  public function getExpectedSize()
  {
      return $this->expectedSize;
  }

  public function setExpectedSize($expectedSize)
  {
      $this->expectedSize = $expectedSize;
  }

  public function getObtainedSize()
  {
      return $this->obtainedSize;
  }

  public function setObtainedSize($obtainedSize)
  {
      $this->obtainedSize = $obtainedSize;
  }

  public function getChecksum()
  {
      return $this->checksum;
  }

  public function setChecksum($checksum)
  {
      $this->checksum = $checksum;
  }
}

?>
