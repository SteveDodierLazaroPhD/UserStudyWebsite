<?php

namespace UCL\StudyBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use UCL\StudyBundle\Controller\UCLStudyController as UCLStudyController;

class AuthController extends UCLStudyController
{
    /**
     * @Route("/a/login", name="ucl_study_app_login")
     */
    public function appLoginAction(Request $request)
    {
      return $this->forward('UCLStudyBundle:Auth:login', array(
                       'twig'    => 'UCLStudyBundle:Auth:app-login.html.twig',
                       'title'   => $this->container->getParameter('ucl_study.site')['title'],
                       'request' => $request));
    }

    /**
     * @Route("/p/login", name="ucl_study_login")
     */
    public function loginAction(Request $request,
                                $twig  = 'UCLStudyBundle:Auth:login.html.twig', 
                                $title = null)
    {
      $params = $this->setupParameters($request, false);
      if (!$title)
        $title = $this->get('translator')->trans('Log In to the Participant Website');
      $params['page'] = array('title' => $title);
      
      $authenticationUtils = $this->get('security.authentication_utils');
      $params['last_username'] = $authenticationUtils->getLastUsername();

      $error = $authenticationUtils->getLastAuthenticationError();
      if ($error)
      {
        $request->getSession()->getFlashBag()->add('error', $error-> getMessage());

        if (is_a ($error, 'Symfony\Component\Security\Core\Exception\UsernameNotFoundException'))
          $params['last_username'] = '';
      }

      return $this->render($twig, $params);
    }
}
