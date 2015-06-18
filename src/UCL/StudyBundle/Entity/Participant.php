<?php
namespace UCL\StudyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;

use Symfony\Component\Validator\Constraints as Assert;

/* A quick note to sane programmers... the property names of this class make little sense (username, isvalid, password)
   but they're hard-coded into the Symfony UserInterface. username is the nickname/pseudonym chosen by the participant,
   isvalid is used to distinguish participants who are accepted into the study from those who have not been screened yet,
   and password is the participant code we'll give to screened participants (or generate when no screening is performed).
 */

define("PARTICIPANT_NOT_STARTED_YET", 0); // Initial state
define("PARTICIPANT_DONE", -1);           // Getting the last state number would be difficult because of Symfony's structure, so set this when participant's done
define("PARTICIPANT_INVALID", -2);        // We can use this as a catch-all state for participants we need to lock out

/* Again, Symfony forces us to be very careful. DO NOT MODIFY these constants without also modifying the validation
   annotation of the currentStep variable. We would need at some point to build up the annotation in PHP to remove
   this duplication. Also, you do not need to use all states. Sometimes you have no briefing/install actions, etc. */
define("PARTICIPANT_WAITING_ENROLLMENT", "waiting_enrollment");     // Waiting for researcher to decide whether to recruit
define("PARTICIPANT_MUST_CONSENT",       "consent");                // Must collect informed consent for this part to start
define("PARTICIPANT_READY_BRIEFING",     "briefing");               // Must arrange briefing meeting with participant
define("PARTICIPANT_MUST_START",         "install");                // Must have participant install study software alone
define("PARTICIPANT_IS_RUNNING",         "running");                // Running the study
define("PARTICIPANT_PRIMARY_TASK",       "primary_task");           // Performing some primary task
define("PARTICIPANT_READY_DEBRIEFING",   "debriefing");             // Must meet for debriefing
define("PARTICIPANT_FINISHED_PART",      "done");                   // Must pay participant and switch to next part based on communication with her

/**
 * UCL\StudyBundle\Entity\Participant
 *
 * @ORM\Table(name="study_participants")
 * @ORM\Entity(repositoryClass="UCL\StudyBundle\Entity\ParticipantRepository")
 * @UniqueEntity(
 *     fields={"email"},
 *     message="participant.email.already_used"
 * )
 */
class Participant implements UserInterface, AdvancedUserInterface, EquatableInterface, \Serializable
{
  /**
   * @ORM\Column(type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  private $id;

  /**
   * @ORM\Column(type="string", length=255, unique=true)
   */
  private $username;

  /**
   * @ORM\Column(type="string", length=255, unique=true)
   */
  private $email;

  /**
   * @ORM\Column(type="string", length=255)
   */
  private $password;

  /**
   * @ORM\Column(name="is_active", type="boolean")
   */
  private $active;

  /**
   * @ORM\Column(name="current_status", type="string", length=25)
   * @Assert\Choice(
   *     choices = { "consent", "briefing", "install", "running", "debriefing", "done" },
   *     message = "participant.status.invalid"
   * )
   */
  private $currentStep;

  /**
   * @ORM\Column(name="current_part", type="integer")
   */
  private $currentPart;

  public function __construct($active = true, $username = null, $email = null, $password = null, $part = PARTICIPANT_NOT_STARTED_YET, $step = PARTICIPANT_WAITING_ENROLLMENT, $id = 0)
  {
    $this->active = $active;
    $this->currentPart = $part;
    $this->currentStep = $step;
    $this->username = $username;
    $this->email = $email;
    $this->password = $password;
    $this->id = $id;
  }
  
  //FIXME find out why these methods are never used anywhere.
  public function isAccountNonExpired()
  {
    return $this->currentPart != PARTICIPANT_DONE || $this->currentStep != PARTICIPANT_FINISHED_PART;
  }

  public function isAccountNonLocked()
  {
    return $this->currentPart != PARTICIPANT_INVALID;
  }

  public function isCredentialsNonExpired()
  {
    return true;
  }

  public function isEnabled()
  {
    return $this->currentPart != PARTICIPANT_NOT_STARTED_YET && $this->active;
  }

  /**
   * @inheritDoc
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * @inheritDoc
   */
  public function getUsername()
  {
    return $this->username;
  }

  /**
   * @inheritDoc
   */
  public function getSalt()
  {
    /* bcrypt does that for us, allegedly. if you changed bcrypt and want to update this, look up
       http://symfony.com/doc/2.6/cookbook/security/entity_provider.html for other places where 
       the salt must be managed */
    return null;
  }

  /**
   * @inheritDoc
   */
  public function getPassword()
  {
    return $this->password;
  }

  /**
   * @inheritDoc
   */
  public function getRoles()
  {
    return array('ROLE_USER');
  }

  /**
   * @inheritDoc
   */
  public function eraseCredentials()
  {
  }

  /**
   * @see \Serializable::serialize()
   */
  public function serialize()
  {
    return serialize(array(
      $this->id,
      $this->username,
      $this->email,
      $this->password,
      $this->currentStep,
      $this->currentPart,
    ));
  }

  /**
   * @see \Serializable::unserialize()
   */
  public function unserialize($serialized)
  {
    list (
      $this->id,
      $this->username,
      $this->email,
      $this->password,
      $this->currentStep,
      $this->currentPart,
    ) = unserialize($serialized);
  }

    /**
     * Set username
     *
     * @param string $username
     * @return Participant
     */
    public function setUsername($username)
    {
      $this->username = $username;

      return $this;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Participant
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
      return $this->email;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return Participant
     */
    public function setPassword($password)
    {
      $this->password = $password;

      return $this;
    }

    /**
     * Set active
     *
     * @param boolean $active
     * @return Participant
     */
    public function setActive($active)
    {
      $this->active = $active;

      return $this;
    }

    /**
     * Get active
     *
     * @return boolean 
     */
    public function getActive()
    {
      return $this->active;
    }

    /**
     * Set currentStep
     *
     * @param string $currentStep
     * @return Participant
     */
    public function setcurrentStep($currentStep)
    {
        $this->currentStep = $currentStep;

        return $this;
    }

    /**
     * Get currentStep
     *
     * @return string 
     */
    public function getcurrentStep()
    {
      return $this->currentStep;
    }

    /**
     * Tells whether participant has already performed the queried step
     * @param part: the study part being queried
     * @param step: the step being queried within that part
     *
     * @return boolean
     */
    public function hasDoneStep($_part, $step)
    {
      //TODO
      return true;
    }

    /**
     * Set currentPart
     *
     * @param integer $currentPart
     * @return Participant
     */
    public function setCurrentPart($currentPart)
    {
      $this->currentPart = $currentPart;

      return $this;
    }

    /**
     * Get currentPart
     *
     * @return integer 
     */
    public function getCurrentPart()
    {
      return $this->currentPart;
    }
    
    public function isEqualTo(UserInterface $user)
    {
      return $this->email === $user->getEmail();
    }
}
