<?php

namespace UCL\StudyBundle\Entity;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

class LoginJob
{
    /**
     * @Assert\NotBlank(message = "login.username.not_blank",)
     * //@Assert\Email(
     * //    message = "login.username.invalid",
     * //    checkMX = true
     * )
     */
    protected $username;

    /**
     * @Assert\NotBlank(message = "login.password.not_blank",)
     */
    protected $password;

    /**
     */
    protected $remember_me;

    function __construct ($username = '', $remember = false)
    {
      $this->username       = $username;
      $this->remember_me = $remember;
    }
    
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
    }
    
    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }


    public function getRememberMe()
    {
        return $this->remember_me;
    }

    public function setRememberMe($remember_me)
    {
        $this->remember_me = $remember_me;
    }
}

?>
