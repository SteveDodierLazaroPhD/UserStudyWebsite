<?php

namespace UCL\StudyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class DataUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
      $builder->add('file', 'file', array(
        'label' => 'Data Collection Archive',));

      $builder->add('dataupload', 'submit', array(
        'label' => 'Upload your Data',));
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
