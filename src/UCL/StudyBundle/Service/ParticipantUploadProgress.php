<?php

namespace UCL\StudyBundle\Service;

use Symfony\Component\DependencyInjection\ContainerAware;
use UCL\StudyBundle\Entity\UploadJob;
use UCL\StudyBundle\Entity\Participant;
use UCL\StudyBundle\Entity\StepProgress;

class ParticipantUploadProgress extends ContainerAware
{
  public function getStepProgress(Participant $participant, $part, $step, $progress = 0)
  {
    $repository = $this->container->get('doctrine')->getRepository('UCLStudyBundle:StepProgress');
    $prg = $repository->findOneBy(array("participant" => $participant,
                                              "part"        => $part,
                                              "step"        => $step));

    if (!$prg)
      $prg = new StepProgress($participant, $part, $step, $progress);

    return $prg;
  }

  public function getUploadJob(Participant $participant, $part, $step)
  {
    $repository = $this->container->get('doctrine')->getRepository('UCLStudyBundle:UploadJob');
    $prg = $this->getStepProgress($participant, $part, $step);
    
    $uploadjob = $repository->findOneBy(array("participant" => $participant,
                                              "part"        => $part,
                                              "step"        => $step));
    
    if (!$uploadjob)
      $uploadjob = new UploadJob($participant, $part, $step, $prg->getProgress());

    return $uploadjob;
  }

  public function feedCurrentJobIntoParameters(&$params, Participant $user, $part, $step)
  {
      $prg = $this->getStepProgress($user, $_part, $step);
      $uploadjob = $this->getUploadJob($user, $_part, $step);

      return $this->feedCurrentJobIntoParametersCached($params, $prg, $uploadjob);
  }

  public function feedCurrentJobIntoParametersCached(&$params, StepProgress $prg, UploadJob $uploadjob)
  {
    /* Setup page parameters for the Twig template */
    $params['daysCollected'] = $prg->getProgress();
    $params['daysInCurrentJob'] = $uploadjob->getDayCount();
    $params['obtainedSize'] = $uploadjob->getObtainedSize();
    $params['expectedSize'] = $uploadjob->getExpectedSize();
    $params['resuming'] = $uploadjob->getExpectedSize() > $uploadjob->getObtainedSize() ? true : false;
    $params['completed'] = !$params['resuming'] && $uploadjob->getObtainedSize() > 0;
    // We also want to propose erasing when no new data is available, to take local edits into account
    $params['proposeErasing'] = $params['obtainedSize'] > 0;
    // Needed for the "refresh day count" button

    return $params;
  }
}
?>
