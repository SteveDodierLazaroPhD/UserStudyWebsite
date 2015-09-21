<?php

namespace UCL\StudyBundle\Entity;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

use CommerceGuys\Addressing\Model\Address;
use CommerceGuys\Addressing\Validator\Constraints\Country;
use CommerceGuys\Addressing\Validator\Constraints\AddressFormat;

use CommerceGuys\Addressing\Repository\AddressFormatRepository;
use CommerceGuys\Addressing\Repository\CountryRepository;
use CommerceGuys\Addressing\Repository\SubdivisionRepository;
use CommerceGuys\Addressing\Formatter\DefaultFormatter;

use Symfony\Component\Yaml\Dumper;

use UCL\StudyBundle\Validator\Constraints as UCLAssert;

class PaymentInfoJob
{
  /**
   * @Assert\NotBlank(message = "payment.name.not_blank",)
   */
  protected $name;

  /**
   * @Assert\NotBlank(message = "payment.address.not_blank",)
   */
  protected $address;
  
  /**
   * @Assert\NotBlank(message = "payment.bankname.not_blank",)
   */
  protected $bankname;

  /**
   * @Assert\NotBlank(message = "payment.bankaddress.not_blank",)
   */
  protected $bankaddress;

  /**
   * @Assert\NotBlank(message = "payment.account.not_blank",)
   */
  protected $account;

  /**
   * @UCLAssert\Swift(message = "payment.swift.invalid",)
   * @Assert\NotBlank(message = "payment.swift.not_blank",)
   */
  protected $swift;

  /**
   * @Assert\Iban(
   *     message="payment.iban.invalid"
   * )
   */
  protected $iban;
  
  function __construct ($initial = array())
  {
    $this->name        = array_key_exists('name', $initial) ? $initial['name'] : '';

    $this->address = new Address();
    if (array_key_exists('address', $initial))
    {
      foreach ($initial['address'] as $key => $value)
      {
        $action = 'set'.$key;
        if (is_callable(array($this->address, $action)))
          $this->address->$action($value);
      }
    }
    $this->bankname    = array_key_exists('bankname', $initial) ? $initial['bankname'] : '';
    
    $this->bankaddress = new Address();
    if (array_key_exists('bankaddress', $initial))
    {
      foreach ($initial['bankaddress'] as $key => $value)
      {
        $action = 'set'.$key;
        if (is_callable(array($this->bankaddress, $action)))
          $this->bankaddress->$action($value);
      }
    }
    $this->account     = array_key_exists('account', $initial) ? $initial['account'] : '';
    $this->swift       = array_key_exists('swift', $initial) ? $initial['swift'] : '';
    $this->iban        = array_key_exists('iban', $initial) ? $initial['iban'] : '';
  }
  
  public function getName()
  {
      return $this->name;
  }

  public function setName($name)
  {
      $this->name = $name;
  }
  
  public function getAddress()
  {
      return $this->address;
  }

  public function setAddress($address)
  {
      $this->address = $address;
  }
  
  public function getBankName()
  {
      return $this->bankname;
  }

  public function setBankName($bankname)
  {
      $this->bankname = $bankname;
  }
  
  public function getBankAddress()
  {
      return $this->bankaddress;
  }

  public function setBankAddress($bankaddress)
  {
      $this->bankaddress = $bankaddress;
  }
  
  public function getAccount()
  {
      return $this->account;
  }

  public function setAccount($account)
  {
      $this->account = $account;
  }
  
  public function getSwift()
  {
      return $this->swift;
  }

  public function setSwift($swift)
  {
      $this->swift = $swift;
  }
  
  public function getIban()
  {
      return $this->iban;
  }

  public function setIban($iban)
  {
      $this->iban = $iban;
  }
  
  private function checkAddressSyntax($address)
  {
    if (!isset ($address) || $address === null)
      return false;

    $validator = Validation::createValidator();

    // Validate the country code, then validate the rest of the address.
    $violations = $validator->validateValue($address->getCountryCode(), new Country());

    // FIXME the proper way of removing Recipient and Organization from validation is to use
    // the code below rather than scrap them from the address format list. AddressField::getAll()
    // might need to be customised for the CountryCode above.
    // $enabledFields = array_diff(AddressField::getAll(), [AddressField::RECIPIENT, AddressField::ORGANIZATION]);
    //new AddressFormat(['fields' => $enabledFields]);
    if (!$violations->count())
      $violations = $validator->validateValue($address, new AddressFormat());
  
    return !$violations->count();
  }

  /**
   * @Assert\True(message = "payment.address.invalid")
   */
  public function isAddressValid()
  {
    return $this->checkAddressSyntax($this->address);
  }

  /**
   * @Assert\True(message = "payment.bankaddress.invalid")
   */
  public function isBankAddressValid()
  {
    return $this->checkAddressSyntax($this->bankaddress);
  }

  /**
   * @Assert\True(message = "payment.iban.mandatory_for_region")
   */
  public function isIbanValid()
  {
    $cc = $this->bankaddress->getCountryCode();
    $eu = array('AD','AT','BH','BE','BG','HR','CY','CZ','DK','EE','FO','FI','FR',
                'GE','DE','GI','GB','GR','GL','HU','IS','IE','IM','IT','JE','JO',
                'LV','LB','LI','LT','LU','MK','MT','MD','MC','ME','NL','NO','PK',
                'PS','PL','PT','QA','RO','SM','SA','SK','SI','ES','SE','CH','TN',
                'TR','AE');

    if (in_array($cc, $eu))
      return !empty($this->iban);
    else
      return true;
  }
  
  /**
   * Creates a YAML representation of this object.
   *
   * @return string The YAML representation.
   * @throws Exception
   */
  function makePaymentInfoYaml()
  {
    $format = new AddressFormatRepository();
    $country = new CountryRepository();
    $subdivision = new SubdivisionRepository();
    $formatter = new DefaultFormatter($format, $country, $subdivision, null, array('html' => FALSE));

    $array = array(
        'name' => $this->name,
        'address' => $formatter->format($this->address),
        'bankname' => $this->bankname,
        'bankaddress' => $formatter->format($this->bankaddress),
        'account' => $this->account,
        'swift' => $this->swift,
        'iban' => $this->iban,
    );

    // Can throw exceptions!
    $dumper = new Dumper();
    return $dumper->dump($array, 1);
  }

}

?>
