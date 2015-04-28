<?php

//TODO create a proper error page
//TODO allow showing the previous parts using the done key -- or show nothing when "done" is not visible
//TODO readd 1/2 and 2/2 in nav menu links

namespace UCL\StudyBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints\True;

use UCL\StudyBundle\Controller\UCLStudyController as UCLStudyController;
use UCL\StudyBundle\Entity\Participant;

class StudyPartController extends UCLStudyController
{

    public function __construct()
    {
      $this->space   = 'participant_space';
    }
    /**
     * @Route("/p/{_part}/", name="ucl_study_part_index",
     *    defaults={"_part" = 1},
     *    requirements={"_part": "\d+"})
     */
    public function indexAction($_part, Request $request)
    {
      return $this->redirect($this->generateUrl('ucl_study_part_next'));
    }

    /**
     * @Route("/p/{_part}/information", name="ucl_study_part_information",
     *    defaults={"_part" = 1},
     *    requirements={"_part": "\d+"})
     */
    public function infosheetAction($_part, Request $request)
    {
      $params = $this->setupParameters($request, true, 'information', $_part);
      $params['page'] = array('title' => 'Information Sheet for Part '.$_part.': '.$this->site['participant_space']['part_'.$_part]['name']);

      return $this->render('UCLStudyBundle:StudyPart:infosheet-p'.$_part.'.html.twig', $params);
    }
    
    /**
     * @Route("/p/{_part}/consent", name="ucl_study_part_consent",
     *    defaults={"_part" = 1},
     *    requirements={"_part": "\d+"})
     */
    public function consentAction($_part, Request $request)
    {
      $params = $this->setupParameters($request, true, 'consent', $_part);
      $builder = $this->createFormBuilder(null, array('validation_groups' => array('consent')));
      
      /* This check only works because 'consent' is first, but it's not very robust */
      if ($this->getUser()->getCurrentPart() > $_part || $this->getUser()->getCurrentStep() != 'consent') 
        $params['step'] = 'AlreadyDone';
      else
        $params['step'] = $this->session->get('ucl_study_part_consent_step', 'Inform');

      /* Already consented, just render the page */
      if ($params['step'] == "AlreadyDone")
      {
        $params['page'] = array('title' => 'Consent Form for Part '.$_part.': '.$this->site['participant_space']['part_'.$_part]['name']);
        return $this->render('UCLStudyBundle:StudyPart:consent.html.twig', $params);
      }
      /* Half-way through, infosheet has been accepted */
      else if ($params['step'] == "Consent")
      {
        $params['page'] = array('title' => 'Consent Form for Part '.$_part.': '.$this->site['participant_space']['part_'.$_part]['name']);
        $submitValue = "Give Consent";
      }
      /* Default position -- show infosheet first */
      else /* if ($params['step'] == "Inform") */
      {
        $params['page'] = array('title' => 'Information Sheet for Part '.$_part.': '.$this->site['participant_space']['part_'.$_part]['name']);
        $checkLabel = "I have read the information above and understand what will happen during the study.";
        $submitValue = "Continue";
      }

      if (isset ($checkLabel))
        $builder->add('check', 'checkbox', array('label' => $checkLabel, 'constraints' => new True(array('message' => 'You need to confirm you have read and understood this information sheet.', 'groups' => 'consent'))));
      $builder->add('button', 'submit', array('label' => $submitValue));

      $form = $builder->getForm();
      $params['form'] = $form->createView();

      $form->handleRequest($request);

      if($form->isValid())
      {
        if ($params['step'] == "Consent")
        {
          $this->session->getFlashBag()->add(
              'success',
              'Thank you. You are now enrolled in the study!'
          );
          $this->session->remove('ucl_study_part_consent_step');
          $this->takeParticipantToNextStep($_part, 'consent');
          return $this->redirect($this->generateUrl('ucl_study_part_next'));
        }
        else /* if ($params['step'] == "Inform") */
        {
          $this->session->set('ucl_study_part_consent_step', 'Consent');
          return $this->redirect($this->generateUrl('ucl_study_part_consent', array('_part' => $_part)));
        }
      }
      else if($form->isSubmitted())
      {
        $iter = $form->getErrors(true, true);
        while($iter->valid())
        {
          $err = $iter->current();
          $offender = $err->getCause();
          
          if($offender->getPropertyPath() ==  'children[check].data')
          {
            $this->session->getFlashBag()->add('error', $err->getMessage());
            $params['err_check'] = $err->getMessage();
          }
          else
          {
            $this->session->getFlashBag()->add('error', $err->getMessage());
          }
          
          $iter->next();      
        }
      }

      return $this->render('UCLStudyBundle:StudyPart:consent.html.twig', $params);	      
    }
    
    /**
     * @Route("/p/{_part}/briefing", name="ucl_study_part_briefing",
     *    defaults={"_part" = 1},
     *    requirements={"_part": "\d+"})
     */
    public function briefingAction($_part, Request $request)
    {
      $params = $this->setupParameters($request, true, 'briefing', $_part);

      return $this->render('UCLStudyBundle:StudyPart:briefing.html.twig', $params);
    }
    
    /**
     * @Route("/p/{_part}/debriefing", name="ucl_study_part_debriefing",
     *    defaults={"_part" = 1},
     *    requirements={"_part": "\d+"})
     */
    public function debriefingAction($_part, Request $request)
    {
      $params = $this->setupParameters($request, true, 'debriefing', $_part);

      return $this->render('UCLStudyBundle:StudyPart:debriefing.html.twig', $params);
    }
    
    /**
     * @Route("/p/{_part}/install", name="ucl_study_part_install",
     *    defaults={"_part" = 1},
     *    requirements={"_part": "\d+"})
     */
    public function installAction($_part, Request $request)
    {
      $params = $this->setupParameters($request, true, 'install', $_part);
      $params['page'] = array('title' => 'Software Installation Instructions');

      return $this->render('UCLStudyBundle:StudyPart:start-p'.$_part.'.html.twig', $params);
    }

    /**
     * @Route("/p/{_part}/running", name="ucl_study_part_running",
     *    defaults={"_part" = 1},
     *    requirements={"_part": "\d+"})
     */
    public function runningAction($_part, Request $request)
    {
      $params = $this->setupParameters($request, true, 'running', $_part);
      $params['page'] = array('title' => 'Check your Current Progress');
      
      #TODO
      return $this->render('UCLStudyBundle:StudyPart:running.html.twig', $params);
    }

    /**
     * @Route("/p/{_part}/manual", name="ucl_study_part_manual",
     *    defaults={"_part" = 1},
     *    requirements={"_part": "\d+"})
     */
    public function manualAction($_part, Request $request)
    {
      $params = $this->setupParameters($request, true, 'manual', $_part);
      $params['page'] = array('title' => 'Software Manuals');
      
      #TODO
      return $this->render('UCLStudyBundle:StudyPart:manual.html.twig', $params);
    }

    /**
     * @Route("/p/waiting_enrollment", name="ucl_study_part_waiting_enrollment")
     */
    public function waitingEnrollmentAction(Request $request)
    {
      $params = $this->setupParameters($request, true, 'waiting_enrollment', null);
      $params['_part'] = 0; /* manually injecting this, since we told setupParameters this was not a 'normal' part page and it didn't */
      $params['page'] = array('title' => 'You Are Not Enrolled Yet');
      
      /* Verify not showing waiting_enrollment to an enrolled user */
      if($this->getUser()->getCurrentPart() == 0)
      {
        return $this->render('UCLStudyBundle:StudyPart:waiting-enrollment.html.twig', $params);
      }
      else
      {
        throw $this->createAccessDeniedException('Access Denied: you are already enrolled in the study.');
      }
    }

    /**
     * @Route("/p/next", name="ucl_study_part_next")
     */
    public function nextUserTaskAction(Request $request)
    {
      $params = $this->setupParameters($request, true, 'next', null);

      if($this->getUser()->getCurrentPart() == 0)
      {
        return $this->redirect($this->generateUrl('ucl_study_part_waiting_enrollment'));
      }
      else
      {
        return $this->redirect($this->generateUrl('ucl_study_part_'.$this->getUser()->getCurrentStep(), array('_part' => $this->getUser()->getCurrentPart())));
      }
    }
}
