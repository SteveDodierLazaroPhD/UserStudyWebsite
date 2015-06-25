<?php
namespace UCL\StudyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="study_stepprogress")
 * Uploaded files can be up to 2.147GB large on 32 bit servers.
 */
class StepProgress
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
   * @ORM\Column(type="integer", name="progress", nullable=false, options={"unsigned":true, "default":0, "comment":"Generic counter representing progress made on a task"})
   */
  protected $progress;


  /*
   * @ORM\Column(type="string", name="counter_name", nullable=false, options={"comment":"Name of the stuff being counted. You must implement per-locale methods to manipulate this word, as not all languages have a simple singular/plural structure for words"})
   */
  //protected $counterName;

  function __construct (Participant $participant, $part, $step, $progress = 0)//, $counterName = "day")
  {
    $this->participant = $participant->getId();
    $this->part = $part;
    $this->step = $step;
    $this->progress = $progress;
    //$this->counterName = $counterName;
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

  public function getProgress()
  {
      return $this->progress;
  }

  public function setProgress($progress)
  {
      $this->clearPreviousFile();
      $this->progress = $progress;
  }

  /*public function getCounterName()
  {
      return $this->counterName;
  }

  public function setCounterName($counterName)
  {
      $this->counterName = $counterName;
  }*/
}

?>

