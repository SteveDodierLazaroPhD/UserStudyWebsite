<?php

namespace UCL\StudyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
      $builder->add('pseudonym', 'text', array(
          'required'  => true,
          'label'     => 'form.contact.name',
      ));

      $builder->add('email', 'email', array(
          'label'     => 'form.contact.email',
          'required'  => true,
      ));

      $builder->add('message', 'textarea', array(
          'label' => 'form.contact.message',
          'required'  => true,
      ));

      $builder->add('spamcheck', 'choice', array(
          'label'     => $options['spam_question'],
          'choices'   => array_combine($options['spam_answer_bag'], $options['spam_translated_answers']),
          'multiple'  => false,
          'required'  => true,
          'expanded'  => true,
      ));

      $builder->add('write', 'submit', array('label' => 'form.contact.send'));
    }
    
    public function getName()
    {
      return 'registration';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'spam_question'   => 'How many letters are there in UCL?',
            'spam_answer_bag' => ['1', '2', '3', '4', '5'],
            'spam_translated_answers' => ['1', '2', '3', '4', '5'],
        ));
    }
}

?>
