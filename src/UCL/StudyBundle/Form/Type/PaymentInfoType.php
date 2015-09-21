<?php

namespace UCL\StudyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use CommerceGuys\Addressing\Model\Address;
use CommerceGuys\Addressing\Form\Type\AddressType;
use CommerceGuys\Addressing\Repository\AddressFormatRepository;
use CommerceGuys\Addressing\Repository\CountryRepository;
use CommerceGuys\Addressing\Repository\SubdivisionRepository;


class PaymentInfoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
      $format = new AddressFormatRepository();
      $country = new CountryRepository();
      $subdivision = new SubdivisionRepository();

      $builder->add('name', 'text', array(
          'required'  => true,
          'label'     => 'form.payment.fullname',
      ));

      $builder->add('address', new AddressType($format, $country, $subdivision), array(
          'required'  => true,
          'error_bubbling' => true,
          'label'     => 'form.payment.address',
      ));

      $builder->add('bankname', 'text', array(
          'required'  => true,
          'label'     => 'form.payment.bankname',
      ));

      $builder->add('bankaddress', new AddressType($format, $country, $subdivision), array(
          'required'  => true,
          'error_bubbling' => true,
          'label'     => 'form.payment.bankaddress',
      ));

      $builder->add('account', 'text', array(
          'required'  => true,
          'label'     => 'form.payment.account',
      ));

      $builder->add('iban', 'text', array(
          'required'  => false,
          'label'     => 'form.payment.iban',
      ));

      $builder->add('swift', 'text', array(
          'required'  => true,
          'label'     => 'form.payment.swift',
      ));

      $builder->add('send', 'submit', array('label' => 'form.payment.send'));
    }
    
    public function getName()
    {
      return 'paymentinfo';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'error_mapping'      => array(
                'addressValid' => 'address',
                'bankAddressValid' => 'bankaddress',
                'ibanValid' => 'iban',
                ),
        ));
    }
}

?>
