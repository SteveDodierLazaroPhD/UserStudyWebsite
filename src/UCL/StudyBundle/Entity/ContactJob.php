<?php

namespace UCL\StudyBundle\Entity;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

class ContactJob
{
    /**
     * @Assert\NotBlank(message = "contact.pseudonym.not_blank",)
     */
    protected $pseudonym;
    /**
     * @Assert\NotBlank(message = "contact.email.not_blank",)
     * @Assert\Email(
     *     message = "contact.email.assert",
     *     checkMX = true
     * )
     */
    protected $email;
    /**
     * @Assert\NotBlank(message = "contact.message.not_blank",)
     * @Assert\Length(
     *      min = 5,
     *      max = 1000,
     *      minMessage = "contact.message.min",
     *      maxMessage = "contact.message.max"
     * )
     */
    protected $message;
    /**
     * @Assert\NotBlank(message = "contact.spamcheck.not_empty",)
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
            'message'  => 'contact.spamcheck.incorrect',
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
