<?php

namespace UCL\StudyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class RegistrationType extends AbstractType
{
    /* You MUST define email and browser in the options -- See DefaultController's use of this class */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
      $builder->add('pseudonym', 'text', array(
          'required'  => true,
          'label'     => 'form.reg.pseudonym',
      ));

      $builder->add('email', 'repeated', array(
          'type' => 'email',
          'invalid_message' => 'form.reg.email.match',
          'first_options'   => array('label' => 'form.reg.email.one'),
          'second_options'  => array('label' => 'form.reg.email.two'),
          'data'            => $options['email'],
          'required' => true,
      ));
      
      $builder->add('proficiency', 'choice', array(
          'label'      => 'form.reg.proficiency.label',
          'choices'   => array(
              'beginner'   => 'form.reg.proficiency.beginner',
              'poweruser' => 'form.reg.proficiency.advanced',
              'techy'   => 'form.reg.proficiency.tech',
              'pro'   => 'form.reg.proficiency.pro',
          ),
          'multiple'  => false,
          'required'  => true,
          'expanded'  => true,
      ));
      
      $builder->add('occupation', 'text', array(
          'label' => 'form.reg.occupation',
          'required'  => true,
      ));
      
      $builder->add('age', 'choice', array(
          'choices'   => array(
              'a1824'   => 'form.reg.age.1824',
              'a2534' => 'form.reg.age.2534',
              'a3544' => 'form.reg.age.3544',
              'a4554' => 'form.reg.age.4554',
              'a5564' => 'form.reg.age.5564',
              'a65p'   => 'form.reg.age.65',
          ),
          'multiple'  => false,
          'required'  => true,
          'expanded'  => true,
      ));
      
      $builder->add('distro', 'choice', array(
          'label' => 'form.reg.distro.label',
          'choices'   => array(
              //'debian'   => 'Debian',
              'ubuntu' => 'form.reg.distro.ubuntu',
              'other'   => 'form.reg.distro.other',
          ),
          'multiple'  => false,
          'required'  => true,
          'expanded'  => true,
          'error_mapping' => array(
            'isDistroValid' => 'choice',
           ),
      ));
      
      $builder->add('distro_other', 'text', array(
          'label' => 'form.reg.distro.otherlabel',
          'required'  => false,
      ));
      
      $builder->add('de', 'choice', array(
          'label' => 'form.reg.de.label',
          'choices'   => array(
              //'xfce'   => 'form.reg.de.xfce',
              'unity' => 'form.reg.de.unity',
              //'gnome' => 'form.reg.de.gnome',
              'other'   => 'form.reg.de.other',
          ),
          'required'  => true,
          'expanded'  => true,
      ));
      
      $builder->add('browser', 'choice', array(
          'label' => 'form.reg.browser.label',
          'choices'   => array(
              //'firefox'   => 'form.reg.browser.ff',
              'chrome' => 'form.reg.browser.chrome',
              'other'   => 'form.reg.browser.other',
          ),
          'data'      => $options['browser'],
          'multiple'  => true,
          'required'  => true,
          'expanded'  => true,
      ));
      
      $builder->add('gender', 'choice', array(
          'label'     => 'form.reg.gender.label',
          'choices'   => array('f' => 'form.reg.gender.female', 'm' => 'form.reg.gender.male', 'o' => 'form.reg.gender.other'),
          'required'  => true,
          'expanded'  => true,
      ));
      
      $builder->add('register', 'submit', array('label' => 'form.reg.submit'));
    }
    
    public function getName()
    {
      return 'registration';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'email'           => '',
            'browser'         => array(),
        ));
    }
}

?>
