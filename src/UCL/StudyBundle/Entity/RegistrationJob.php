<?php
namespace UCL\StudyBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Yaml\Dumper;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 */
class RegistrationJob extends EntityRepository
{
  /**
   * @Assert\NotBlank(message = "participant.pseudonym.not_blank",)
   */
  protected $pseudonym;

  /**
   * @Assert\NotBlank(message = "participant.email.not_blank",)
   * @Assert\Email(
   *     message = "participant.email.invalid",
   *     checkMX = true
   * )
   */
  protected $email;

  /**
   * @Assert\NotBlank(message = "participant.email.not_blank",)
   * @Assert\Email(
   *     message = "participant.email.invalid",
   *     checkMX = true
   * )
   */
  protected $email2;

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
  function __construct (EntityManager $em, $initial = array())
  {
    parent::__construct($em, new ClassMetadata("UCL\StudyBundle\Entity\Participant"));
    // Note that email (repeated) and browser (choices multiple) are not automatically managed and need manual data instantiation in the controller
    $this->pseudonym    = $initial ? (array_key_exists('pseudonym', $initial) ? $initial['pseudonym'] : '') : '';
    $this->email        = $initial ? (array_key_exists('email', $initial) ? $initial['email'] : '') : '';
    $this->email2       = $initial ? (array_key_exists('email2', $initial) ? $initial['email2'] : '') : '';
    $this->age          = $initial ? (array_key_exists('age', $initial) ? $initial['age'] : '') : '';
    $this->gender       = $initial ? (array_key_exists('gender', $initial) ? $initial['gender'] : '') : '';
    $this->proficiency  = $initial ? (array_key_exists('proficiency', $initial) ? $initial['proficiency'] : '') : '';
    $this->occupation   = $initial ? (array_key_exists('occupation', $initial) ? $initial['occupation'] : '') : '';
    $this->distro       = $initial ? (array_key_exists('distro', $initial) ? $initial['distro'] : '') : '';
    $this->distroOther  = $initial ? (array_key_exists('distro_other', $initial) ? $initial['distro_other'] : '') : '';
    $this->de           = $initial ? (array_key_exists('de', $initial) ? $initial['de'] : '') : '';
    $this->browser      = $initial ? (array_key_exists('browser', $initial) ? $initial['browser'] : []) : [];
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

  public function getEmail2()
  {
    return $this->email2;
  }

  public function setEmail2($email)
  {
    $this->email2 = $email;
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
   * @Assert\True(message = "participant.email.already_used")
   */
  public function isEmailValid()
  {
    $q = $this
          ->createQueryBuilder('u')
          ->where('u.email = :email')
          ->setParameter('email', $this->email)
          ->getQuery();
    try {
        $user = $q->getOneOrNullResult();
    } catch (NoResultException $e) {
      return true;
    } finally {
      return $user === null;
    }
  }

  /**
   * @Assert\True(message = "participant.email.match")
   */
  public function isEmail2Valid()
  {
    return $this->email === $this->email2;
  }

  /**
   * @Assert\True(message = "participant.pseudonym.already_used")
   */
  public function isPseudonymValid()
  {
    $q = $this
          ->createQueryBuilder('u')
          ->where('u.username = :pseudonym')
          ->setParameter('pseudonym', $this->pseudonym)
          ->getQuery();
    try {
        $user = $q->getOneOrNullResult();
    } catch (NoResultException $e) {
      return true;
    } finally {
      return $user === null;
    }
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

  public function setPasswordFromClearText($password)
  {
    $this->clearpw = $password;
  }

  public function getPasswordFromClearText()
  {
    return $this->clearpw;
  }
}
