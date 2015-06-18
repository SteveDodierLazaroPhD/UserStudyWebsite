<?php
namespace UCL\StudyBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Yaml\Dumper;

/**
 * @ORM\Entity
 * @ORM\Table(name="study_participants")
 * @UniqueEntity(
 *     fields={"email"},
 *     message="participant.email.already_used"
 * )
 * @UniqueEntity(
 *     fields={"pseudonym"},
 *     message="participant.pseudonym.already_used"
 * )
 */
class RegistrationJob
{
  /* Public properties (unique db id, nickname and email) */
  /**
   * @ORM\Id
   * @ORM\Column(type="integer", unique=true)
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  protected $id;

  /**
   * @Assert\NotBlank(message = "participant.pseudonym.not_blank",)
   * @ORM\Column(name="username", type="string", length=255, unique=true)
   */
  protected $pseudonym;
  /**
   * @Assert\NotBlank(message = "participant.email.not_blank",)
   * @ORM\Column(name="email", type="string", length=255, unique=true)
   * @Assert\Email(
   *     message = "participant.email.invalid",
   *     checkMX = true
   * )
   */
  protected $email;


  /* PII fields (not readable after locked) */
  /**
   * @Assert\NotBlank(message = "participant.gender.not_blank",)
   * @Assert\Choice(
   *     choices = { "m", "f", "o" },
   *     message = "participant.gender.invalid"
   * )
   */
  protected $gender;
  /**
   * @Assert\NotBlank(message = "participant.age.not_blank",)
   * @Assert\Choice(
   *     choices = { "a1824", "a2534", "a3544", "a4554", "a5564", "a65p" },
   *     message = "participant.age.invalid"
   * )
   */
  protected $age;
  /**
   * @Assert\NotBlank(message = "participant.proficiency.not_blank",)
   * @Assert\Choice(
   *     choices = { "beginner", "poweruser", "techy", "pro" },
   *     message = "participant.proficiency.invalid"
   * )
   */
  protected $proficiency;
  /**
   * @Assert\NotBlank(
   *     message = "participant.occupation.not_blank"
   * )
   */
  protected $occupation;
  /**
   * @Assert\NotBlank(
   *     message = "participant.distro.not_blank"
   * )
   */
  protected $distro;
  protected $distroOther;
  /**
   * @Assert\NotBlank(
   *     message = "participant.de.not_blank"
   * )
   */
  protected $de;
  /**
   * @Assert\NotBlank(
   *     message = "participant.browser.not_blank"
   * )
   */
  protected $browser;
  
  private $clearpw;
  
  /* Methods */
  function __construct ($initial = array())
  {
    // Note that email (repeated) and browser (choices multiple) are not automatically managed and need manual data instantiation in the controller
    $this->pseudonym    = $initial ? (array_key_exists('pseudonym', $initial) ? $initial['pseudonym'] : '') : '';
    $this->email        = $initial ? (array_key_exists('email', $initial) ? $initial['email'] : '') : '';
    $this->age          = $initial ? (array_key_exists('age', $initial) ? $initial['age'] : '') : '';
    $this->gender       = $initial ? (array_key_exists('gender', $initial) ? $initial['gender'] : '') : '';
    $this->proficiency  = $initial ? (array_key_exists('proficiency', $initial) ? $initial['proficiency'] : '') : '';
    $this->occupation   = $initial ? (array_key_exists('occupation', $initial) ? $initial['occupation'] : '') : '';
    $this->distro       = $initial ? (array_key_exists('distro', $initial) ? $initial['distro'] : '') : '';
    $this->distroOther  = $initial ? (array_key_exists('distro_other', $initial) ? $initial['distro_other'] : '') : '';
    $this->de           = $initial ? (array_key_exists('de', $initial) ? $initial['de'] : '') : '';
    $this->browser      = $initial ? (array_key_exists('browser', $initial) ? $initial['browser'] : '') : '';
    $this->clearpw      = null;
  }

  public function getPseudonym()
  {
    return $this->pseudonym;
  }

  public function setPseudonym($pseudonym)
  {
    $this->pseudonym = $pseudonym;
  }

  public function getEmail()
  {
    return $this->email;
  }

  public function setEmail($email)
  {
    $this->email = $email;
  }

  public function getGender()
  {
    return $this->gender;
  }

  public function setGender($gender)
  {
    $this->gender = $gender;
  }

  public function getAge()
  {
    return $this->age;
  }

  public function setAge($age)
  {
    $this->age = $age;
  }

  public function getProficiency()
  {
    return $this->proficiency;
  }

  public function setProficiency($proficiency)
  {
    $this->proficiency = $proficiency;
  }

  public function getOccupation()
  {
    return $this->occupation;
  }

  public function setOccupation($occupation)
  {
    $this->occupation = $occupation;
  }

  public function getDistro()
  {
    return $this->distro;
  }

  public function setDistro($distro)
  {
    $this->distro = $distro;
  }

  public function getDistroOther()
  {
    return $this->distroOther;
  }

  public function setDistroOther($distro)
  {
    $this->distroOther = $distro;
  }

  public function getDe()
  {
    return $this->de;
  }

  public function setDe($de)
  {
    $this->de = $de;
  }

  public function getBrowser()
  {
    return $this->browser;
  }

  public function setBrowser($browser)
  {
    $this->browser = $browser;
  }


  /* Constraint validation */
  /**
   * @Assert\True(
   *     message = "participant.de.invalid",
   *     groups={"envsupport"}
   * )
   */
  public function isDeValid()
  {
    return $this->de != "other";
  }

  /**
   * @Assert\True(
   *     message = "participant.browser.invalid",
   *     groups={"envsupport"}
   * )
   */
  public function isBrowserValid()
  {
    $supportedFound = false;
    foreach($this->browser as $browser)
      $supportedFound |= $browser != "other";
    return $supportedFound;
  }

  /**
   * @Assert\True(message = "participant.distro.invalid")
   */
  public function isDistroValid()
  {
    return ($this->distro != 'other' && $this->distroOther == '') || ($this->distro == 'other' && $this->distroOther != '');
  }
  

  
  /**
   * Creates a name for the file containing this object.
   *
   * @return string The file name.
   */
  public function makeFileName()
  {
    $extension = 'yaml';
    date_default_timezone_set('Europe/London');
    $time = date('Y-m-d_h:i:s');
    //$date = new DateTime('now', new DateTimeZone('Europe/London'));
    //$time = $date->format('Y-m-d h:i:s'); //Y-m-d\TH:i:sP

    return $time.'_'.$this->email.'_'.uniqid('', false).'.'.$extension;
  }
  
  /**
   * Creates a YAML representation of this object.
   *
   * @return string The YAML representation.
   * @throws Exception
   */
  function makeScreeningYaml()
  {
    $array = array(
        'id' => $this->id,
        'pseudonym' => $this->pseudonym,
        'email' => $this->email,
        'gender' => $this->gender,
        'age' => $this->age,
        'proficiency' => $this->proficiency,
        'occupation' => $this->occupation,
        'distro' => $this->distro,
        'distroOther' => $this->distroOther,
        'de' => $this->de,
        'browser' => $this->browser,
        'password' => $this->clearpw
    );

    // Can throw exceptions!
    $dumper = new Dumper();
    return $dumper->dump($array, 1);
  }

  /**
   * Get id
   *
   * @return integer 
   */
  public function getId()
  {
    return $this->id;
  }

  public function setPasswordFromClearText($password)
  {
    $this->clearpw = $password;
  }

  public function getPasswordFromClearText()
  {
    return $this->clearpw;
  }
}
