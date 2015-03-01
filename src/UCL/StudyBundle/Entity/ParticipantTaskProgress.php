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
class ParticipantTaskProgress implements UserInterface, AdvancedUserInterface, \Serializable
{
  /**
   * @ORM\Column(type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  private $id;

  /**
   * @ORM\ManyToMany(targetEntity="Participant", mappedBy="id")
   */
  private $participant;

  /**
   * @ORM\Column(type="string", length=255, unique=true)
   * @ORM\ManyToMany(targetEntity="StudyPart", mappedBy="id")
   */
  private $part;

  /**
   * @ORM\ManyToMany(targetEntity="StudyStep", mappedBy="part,name")
   */
  private $step;

  /**
   * @ORM\Column(name="task_progress", type="integer")
   */
  private $task_progress;
}
