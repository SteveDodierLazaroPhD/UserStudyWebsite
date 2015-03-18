<?php

namespace UCL\StudyBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

use UCL\StudyBundle\Controller\UCLStudyController as UCLStudyController;
use UCL\StudyBundle\Form\Type\RegistrationType;
use UCL\StudyBundle\Entity\RegistrationJob;
use UCL\StudyBundle\Entity\ContactJob;

class DefaultController extends UCLStudyController
{
    /**
     * @Route("/", name="ucl_study_homepage")
     */
    public function indexAction(Request $request)
    {
      $params = $this->setupParameters($request, false);
      $params['page'] = array('title' => 'Under Development');
      return $this->render('UCLStudyBundle:Default:index.html.twig', $params);
    }

    /**
     * @Route("/join", name="ucl_study_screening_join")
     */
    public function joinAction(Request $request)
    {
      $params = $this->setupParameters($request, false);
      $params['page'] = array('title' => 'Register for Participant Screening');
      
      $previous = $request->request->get('form');
      $registration_folder = $this->container->getParameter('upload_destination_screening');
      $task = new RegistrationJob($registration_folder, $previous != null ? $previous : array());

      $prev_email = $previous ? (array_key_exists('email', $previous) ? $previous['email']['first'] : '') : '';
      $prev_browser = $previous ? (array_key_exists('browser', $previous) ? $previous['browser'] : array()) : array();
      
      $form = $this->createForm(new RegistrationType(), $task, array( 'email' => $prev_email, 'browser' => $prev_browser));
      $params['form'] = $form->createView();
      
      $form->handleRequest($request);

      if($form->isValid())
      {
        $validator = $this->get('validator');
        $errors = $validator->validate($task, array('envsupport'), true, true);

        if(count($errors) > 0)
        {
          $request->getSession()->getFlashBag()->add(
              'notice',
              'Sorry. The study software does not support your desktop environment and Web browser, and you cannot participate. Thank you for your interest nevertheless.'
          );
          return $this->redirect($this->generateUrl('ucl_study_homepage'));
        }
        else
        {
        
          try {
            $store = $this->get('screening_store');
            $yaml = $task->makeScreeningYaml();
            if (!$yaml)
              $request->getSession()->getFlashBag()->add('error', 'Could not process your registration, because of an unknown error when saving the form you filled. This is a bug, please inform the researchers.');
            else
            {
              $filename = $store->makeFile($yaml, "yaml", $this->getUser()->getEmail());
              $em = $this->getDoctrine()->getManager();
              $em->persist($task);
              $em->flush();
              
              $mailer = $this->get('mailer');
              $message = $mailer->createMessage()
                  ->setSubject('['.$this->globals['study_id'].'.study] New Registration from \''.$task->getPseudonym().'\' on ('.date('Y-m-d').')')
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
              $message = $mailer->createMessage()
                  ->setSubject('['.$this->globals['study_id'].'.study] Thank you for registering!')
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

              $request->getSession()->getFlashBag()->add('success','Thank you for your interest in this study. A confirmation email was sent to you. I will be in touch with you shortly.');
              return $this->redirect($this->generateUrl('ucl_study_homepage'));
            }
          } catch (Exception $e) {
            $request->getSession()->getFlashBag()->add('error', 'The registration process was interrupted by an error on the server ('.$e->getMessage().')');
          }
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
            \Doctrine\Common\Util\Debug::dump($err);
          
          if($offender && DefaultController::startsWith($offender->getPropertyPath(), 'data.'))
          {
            $has_seen_local_errors = true;
            $params['err_'.substr($offender->getPropertyPath(),5)] = $err->getMessage(); //length of 'data.'
          }
          else if($offender && $offender->getPropertyPath() == "children[email]")
          {
            $has_seen_local_errors = true;
            $params['err_email_different'] = $err->getMessage();
            $params['err_email_first'] = $offender->getInvalidValue()['first'];
            $params['err_email_second'] = $offender->getInvalidValue()['second'];
            
            /* FIXME should be propagated in the view by getting the client to do an AJAX call or something...
            $email_first = $builder->get('email')->get('first');
            $email_first->setData($offender->getInvalidValue()['first']);
            $email_second = $builder->get('email')->get('second');
            $email_second->setData($offender->getInvalidValue()['second']);
            */
          }
          else
          {
            $request->getSession()->getFlashBag()->add('error', $err->getMessage());
            $has_seen_global_errors = true;
          }
          
          $iter->next();      
        }
        
        if($has_seen_global_errors && $has_seen_local_errors)
            $request->getSession()->getFlashBag()->add('error', "There are additional errors in the form, please see the messages below.");
        else if($has_seen_local_errors)
            $request->getSession()->getFlashBag()->add('error', "There are errors in the form, please see the messages below.");
      }
      return $this->render('UCLStudyBundle:Default:join.html.twig', $params);
    } 

    /**
     * @Route("/information", name="ucl_study_infopre")
     */
    public function infopreAction(Request $request)
    {
      $params = $this->setupParameters($request, false);
      $params['_part'] = 1;
      $params['page'] = array('title' => 'Information Sheet for Prospective Participants');
      return $this->render('UCLStudyBundle:Default:infosheet.html.twig', $params);
    }

    /**
     * @Route("/contact", name="ucl_study_contact")
     */
    public function contactAction(Request $request)
    {
      $params = $this->setupParameters($request, false);
      
      $params['page'] = array('title' => 'Contact the Researchers');

      $previous = $request->request->get('form');
      $task = new ContactJob($this->globals['spam_correct_answer'], $previous != null ? $previous : array());

      $builder = $this->createFormBuilder($task);
      $username = ($this->getUser() != null) ? $this->getUser()->getUsername() : '';
      $email = $this->getUser() ? $this->getUser()->getEmail() : '';
      $selected_answer = $this->getUser() ? $this->globals['spam_correct_answer'] : '';
      
      $builder->add('pseudonym', 'text', array(
          'required'  => true,
          'label'     => 'Name',
          'data'      => $username,
      ));

      $builder->add('email', 'email', array(
          'label'     => 'Email address',
          'required'  => true,
          'data'      => $email,
      ));
      
      $builder->add('message', 'textarea', array(
          'label' => 'Your message',
          'required'  => true,
      ));
      
      $builder->add('spamcheck', 'choice', array(
          'label'     => $this->globals['spam_question'],
          'choices'   => array_combine($this->globals['spam_answer_bag'], $this->globals['spam_answer_bag']),
          'data'      => $selected_answer,
          'multiple'  => false,
          'required'  => true,
          'expanded'  => true,
      ));
      
      $builder->add('write', 'submit', array('label' => 'Send Your Message'));

      $form = $builder->getForm();
      $params['form'] = $form->createView();
      
      $form->handleRequest($request);

      if($form->isValid())
      {
        $mailer = $this->get('mailer');
        $message = $mailer->createMessage()
            ->setSubject('['.$this->globals['study_id'].'.study] Contact Form message from \''.$task->getPseudonym().'\' on ('.date('Y-m-d').')')
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
            'Thank you for your message. I will be in touch with you as soon as I read it.'
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
          
          if(DefaultController::startsWith($offender->getPropertyPath(), 'data.'))
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
            $request->getSession()->getFlashBag()->add('error', "There are additional errors in the form, please see the messages below.");
        else if($has_seen_local_errors)
            $request->getSession()->getFlashBag()->add('error', "There are errors in the form, please see the messages below.");
      }
      return $this->render('UCLStudyBundle:Default:contact.html.twig', $params);
    }

    /**
     * @Route("/hello", name="ucl_study_advert")
     */
    public function advertAction(Request $request)
    {
      $params = $this->setupParameters($request, false);
      $params['page'] = array('title' => 'Join a Study about Multitasking on Linux');
      
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
