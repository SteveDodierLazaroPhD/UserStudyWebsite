<?php

namespace UCL\StudyBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use UCL\StudyBundle\Controller\UCLStudyController as UCLStudyController;
use UCL\StudyBundle\Form\Type\RegistrationType;
use UCL\StudyBundle\Entity\RegistrationJob;
use UCL\StudyBundle\Entity\ContactJob;
use UCL\StudyBundle\Entity\Participant;

class DefaultController extends UCLStudyController
{
    /**
     * @Route("/", name="ucl_study_homepage")
     */
    public function indexAction(Request $request)
    {
      $params = $this->setupParameters($request, false);
      $translated = $this->get('translator')->trans('Welcome to the %siteTitle% website.', array('%siteTitle%' => $params['site']['title']));
      $params['page'] = array('title' => $translated);
      return $this->render('UCLStudyBundle:Default:index.html.twig', $params);
    }
    /**
     * @Route("/logged_out", name="ucl_study_logged_out")
     */
    public function loggedOutAction(Request $request)
    {
      $request->getSession()->getFlashBag()->add(
          'notice',
          $this->get('translator')->trans('You have been logged out. See you soon!')
      );
      return $this->redirect($this->generateUrl('ucl_study_homepage'));
    }

    /**
     * @Route("/register", name="ucl_study_register")
     */
    public function registerAction(Request $request)
    {
      $translator = $this->get('translator');
      $em = $this->getDoctrine()->getManager();
      $params = $this->setupParameters($request, false);

      if ($this->globals['screen_participants'] == true)
      {
        $params['page'] = array('title' => $translator->trans('Register for Participant Screening'));
        $confirmationMsg = 'Thank you for your interest in this study. A confirmation email was sent to you. We will be in touch with you shortly.';
      }
      else
      {
        $params['page'] = array('title' => $translator->trans('Enroll into the Study'));
        $confirmationMsg = 'Thank you for enrolling into our study. A confirmation email was sent to you. You can now log in into the participant space.';
      }

      $previous = $request->request->get('registration');
      $task = new RegistrationJob($em, $previous !== null ? $previous : array());

      $form = $this->createForm(new RegistrationType(), $task, array('screening' => $this->globals['screen_participants']));
      $params['form'] = $form->createView();
      
      $form->handleRequest($request);

      if($form->isValid())
      {
        try {
          $store = $this->get('screening_store');
          $password = base64_encode (openssl_random_pseudo_bytes (6));
          $task->setPasswordFromClearText($password);
          $filename = null;
          try {
            $yaml = $task->makeScreeningYaml();
            $filename = $store->makeFile($yaml, "yaml", $task->getEmail());
          } catch (Exception $e) {
            $request->getSession()->getFlashBag()->add('error', $translator->trans('An error occurred while processing your registration: %errMsg%. Please try again later, or contact us if it keeps happening.', array('%errMsg%' => $e->getMessage())));
          }
          if ($filename)
          {
            $mailer = $this->get('mailer');
            
            $message = $mailer->createMessage()
                ->setSubject($translator->trans('[%id% study] New Registration from %pseudonym% on (%date%)',
                                                array('%id%' => $this->globals['study_id'], '%pseudonym%' => $task->getPseudonym(), '%date%' => date('Y-m-d'))))
                ->setFrom($this->getEmailAddress())
                ->setReplyTo($task->getEmail())
                ->setTo($this->site['author_email'])
                ->setBody($this->renderView('UCLStudyBundle:Mail:registrationform.txt.twig',
                                array('site'    => $this->site,
                                      'globals' => $this->globals,
                                      'name'    => $task->getPseudonym(),
                                      'email'   => $task->getEmail(),
                                      'date'    => date('r'))));
            $mailer->send($message);
            
            // If you ever need to email participants their passwords, it happens here.
            // For now the passwords are in the store for us to hand out, and they are not in use anyway.
            $message = $mailer->createMessage()
                ->setSubject($translator->trans('[%id% study] Thank you for registering!', array('%id%' => $this->globals['study_id'])))
                ->setFrom($this->getEmailAddress())
                ->setReplyTo($this->site['author_email'])
                ->setTo($task->getEmail())
                ->setBody($this->renderView('UCLStudyBundle:Mail:registrationform-participant.txt.twig',
                                array('site'    => $this->site,
                                      'globals' => $this->globals,
                                      'name'    => $task->getPseudonym(),
                                      'email'   => $task->getEmail(),
                                      'date'    => date('r'))));
            $mailer->send($message);
            
            $enabledSteps = $this->getEnabledStepsForPart(1, 'participant_space');
            \Doctrine\Common\Util\Debug::dump($enabledSteps);
            $participant = new Participant($task->getPseudonym(), $task->getEmail(), null, 1, $enabledSteps[0]);
            $encoder = $this->container->get('security.password_encoder');
            $encodedPw = $encoder->encodePassword($participant, $password);
            $participant->setPassword($encodedPw);

            $em->persist($participant);
            $em->flush();

            $request->getSession()->getFlashBag()->add('success',$translator->trans($confirmationMsg));
            return $this->redirect($this->generateUrl('ucl_study_homepage'));
          }
        } catch (IOException $e) {
          $request->getSession()->getFlashBag()->add('error', $translator->trans('The registration process was interrupted by an error on the server: %errMsg%. Please try again later, or contact us if it keeps happening.', array('%errMsg%', $e->getMessage())));
        }
      }
      else if($form->isSubmitted())
      {
        $iter = $form->getErrors(true, true);
        $has_seen_local_errors = false;
        $has_seen_global_errors = false;
        while($iter->valid())
        {
          $err = $iter->current();
          $offender = $err->getCause();
          
          if($offender && DefaultController::startsWith($offender->getPropertyPath(), 'data.'))
          {
            $has_seen_local_errors = true;
            $params['err_'.substr($offender->getPropertyPath(),5)] = $err->getMessage(); //length of 'data.'
          }
          else
          {
            $request->getSession()->getFlashBag()->add('error', $err->getMessage());
            $has_seen_global_errors = true;
          }
          
          $iter->next();      
        }
        
        if($has_seen_global_errors && $has_seen_local_errors)
            $request->getSession()->getFlashBag()->add('error', $translator->trans('There are additional errors in the form, please see the messages below.'));
        else if($has_seen_local_errors)
            $request->getSession()->getFlashBag()->add('error', $translator->trans('There are errors in the form, please see the messages below.'));
      }
      return $this->render('UCLStudyBundle:Default:register.html.twig', $params);

    }
    
    /**
     * @Route("/join", name="ucl_study_join")
     */
    public function joinAction(Request $request)
    {
        return $this->redirect($this->generateUrl('ucl_study_register'));
    }

    /**
     * @Route("/information", name="ucl_study_infopre")
     */
    public function infopreAction(Request $request)
    {
      $params = $this->setupParameters($request, false);
      $params['_part'] = 1;
      $params['page'] = array('title' => $this->get('translator')->trans('Information Sheet for Prospective Participants'));
      return $this->render('UCLStudyBundle:Default:infosheet.html.twig', $params);
    }

    /**
     * @Route("/contact", name="ucl_study_contact")
     */
    public function contactAction(Request $request)
    {
      $translator = $this->get('translator');
      $params = $this->setupParameters($request, false);
      
      $params['page'] = array('title' => $translator->trans('Contact the Researchers'));

      $previous = $request->request->get('form');

      if (!$previous)
      {
        $previous = array();
        
        if ($this->getUser())
        {
          $previous['pseudonym'] = $this->getUser()->getUsername();
          $previous['email'] = $this->getUser()->getEmail();
          $previous['spamcheck'] = $this->globals['spam_correct_answer'];
        }
      }

      $task = new ContactJob($this->globals['spam_correct_answer'], $previous);
      $builder = $this->createFormBuilder($task);
      
      $builder->add('pseudonym', 'text', array(
          'required'  => true,
          'label'     => $translator->trans('Name'),
      ));

      $builder->add('email', 'email', array(
          'label'     => $translator->trans('Email address'),
          'required'  => true,
      ));
      
      $builder->add('message', 'textarea', array(
          'label' => $translator->trans('Your message'),
          'required'  => true,
      ));
      
      $transbag = array();
      foreach ($this->globals['spam_answer_bag'] as $bagentry)
        array_push ($transbag, $translator->trans($bagentry));

      $builder->add('spamcheck', 'choice', array(
          'label'     => $translator->trans($this->globals['spam_question']),
          'choices'   => array_combine($this->globals['spam_answer_bag'], $transbag),
          'multiple'  => false,
          'required'  => true,
          'expanded'  => true,
      ));
      
      $builder->add('write', 'submit', array('label' => $translator->trans('Send Your Message')));

      $form = $builder->getForm();
      $params['form'] = $form->createView();
      
      $form->handleRequest($request);

      if($form->isValid())
      {
        $mailer = $this->get('mailer');
        $message = $mailer->createMessage()
            ->setSubject($translator->trans('[%id% study] Message from %pseudonym% on (%date%)',
                                            array('%id%' => $this->globals['study_id'], '%pseudonym%' => $task->getPseudonym(), '%date%' => date('Y-m-d'))))
            ->setFrom($this->getEmailAddress())
            ->setReplyTo($task->getEmail())
            ->setTo($this->site['author_email'])
            ->setBody($this->renderView('UCLStudyBundle:Mail:contactform.txt.twig',
                            array('site'    => $this->site,
                                  'globals' => $this->globals,
                                  'name'    => $task->getPseudonym(),
                                  'email'   => $task->getEmail(),
                                  'message' => $task->getMessage(),
                                  'date'    => date('r'))));
        $mailer->send($message);

        $request->getSession()->getFlashBag()->add(
            'success',
            $translator->trans('Thank you for your message. I will be in touch with you as soon as I read it.')
        );
        return $this->redirect($this->generateUrl('ucl_study_contact'));
      }
      else if($form->isSubmitted())
      {
        $iter = $form->getErrors(true, true);
        $has_seen_local_errors = false;
        $has_seen_global_errors = false;
        while($iter->valid())
        {
          $err = $iter->current();
          $offender = $err->getCause();
          
          if($offender !== null && DefaultController::startsWith($offender->getPropertyPath(), 'data.'))
          {
            $has_seen_local_errors = true;
            $params['err_'.substr($offender->getPropertyPath(),5)] = $err->getMessage(); //length of 'data.'
          }
          else
          {
            $request->getSession()->getFlashBag()->add('error', $err->getMessage());
            $has_seen_global_errors = true;
          }
          
          $iter->next();      
        }
        
        if($has_seen_global_errors && $has_seen_local_errors)
            $request->getSession()->getFlashBag()->add('error', $translator->trans("There are additional errors in the form, please see the messages below."));
        else if($has_seen_local_errors)
            $request->getSession()->getFlashBag()->add('error', $translator->trans("There are errors in the form, please see the messages below."));
      }
      return $this->render('UCLStudyBundle:Default:contact.html.twig', $params);
    }

    /**
     * @Route("/hello", name="ucl_study_advert")
     */
    public function advertAction(Request $request)
    {
      $params = $this->setupParameters($request, false);
      $params['page'] = array('title' => $this->get('translator')->trans('Join our Study!'));
      
      return $this->render('UCLStudyBundle:Default:advert.html.twig', $params);
    }

    /**
     * @Route("/next", name="ucl_study_next")
     */
    public function nextAction(Request $request)
    {
      $token = $this->get('security.token_storage')->getToken();
      if (is_a ($token, 'Symfony\Component\Security\Core\Authentication\Token\AnonymousToken'))
      {
        return $this->redirect($this->generateUrl('ucl_study_homepage'));
      }
      else if ($token->getProviderKey() == 'application_space')
      {
        return $this->redirect($this->generateUrl('ucl_study_app_status'));
      }
      else if ($token->getProviderKey() == 'participant_space')
      {
        return $this->redirect($this->generateUrl('ucl_study_part_next'));
      }
    }
}
