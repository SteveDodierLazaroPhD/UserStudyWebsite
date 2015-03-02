<?php
namespace UCL\StudyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * UCL\StudyBundle\Entity\ParticipantTaskProgress
 *
 * @ORM\Table(name="study_participant_progress")
 */
class ParticipantTaskProgress
{
  /**
   * @ORM\ManyToMany(targetEntity="Participant", mappedBy="id")
   */
  private $participant;

  /**
   * @ORM\Column(type="string", length=255, unique=true)
   * @ORM\ManyToMany(targetEntity="Participant", mappedBy="currentPart")
   */
  private $part;

  /**
   * @ORM\ManyToMany(targetEntity="Participant", mappedBy="currentStep")
   */
  private $step;

  /**
   * @ORM\Column(name="task_progress", type="integer")
   */
  private $task_progress;
  
  public function getParticipant()
  {
    return $this->participant;
  }  
  
  public function setParticipant($participant)
  {
    $this->participant = $participant;
  }  
}
