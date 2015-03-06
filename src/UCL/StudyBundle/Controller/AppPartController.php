<?php

namespace UCL\StudyBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

use UCL\StudyBundle\Controller\UCLStudyController as UCLStudyController;

use UCL\StudyBundle\Form\Type\DataUploadType;
use UCL\StudyBundle\Entity\DataUploadJob;
use UCL\StudyBundle\Entity\Participant;

class AppPartController extends UCLStudyController
{

    public function __construct()
    {
      $this->space   = 'application_space';
    }
    
    protected function getApplicationId()
    {
      $user = $this->getUser();
      if ($user == null)
        return null;

      $email_hash = hash('sha256', $user->getEmail());
      return $email_hash;
    }
    
    protected function validateApplication($_app)
    {
      if ($_app != $this->getApplicationId())
        return new Response('', Response::HTTP_UNAUTHORIZED, array('content-type' => 'text/plain'));
    }

    /**
     * @Route("/a/{_app}/{_part}/start", name="ucl_study_app_start",
     *    defaults={"_part" = 1},
     *    requirements={"_app": "[a-fA-F0-9]{64}","_part": "\d+"})
     */
    public function startAction($_app, $_part, Request $request)
    {
      $params = $this->setupParameters($request, true, 'start', $_part);
      $this->validateApplication($_app);

      $logger = $this->get('logger');
      $already_done = $this->isParticipantDone($_part, 'start');
      if ($alreadydone === null)
      {
        $logger->critical('AppPartController:startAction: Failed to determine if the application was already installed. This was caused by an underlying bug.');
        \Doctrine\Common\Util\Debug::dump("THE BUG");
      }
      else if ($alreadydone)
      {
        \Doctrine\Common\Util\Debug::dump("LOLOLOLOL ALREADY DONE");
      }
      else
      {
        \Doctrine\Common\Util\Debug::dump("HELLO THERE> NEXT.");
        $this->session->getFlashBag()->add('success', 'Congratulations! The study application is correctly installed.');
        $this->takeParticipantToNextStep();
      }
    
      return new Response('InstallRegistered', Response::HTTP_OK, array('content-type' => 'text/plain'));
    }

    /**
     * @Route("/a/{_app}/{_part}/status", name="ucl_study_app_status",
     *    defaults={"_part" = 1},
     *    requirements={"_app": "[a-fA-F0-9]{64}","_part": "\d+"})
     */
    public function statusAction($_app, $_part, Request $request)
    {
      $params = $this->setupParameters($request, true, 'start', $_part);
      
      //TODO process request

      return new Response('name: %%\npart: %%\nstep: %%\n', Response::HTTP_UNAUTHORIZED, array('content-type' => 'text/yaml'));
    }

    /**
     * @Route("/a/{_app}/{_part}/upload", name="ucl_study_app_upload",
     *    defaults={"_part" = 1},
     *    requirements={"_app": "[a-fA-F0-9]{64}","_part": "\d+"})
     */
    public function uploadAction($_app, $_part, Request $request)
    {
      $params = $this->setupParameters($request, true, 'upload', $_part);
      
      //TODO analyse crafted request
      //TODO save file

      return new Response('', Response::HTTP_NOT_IMPLEMENTED, array('content-type' => 'text/plain'));
    }

    /**
     * @Route("/a/{_app}/{_part}/running/update", name="ucl_study_app_running_update",
     *    defaults={"_part" = 1},
     *    requirements={"_app": "[a-fA-F0-9]{64}","_part": "\d+"})
     */
    public function runningUpdateAction($_app, $_part, Request $request)
    {
      $params = $this->setupParameters($request, true, 'running', $_part);
      
      //TODO analyse crafted request
      //TODO update internal completion stats
        
      return new Response('content: ...', Response::HTTP_NOT_IMPLEMENTED, array('content-type' => 'text/yaml'));
    }

    /**
     * @Route("/a/{_app}/{_part}/running", name="ucl_study_app_running",
     *    defaults={"_part" = 1},
     *    requirements={"_app": "[a-fA-F0-9]{64}","_part": "\d+"})
     */
    public function runningAction($_app, $_part, Request $request)
    {
      $params = $this->setupParameters($request, true, 'running', $_part);
      
      //TODO pull out completion stats
        
      return new Response('content: ...', Response::HTTP_NOT_IMPLEMENTED, array('content-type' => 'text/yaml'));
    }

    /**
     * @Route("/a/next", name="ucl_study_app_next")
     */
    public function nextUserTaskAction(Request $request)
    {
      $params = $this->setupParameters($request, true, 'next', null);

      if($this->getUser()->getCurrentPart() == 0)
      {
        return new Response('', Response::HTTP_UNAUTHORIZED, array('content-type' => 'text/plain'));
      }
      else
      {
        return $this->redirect($this->generateUrl('ucl_study_app_'.$this->getUser()->getCurrentStep(),
                                                  array('_app' => $this->getApplicationId(),
                                                        '_part' => $this->getUser()->getCurrentPart())
                                                 ));
      }
    }

    /**
     * @Route("/a/contact", name="ucl_study_app_contact")
     */
    public function contactAction(Request $request)
    {
        return $this->forward('UCLStudyBundle:Default:contact', array('request' => $request));
    }
}
