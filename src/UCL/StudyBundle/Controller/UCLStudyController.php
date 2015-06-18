<?php

namespace UCL\StudyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpKernel\Exception\HttpException;

use UCL\StudyBundle\Entity\Participant;

class UCLStudyController extends Controller
{
  protected $session;
  protected $globals;
  protected $site;
  protected $space;

  public function __construct()
  {
    $this->session = null;
    $this->globals = null;
    $this->site    = null;
    $this->space   = 'anonymous_space';
  }
  
  /*    protected function injectErrors($form, $params, $request, $add_flashbag = true, $custom_processor = null)
    {
      $iter = $form->getErrors(true, true);
      $has_seen_local_errors = false;
      $has_seen_global_errors = false;
      while($iter->valid())
      {
        $err = $iter->current();
        $offender = $err->getCause();
        
        if($offender && AuthController::startsWith($offender->getPropertyPath(), 'data.'))
        {
          $has_seen_local_errors = true;
          $params['err_'.substr($offender->getPropertyPath(),5)] = $err->getMessage(); //length of 'data.'
        }
        else if($custom_processor != null && $custom_processor($form, $params, $request))
        {
        }
        else
        {
          $request->getSession()->getFlashBag()->add('error', $err->getMessage());
          $has_seen_global_errors = true;
        }
        
        $iter->next();      
      }
      
      if($add_flashbag)
      {
        if($has_seen_global_errors && $has_seen_local_errors)
          $request->getSession()->getFlashBag()->add('error', "There are additional errors in the form, please see the messages below.");
        else if($has_seen_local_errors)
          $request->getSession()->getFlashBag()->add('error', "There are errors in the form, please see the messages below.");
      }
    }*/


  protected function getEmailAddress()
  {
    return $this->container->getParameter('mail_username').'@'.$this->container->getParameter('mail_host');
  }

  protected function persistObject($obj)
  {
    $em = $this->getDoctrine()->getManager();
    $em->persist($obj);
    $em->flush($obj);
  }

  protected function removeObject($obj)
  {
    $em = $this->getDoctrine()->getManager();
    $em->remove($obj);
    $em->flush();
  }

  protected function getEnabledStepsForPart($part)
  {
    $logger = $this->get('logger');
    $translator = $this->get('translator');
    if (!isset ($this->site[$this->space]))
    {
      $logger->error($translator->trans('This study is misconfigured (missing a \'%sectionname%\' section). Please inform the researchers so they can fix the issue.', array('%sectionname%' => $this->space)));
      return null;
    }
    if (!isset ($this->site[$this->space]['part_'.$part]))
    {
      $logger->error($translator->trans('This study is misconfigured (missing part \'%partname%\' for the \'%sectionname%\' section). Please inform the researchers so they can fix the issue.',
                                       array('%partname%' => $part,
                                             '%sectionname%' => $this->space)));
      return null;
    }
    if (!isset ($this->site['participant_space']['part_'.$part]['enabled_steps']))
    {
      $logger->error($translator->trans('This study is misconfigured (missing a list of enabled steps for part \'%partname%\'). Please inform the researchers so they can fix the issue.', array('%partname%' => $part)));
      return null;
    }

    return $this->site['participant_space']['part_'.$part]['enabled_steps'];
  }

  protected function getVisiblePagesForPartAndStep($part, $step)
  {
    $logger = $this->get('logger');
    $translator = $this->get('translator');
    if (!isset ($this->site[$this->space]))
    {
      $logger->error($translator->trans('This study is misconfigured (missing a \'%sectionname%\' section). Please inform the researchers so they can fix the issue.', array('%sectionname%' => $this->space)));
      return null;
    }
    if (!isset ($this->site[$this->space]['part_'.$part]))
    {
      $logger->error($translator->trans('This study is misconfigured (missing part \'%partname%\' for the \'%sectionname%\' section). Please inform the researchers so they can fix the issue.',
                                       array('%partname%' => $part,
                                             '%sectionname%' => $this->space)));
      return null;
    }
    if (!isset ($this->site[$this->space]['part_'.$part]['navigation']))
    {
      $logger->error($translator->trans('This study is misconfigured (missing a \'navigation\' section for part \'%partname%\'). Please inform the researchers so they can fix the issue.', array('%partname%' => $part)));
      return null;
    }
    if (!isset ($this->site[$this->space]['part_'.$part]['navigation'][$step]) || !isset ($this->site[$this->space]['part_'.$part]['navigation'][$step]['visible_steps']))
    {
      if (!isset ($this->site[$this->space]['part_'.$part]['default_visible']))
      {
        $logger->error($translator->trans('This study is misconfigured (missing a list of visible pages for part \'%partname%\' and step \'%stepname%\'). Please inform the researchers so they can fix the issue.', array('%partname%' => $part, '%stepname%' => $step)));
        return null;
      }
  
      return $this->site[$this->space]['part_'.$part]['default_visible'];
    }

    return $this->site[$this->space]['part_'.$part]['navigation'][$step]['visible_steps'];
  }

  protected function checkPartBoundaries($part)
  {
    if (($part <= 0 || $part > $this->globals['part_count']))
    {
      $translated = $this->get('translator')->trans('This study only contains %partCount% parts. Part %part% is not valid for this study.', array('%part%' => $part, '%partCount%' => $this->globals['part_count']));
      throw new HttpException(404, $translated);
    }
  }

  protected function checkUserAuthorised(Participant $user, $step, $part)
  {
    $translator = $this->get('translator');
    /* Validate user, because. */
    if ($user == null)
      throw $this->createAccessDeniedException($translator->trans('Access Denied: you must be logged in to access this part of the website. If you were logged in and do not know why this message is shown, please contact the researchers.'));
    
    /* Verify user is allowed in target part */
    if ($user->getCurrentPart() < $part)
    {
      $translated = $translator->trans('Access Denied: you are not yet enrolled in part %part% of this study.', array('%part%' => $part));
      throw $this->createAccessDeniedException($translated);
    }
    
    /* Pages that are always meant to exist, regardless of study part setup ('partless' pages like next should manage authorisation themselves) */
    $alwaysAllowed = array('manual', 'information', 'index', 'next', 'waiting_enrollment');
    
    /* Get the enabled steps for the target part */
    $enabledPages = $this->getEnabledStepsForPart($part);
    if (!$enabledPages)
    {
      $translated = $translator->trans('Could not find a list of enabled steps for your current progress throughout the study (part %currentPart%, step \'%currentStep%\', space \'%space%\'). Please inform the researchers so they can fix the issue.', array('%space%' => $this->space, '%currentPart%' => $user->getCurrentPart(), '%currentStep%' => $user->getCurrentStep()));
      throw new HttpException(500, $translated);
    }
    
    /* Verify target page is an enabled step for target part */
    if(!in_array ($step, $enabledPages) && !in_array ($step, $alwaysAllowed))
      throw new HttpException(404, $translator->trans('This study part does not contain the page you\'re looking for.'));

    /* Get list of pages that are visible given user's current step -- if the user is looking back at old parts, use the 'done' step */
    if ($user->getCurrentPart() == $part)
      $allowedForStep = $this->getVisiblePagesForPartAndStep($user->getCurrentPart(), $user->getCurrentStep());
    else
      $allowedForStep = $this->getVisiblePagesForPartAndStep($part, 'done');
    if (!$allowedForStep)
    {
      $translated = $translator->trans('Could not find a list of visible pages for your current progress throughout the study (part %currentPart%, step \'%currentStep%\', space \'%space%\'). Please inform the researchers so they can fix the issue.', array('%space%' => $this->space, '%currentPart%' => $user->getCurrentPart(), '%currentStep%' => $user->getCurrentStep()));
      throw new HttpException(500, $translated);
    }

    /* Verify target page is a visible page given user's current step */
    if (!in_array ($step, $allowedForStep) && !in_array ($step, $alwaysAllowed))
      throw $this->createAccessDeniedException($translator->trans('Access Denied: this page is not available yet, or you\'ve already completed the tasks associated with it.'));
  }

  protected function checkLoggedIn($remember_ok = true)
  {
    $level = $remember_ok? 'REMEMBERED':'FULLY';
    if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_'.$level))
      throw $this->createAccessDeniedException($this->get('translator')->trans('Access Denied: you must be logged in to access this part of the website.'));
  }

  protected function setupParameters(Request $request, $authenticated = true, $step = null, $part = null)
  {
    $this->session = $request->getSession();
    $this->globals = $this->container->getParameter('ucl_study.globals');
    $this->site    = $this->container->getParameter('ucl_study.site');
    
    $params = array('controller' => $this,
                   'site'        => $this->site,
                   'globals'     => $this->globals,
                   'user'        => $this->getUser(),
                   '_route'      => $request->get('_route'));

    $token = $this->get('security.token_storage')->getToken();
    if (is_a ($token, 'Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken'))
    {
      $params['space'] = $token->getProviderKey();
    }

    if ($authenticated)
    {
      $this->checkLoggedIn($this);

      if ($part != null && $step != null)
        $this->checkUserAuthorised($this->getUser(), $step, $part);
    }

    if ($part != null)
    {
      $this->checkPartBoundaries($part);
      $params['_part'] = $part;
    }

    if ($step != null)
      $params['_step'] = $step;
    
    return $params;
  }

  protected function startsWith($haystack, $needle)
  {
    // search backwards starting from haystack length characters from the end
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
  }
  
  protected function isParticipantDone($_part, $_step)
  {
    $logger     = $this->get('logger');
    $em         = $this->getDoctrine()->getManager();
    $user       = $this->getUser();
    $translator = $this->get('translator');
    
    if (!$user)
    {
      $logger->error('isParticipantDone: No user found.');
      return;
    }
    
    /* Different parts... */
    if ($user->getCurrentPart() != $_part)
      return $user->getCurrentPart() > $_part;
    
    /* Same parts, different steps? */
    $enabledSteps = $this->getEnabledStepsForPart($user->getCurrentPart());
    if (!$enabledSteps)
    {
      $logger->critical('isParticipantDone: Failed to retrieve a list of enabled steps for study part '.$user->getCurrentPart().'. This is a bug.');
      $this->session->getFlashBag()->add('warning', $translator->trans('Because of an unexpected problem, we were unable to determine your current progress in the study. Please try again later or contact us.'));
      return null;
    }
    
    $keys = array_keys($enabledSteps);
    
    $userpos = array_search($user->getCurrentStep(), $keys);
    if ($userpos === FALSE)
    {
      $logger->critical('isParticipantDone: User step \''.$user->getCurrentStep().'\' was not found in the list of enabled steps for part \''.$user->getCurrentPart().'\'. This is a bug.');
      $this->session->getFlashBag()->add('warning', $translator->trans('Because of an unexpected problem, we were unable to determine your current progress in the study. Please try again later or contact us.'));
      return null;
    }
  
    $_steppos = array_search($_step, $keys);
    if ($_steppos === FALSE)
    {
      $logger->critical('isParticipantDone: Queried step \''.$_step.'\' was not found in the list of enabled steps for part \''.$user->getCurrentPart().'\'. This is a bug.');
      $this->session->getFlashBag()->add('warning', $translator->trans('Because of an unexpected problem, we were unable to determine your current progress in the study. Please try again later or contact us.'));
      return null;
    }
  
    return $userpos > $_steppos;
  }

  protected function takeParticipantToNextPart($currentPart)
  {
    $logger = $this->get('logger');
    $em     = $this->getDoctrine()->getManager();
    $user   = $this->getUser();
    
    if (!$user)
    {
      $logger->error('takeParticipantToNextPart: No user found.');
      return;
    }

    $enabledSteps = $this->getEnabledStepsForPart($currentPart);
    $val = current($enabledSteps);
    reset($enabledSteps);

    if (!$enabledSteps || !$val)
    {
      $logger->critical('takeParticipantToNextPart: Failed to retrieve a list of enabled steps for study part '.$currentPart.', whilst updating user \''.$user->getUsername().'\' ('.$user->getEmail().') from part '.$currentPart.'. This is a bug.');
      $this->session->getFlashBag()->add('warning', $this->get('translator')->trans('Because of an unexpected problem, we were unable to take you to the next part of the study. Please try again later or contact us.'));
      return;
    }

    $user->setCurrentStep ($val);
    $user->setCurrentPart($currentPart + 1);
    $em->flush();
  }

  protected function takeParticipantToNextStep($currentPart, $currentStep)
  {
    $logger = $this->get('logger');
    $em     = $this->getDoctrine()->getManager();
    $user   = $this->getUser();
    
    if (!$user)
    {
      $logger->error('takeParticipantToNextStep: No user found.');
      return;
    }
    
    if ($this->isParticipantDone($currentPart, $currentStep))
    {
      $logger->debug('takeParticipantToNextStep: Participant has already progressed further in the study (currently at part '.$user->getCurrentPart().' and step \''.$user->getCurrentStep().'\').');
      return;
    }

    $enabledSteps = $this->getEnabledStepsForPart($currentPart);
    if (!$enabledSteps)
    {
      $logger->critical('takeParticipantToNextStep: Failed to retrieve a list of enabled steps for study part '.$currentPart.', whilst updating user \''.$user->getUsername().'\' ('.$user->getEmail().') from part '.$currentPart.' and step '.$currentStep.'. This is a bug.');
      $this->session->getFlashBag()->add('warning', $this->get('translator')->trans('Because of an unexpected problem, we were unable to take you to the next step of the study. Please try again later or contact us.'));
      return;
    }

    $nextStep = FALSE;
    while ($val = current($enabledSteps))
    {
      if ($val == $currentStep)
        $nextStep = next($enabledSteps);
      next($enabledSteps);
    }
    reset($enabledSteps);

    if ($nextStep)
    {
      $user->setCurrentStep ($nextStep);
      $em->flush();
    }
    else
    {
      takeParticipantToNextPart($currentPart);
    }
  }
}
