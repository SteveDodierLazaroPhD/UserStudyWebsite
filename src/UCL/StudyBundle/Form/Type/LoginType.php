<?php

namespace UCL\StudyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class LoginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
      $builder->add('_username', 'text', array(
        'label' => 'form.login.name',));

      $builder->add('_password', 'password', array(
        'label' => 'form.login.password',));

      $builder->add('remember_me', 'checkbox', array(
        'label'     => 'form.login.remember',
        'required'  => false,));

      $builder->add('login', 'submit', array(
        'label' => 'form.login.submit',));
    }
    
    public function getName()
    {
      return 'login';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
      $resolver->setDefaults(array(
        'data_class' => 'UCL\StudyBundle\Entity\LoginJob',
        'csrf_protection' => false, # Avoid the form generating its own csrf token since the security.yml takes care of it
        'intention'       => 'authenticate',
      ));
    }
}

?>
