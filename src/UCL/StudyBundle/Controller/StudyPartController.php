<?php

//TODO create a proper error page
//TODO allow showing the previous parts using the done key -- or show nothing when "done" is not visible
//TODO readd 1/2 and 2/2 in nav menu links
//TODO when a study doesn't have multiple parts, don't display the "For part X: Y" titles...

namespace UCL\StudyBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\True;
use UCL\StudyBundle\Controller\UCLStudyController as UCLStudyController;
use UCL\StudyBundle\Form\Type\PaymentInfoType;
use UCL\StudyBundle\Entity\PaymentInfoJob;

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
      $partName = $this->get('translator')->trans($this->site['participant_space']['part_'.$_part]['name']);
      $translated = $this->get('translator')->trans('Information Sheet for Part %part%: %partName%', array('%part%' => $_part, '%partName%' => $partName));
      $params['page'] = array('title' => $translated);

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
      if ($this->getUser()->hasDoneStep($_part, 'consent', $this->getEnabledStepsForPart($_part)))
        $params['step'] = 'AlreadyDone';
      else
        $params['step'] = $this->session->get('ucl_study_part_consent_step', 'Inform');

      /* Already consented, just render the page */
      if ($params['step'] == "AlreadyDone")
      {
        $partName = $this->get('translator')->trans($this->site['participant_space']['part_'.$_part]['name']);
        $params['page'] = array('title' => $this->get('translator')->trans('Consent Form for Part %part%: %partName%', array('%part%' => $_part, '%partName%' => $partName)));
        return $this->render('UCLStudyBundle:StudyPart:consent.html.twig', $params);  // no form processing needed, render now
      }
      /* Half-way through, infosheet has been accepted */
      else if ($params['step'] == "Consent")
      {
        $partName = $this->get('translator')->trans($this->site['participant_space']['part_'.$_part]['name']);
        $params['page'] = array('title' => $this->get('translator')->trans('Consent Form for Part %part%: %partName%', array('%part%' => $_part, '%partName%' => $partName)));
        $submitValue =  $this->get('translator')->trans("Give Consent");
      }
      /* Default position -- show infosheet first */
      else /* if ($params['step'] == "Inform") */
      {
        $partName = $this->get('translator')->trans($this->site['participant_space']['part_'.$_part]['name']);
        $params['page'] = array('title' => $this->get('translator')->trans('Information Sheet for Part %part%: %partName%', array('%part%' => $_part, '%partName%' => $partName)));
        $checkLabel = $this->get('translator')->trans("I have read the information above and understand what will happen during the study.");
        $submitValue = $this->get('translator')->trans("Continue");
      }

      if (isset ($checkLabel))
        $builder->add('check', 'checkbox', array('label' => $checkLabel, 'constraints' => new True(array('message' => $this->get('translator')->trans('You need to confirm you have read and understood this information sheet.'), 'groups' => 'consent'))));
      $builder->add('button', 'submit', array('label' => $submitValue));

      $form = $builder->getForm();
      $form->handleRequest($request);
      $params['form'] = $form->createView();

      if($form->isValid())
      {
        if ($params['step'] == "Consent")
        {
          $this->session->getFlashBag()->add(
              'success',
              $this->get('translator')->trans('Thank you. You are now enrolled in the study!')
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
        $request->getSession()->getFlashBag()->add('error', $translator->trans("There are errors in the form, please see the messages below."));
      }

      return $this->render('UCLStudyBundle:StudyPart:consent.html.twig', $params);	      
    }
    
    /**
     * @Route("/p/{_part}/payment-info", name="ucl_study_part_payment_info",
     *    defaults={"_part" = 1},
     *    requirements={"_part": "\d+"})
     */
    public function paymentInfoAction($_part, Request $request)
    {
      $translator = $this->get('translator');
      $params = $this->setupParameters($request, true, 'payment_info', $_part);
      $params['page'] = array('title' => $translator->trans('Provide your Bank Account Details'));
      $params['enabledSteps'] = $this->getEnabledStepsForPart($_part);

      $previous = $request->request->get('paymentinfo');
      $task = new PaymentInfoJob($previous ? $previous : array());

      $form = $this->createForm(new PaymentInfoType(), $task);
      $form->handleRequest($request);
      $params['form'] = $form->createView();

      if($form->isValid())
      {
        $store = $this->get('payment_store');
        $filename = null;
        try {
          $yaml = $task->makePaymentInfoYaml();
          $filename = $store->makeFile($yaml, "yaml", $this->getUser()->getEmail());
        } catch (Exception $e) {
          $request->getSession()->getFlashBag()->add('error', $translator->trans('An error occurred while processing your form: %errMsg%. Please try again later, or contact us if it keeps happening.', array('%errMsg%' => $e->getMessage())));
        }
        if ($filename)
        {
          $request->getSession()->getFlashBag()->add('success',$translator->trans('Your payment details have been saved. They will be passed on to UCL for payment. Thank you again for participating to our study.'));
          $this->takeParticipantToNextStep($_part, 'payment_info');
          return $this->nextUserTaskAction($request);
        }
      }
      else if($form->isSubmitted())
      {
        $request->getSession()->getFlashBag()->add('error', $translator->trans("There are errors in the form, please see the messages below."));
      }
      return $this->render('UCLStudyBundle:StudyPart:payment-info.html.twig', $params);
    }
    
    /**
     * @Route("/p/{_part}/briefing", name="ucl_study_part_briefing",
     *    defaults={"_part" = 1},
     *    requirements={"_part": "\d+"})
     */
    public function briefingAction($_part, Request $request)
    {
      $params = $this->setupParameters($request, true, 'briefing', $_part);

      #TODO
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
      $params['page'] = array('title' => $this->get('translator')->trans('Schedule Debriefing'));
      return $this->render('UCLStudyBundle:StudyPart:debriefing.html.twig', $params);
    }
    
    /**
     * @Route("/p/{_part}/weekly/{_week}", name="ucl_study_part_weekly",
     *    defaults={"_part" = 1, "_week" = 1},
     *    requirements={"_part": "\d+", "week": "\d+"})
     */
    public function weeklyAction($_part, $_week, Request $request)
    {
      $params = $this->setupParameters($request, true, 'running', $_part);
      $params['page'] = array('title' => $this->get('translator')->trans('Instructions for Week %w%', array('%w%' => $_week)));
      $params['_week'] = $_week;
      return $this->render('UCLStudyBundle:StudyPart:weekly-p'.$_part.'-w'.$_week.'.html.twig', $params);
    }
    
    /**
     * @Route("/p/{_part}/done", name="ucl_study_part_done",
     *    defaults={"_part" = 1},
     *    requirements={"_part": "\d+"})
     */
    public function doneAction($_part, Request $request)
    {
      $params = $this->setupParameters($request, true, 'done', $_part);
      $builder = $this->createFormBuilder(null, array());

      /* This is completely over, special title and no continue form */
      $partsLeft = ($_part < $this->globals['part_count']);
      if (!$partsLeft)
      {
        $params['page'] = array('title' => $this->get('translator')->trans('You Completed the Study', array()));
        return $this->render('UCLStudyBundle:StudyPart:consent.html.twig', $params);  // no form processing needed, render now
      }

      $partName = $this->get('translator')->trans($this->site['participant_space']['part_'.$_part]['name']);
      $params['page'] = array('title' => $this->get('translator')->trans('You Completed Part %part%: %partName%', array('%part%' => $_part, '%partName%' => $partName)));

      /* The participant has already completed this bit, no continue form */
      $userBeyond = ($_part < $this->getUser()->getCurrentPart());
      if ($userBeyond)
      {
        return $this->render('UCLStudyBundle:StudyPart:consent.html.twig', $params);  // no form processing needed, render now
      }

      /* Add a form to continue to the next part */
      $builder->add('button', 'submit', array('label' => $this->get('translator')->trans("Continue to Next Part")));

      $form = $builder->getForm();
      $form->handleRequest($request);
      $params['form'] = $form->createView();

      if($form->isValid())
      {
        $this->session->getFlashBag()->add('success', $this->get('translator')->trans('Thank you. You are now enrolled in the study!'));
        $this->takeParticipantToNextPart($_part);
        return $this->redirect($this->generateUrl('ucl_study_part_next'));
      }
      else if($form->isSubmitted())
      {
        $request->getSession()->getFlashBag()->add('error', $translator->trans("There are errors in the form, please see the messages below."));
      }

      return $this->render('UCLStudyBundle:StudyPart:done.html.twig', $params);
    }
    
    /**
     * @Route("/p/{_part}/install", name="ucl_study_part_install",
     *    defaults={"_part" = 1},
     *    requirements={"_part": "\d+"})
     */
    public function installAction($_part, Request $request)
    {
      $params = $this->setupParameters($request, true, 'install', $_part);
      $params['page'] = array('title' => $this->get('translator')->trans('Software Installation Instructions'));
      $params['enabledSteps'] = $this->getEnabledStepsForPart($_part);

      if ($this->globals['verify_app_install'] !== 'true')
          $this->takeParticipantToNextStep($_part, 'install');

      return $this->render('UCLStudyBundle:StudyPart:install-p'.$_part.'.html.twig', $params);
    }

    /**
     * @Route("/p/{_part}/forum", name="ucl_study_part_forum",
     *    defaults={"_part" = 2},
     *    requirements={"_part": "\d+"})
     */
    public function forumAction($_part, Request $request)
    {
      /* Part 2 only! */
      $params = $this->setupParameters($request, true, 'running', 2);
      $params['page'] = array('title' => $this->get('translator')->trans('Participant Forum'));
      return $this->render('UCLStudyBundle:StudyPart:forum.html.twig', $params);
    }

    /**
     * @Route("/p/{_part}/running", name="ucl_study_part_running",
     *    defaults={"_part" = 1},
     *    requirements={"_part": "\d+"})
     */
    public function runningAction($_part, Request $request)
    {
      $params = $this->setupParameters($request, true, 'running', $_part);
      $params['page'] = array('title' => $this->get('translator')->trans('Check your Current Progress'));
      
      /* Fetch the current upload job, or start a new one */
      $progressService = $this->get('participant_upload_progress');
      $prg = $progressService->getStepProgress($this->getUser(), $_part, 'running');
      $uploadjob = $progressService->getUploadJob($this->getUser(), $_part, 'running');
      
      /* Feed all the job data into our view */
      $progressService->feedCurrentJobIntoParametersCached($params, $prg, $uploadjob);

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
      $params['page'] = array('title' => $this->get('translator')->trans('Software Manuals'));
      
      #TODO
      return $this->render('UCLStudyBundle:StudyPart:manual-p'.$_part.'.html.twig', $params);
    }

    /**
     * @Route("/p/{_part}/waiting-enrollment", name="ucl_study_part_waiting_enrollment",
     *    defaults={"_part" = 1},
     *    requirements={"_part": "\d+"})
     */
    public function waitingEnrollmentAction($_part, Request $request)
    {
      $params = $this->setupParameters($request, true, 'waiting_enrollment', $_part);
      $params['page'] = array('title' => $this->get('translator')->trans('You Are Not Enrolled Yet'));
      
      if ($this->globals['screen_participants'] == true)
      {
        return $this->render('UCLStudyBundle:StudyPart:waiting-enrollment.html.twig', $params);
      }
      else
      {
        $this->takeParticipantToNextStep($_part, 'waiting_enrollment');
        return $this->nextUserTaskAction($request);
      }
    }

    /**
     * @Route("/p/next", name="ucl_study_part_next")
     */
    public function nextUserTaskAction(Request $request)
    {
      /* $params = */$this->setupParameters($request, true, 'next', null);

      return $this->redirect($this->generateUrl('ucl_study_part_'.$this->getUser()->getCurrentStep(), array('_part' => $this->getUser()->getCurrentPart())));
    }
}
