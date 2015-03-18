<?php

namespace UCL\StudyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class DataUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
      //$builder->add('file', 'file', array(
      //  'label' => 'Data Collection Archive',));
      
      $builder->add('participant', 'hidden');
      $builder->add('part', 'hidden');
      $builder->add('step', 'hidden');
      $builder->add('filename', 'hidden');
      $builder->add('dayCount', 'hidden');
      $builder->add('expectedSize', 'hidden');
      $builder->add('obtainedSize', 'hidden');

      $builder->add('dataupload', 'submit', array(
        'label' => 'Upload your Data',)); // Managed in the Twig file!

      $builder->add('erasecurrentstartnew', 'submit', array(
        'label' => 'New Upload (erase previous job)',)); // Managed in the Twig file!
    }
    
    public function getName()
    {
      return 'dataupload';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
      $resolver->setDefaults(array(
        'data_class' => 'UCL\StudyBundle\Entity\DataUploadJob',
      ));
    }
}

?>
