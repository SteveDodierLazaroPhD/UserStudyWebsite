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
          'label'     => 'Nickname',
      ));

      $builder->add('email', 'repeated', array(
          'type' => 'email',
          'invalid_message' => 'The email fields must match.',
          'first_options'   => array('label' => 'Email address'),
          'second_options'  => array('label' => 'Repeat Email address'),
          'data'            => $options['email'],
          'required' => true,
      ));
      
      $builder->add('proficiency', 'choice', array(
          'label'      => 'Linux Proficiency',
          'choices'   => array(
              'beginner'   => 'Beginner',
              'poweruser' => 'Advanced user',
              'techy'   => 'Advanced user with technical skills<sup>1</sup>',
              'pro'   => 'IT professional',
          ),
          'multiple'  => false,
          'required'  => true,
          'expanded'  => true,
      ));
      
      $builder->add('occupation', 'text', array(
          'label' => 'Occupation',
          'required'  => true,
      ));
      
      $builder->add('age', 'choice', array(
          'choices'   => array(
              'a1824'   => '18 – 24',
              'a2534' => '25 – 34',
              'a3544' => '35 – 44',
              'a4554' => '45 – 54',
              'a5564' => '55 – 64',
              'a65p'   => '65+',
          ),
          'multiple'  => false,
          'required'  => true,
          'expanded'  => true,
      ));
      
      $builder->add('distro', 'choice', array(
          'label' => 'Linux Distribution',
          'choices'   => array(
              'debian'   => 'Debian',
              'ubuntu' => 'Ubuntu',
              'other'   => 'Other',
          ),
          'multiple'  => false,
          'required'  => true,
          'expanded'  => true,
          'error_mapping' => array(
            'isDistroValid' => 'choice',
           ),
      ));
      
      $builder->add('distro_other', 'text', array(
          'label' => 'If Other, please specify',
          'required'  => false,
      ));
      
      $builder->add('de', 'choice', array(
          'label' => 'Desktop Environment',
          'choices'   => array(
              'xfce'   => 'Xfce',
              'unity' => 'Unity',
              'gnome' => 'GNOME',
              'other'   => 'Other (unsupported)',
          ),
          'required'  => true,
          'expanded'  => true,
      ));
      
      $builder->add('browser', 'choice', array(
          'label' => 'Web Browser',
          'choices'   => array(
              'firefox'   => 'Firefox/Iceweasel',
              'chrome' => 'Chrome/Chromium',
              'other'   => 'Other (unsupported)',
          ),
          'data'      => $options['browser'],
          'multiple'  => true,
          'required'  => true,
          'expanded'  => true,
      ));
      
      $builder->add('gender', 'choice', array(
          'choices'   => array('f' => 'Female', 'm' => 'Male', 'o' => 'Other'),
          'required'  => true,
          'expanded'  => true,
      ));
      
      $builder->add('register', 'submit', array('label' => 'Join Participant Waiting List'));
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
