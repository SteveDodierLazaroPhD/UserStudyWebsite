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

/* FIXME It is currently impossible to get a repeated field validated because of a bug in Symfony. If we use the code
         below and only map the first item, it will map onto the constraint validations of the RegistrationJob, but it
         will not validate for difference with the second item. If we map the second item, the RegistrationJob will
         receive an array every time both items are different, which will cause an exception in Doctrine's constraint
         validation routines. We're better off not using repeated fields at all until they fix those. 
        $builder->add('email', 'repeated', array(
          'type' => 'email',
          'invalid_message' => 'form.reg.email.match',
          'first_options'   => array('label'  => 'form.reg.email.one',
                                     'mapped' => true),
          'second_options'  => array('label'  => 'form.reg.email.two',
                                     'mapped' => false),
          'data'            => $options['email'],
          'required'        => true,
      ));
*/

        $builder->add('email', 'email', array(
          'label'  => 'form.reg.email.one',
          'required'        => true,
      ));

        $builder->add('email2', 'email', array(
          'label'  => 'form.reg.email.two',
          'required'        => true,
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
              'firefox'   => 'form.reg.browser.ff',
              'chrome' => 'form.reg.browser.chrome',
              'other'   => 'form.reg.browser.other',
          ),
          //'data'      => $options['browser'],
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
      
      if ($options['screening'])
        $submitlabel = 'form.reg.submit_screening';
      else
        $submitlabel = 'form.reg.submit_automatic';
      
      $builder->add('register', 'submit', array('label' => $submitlabel));
    }
    
    public function getName()
    {
      return 'registration';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
//            'email'           => array('', ''),
            'browser'         => array(),
            'screening'       => false,
            'error_mapping'   => array(
              'isDistroValid' => 'distro',
              'isEmailValid'  => 'email',
              'isEmail2Valid' => 'email2',
              'isPseudonymValid' => 'pseudonym',
             ),
        ));
    }
}

?>
