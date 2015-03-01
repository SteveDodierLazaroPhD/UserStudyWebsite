<?php

namespace UCL\StudyBundle\Entity;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

class ContactJob
{
    /**
     * @Assert\NotBlank(message = "You must provide a nickname.",)
     */
    protected $pseudonym;
    /**
     * @Assert\NotBlank(message = "You must provide an email.",)
     * @Assert\Email(
     *     message = "The email '{{ value }}' is not a valid email.",
     *     checkMX = true
     * )
     */
    protected $email;
    /**
     * @Assert\NotBlank(message = "You must write a message.",)
     * @Assert\Length(
     *      min = 5,
     *      max = 1000,
     *      minMessage = "Your message must be at least {{ limit }} characters long.",
     *      maxMessage = "Your message cannot be longer than {{ limit }} characters long."
     * )
     */
    protected $message;
    /**
     * @Assert\NotBlank(message = "Sorry, this is necessary to prevent robots from spamming us...",)
     * )
     */
    protected $spamcheck;
    private static $spam_valid_reply = '';
    
    function __construct ($spam_valid_reply, $initial = array())
    {
      self::$spam_valid_reply = $spam_valid_reply;
      $this->pseudonym   = $initial ? (array_key_exists('pseudonym', $initial) ? $initial['pseudonym'] : '') : '';
      $this->email       = $initial ? (array_key_exists('email', $initial) ? $initial['email'] : '') : '';
      $this->message     = $initial ? (array_key_exists('message', $initial) ? $initial['message'] : '') : '';
      $this->spamcheck   = $initial ? (array_key_exists('spamcheck', $initial) ? $initial['spamcheck'] : '') : '';
    }
    
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('spamcheck', new Assert\Choice(array(
            'choices' => array(self::$spam_valid_reply,),
            'message'  => "Sorry, this is not the correct answer.",
        )));
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

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }
    
    public function getSpamcheck()
    {
        return $this->spamcheck;
    }

    public function setSpamcheck($spamcheck)
    {
        $this->spamcheck = $spamcheck;
    }
}

?>
