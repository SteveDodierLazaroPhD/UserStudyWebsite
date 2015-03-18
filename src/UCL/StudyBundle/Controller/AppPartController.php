<?php

namespace UCL\StudyBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\IOException;
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
    
    protected function getParticipantJSONObject()
    {
      $user = $this->getUser();
      if (!$user)
      {
        $logged = '"LoggedOut": {}';
        $status = '"Status": {"Part":"invalid","Step":"invalid"}';
      }
      else
      {
        $logged = '"LoggedIn": {"Username":"'.$user->getUsername().'","Email":"'.$user->getEmail().'"}';
        $status = '"Status": {"Part":'.$user->getCurrentPart().',"Step":"'.$user->getCurrentStep().'"}';
      }
      
      
      return '"Participant" : { '.$logged.','.$status.' }';
    }

    /**
     * @Route("/a/logged_in", name="ucl_study_app_logged_in")
     */
    public function loggedInAction(Request $request)
    {
      $params = $this->setupParameters($request, true, null, null);
      
      $p = $this->getParticipantJSONObject();
    
      return new Response('{ "LoggedIn" : "Success", '.$p.'}', Response::HTTP_OK, array('content-type' => 'application/json'));
    }

    /**
     * @Route("/a/{_part}/install", name="ucl_study_app_install",
     *    defaults={"_part" = 1},
     *    requirements={"_part": "\d+"})
     */
    public function installAction($_part, Request $request)
    {
      $params = $this->setupParameters($request, true, 'install', $_part);

      $this->session->getFlashBag()->add('success', 'Congratulations! The study application is correctly installed.');
      $this->takeParticipantToNextStep($_part, 'install');
      $p = $this->getParticipantJSONObject(); // Update the participant object!
      return new Response('{ "InstallRegistered" : "Success", '.$p.'}', Response::HTTP_OK, array('content-type' => 'application/json'));
    }

    /**
     * @Route("/a/status", name="ucl_study_app_status")
     */
    public function statusAction(Request $request)
    {
      $params = $this->setupParameters($request, true);
      
      $p = $this->getParticipantJSONObject();
      return new Response('{ "Status" : "Success", '.$p.'}', Response::HTTP_OK, array('content-type' => 'application/json'));
    }
    
    /**
     * @Route("/a/{_part}/uploading", name="ucl_study_app_uploading",
     *    defaults={"_part" = 1},
     *    requirements={"_part": "\d+"})
     */
    public function uploadingAction($_part, Request $request)
    {
      $params = $this->setupParameters($request, true, 'running', $_part); //FIXME use the running status for authentication here!
      $p = $this->getParticipantJSONObject();
      $length = 0;

      $repository = $this->getDoctrine()->getRepository('UCLStudyBundle:DataUploadJob');
      $uploadjob = $repository->findOneBy(array("participant" => $this->getUser()->getId(),
                                                "part"        => $this->getUser()->getCurrentPart(),
                                                "step"        => 'running'));
                                                
      $uploadingState = $request->getSession()->get('Uploading', null);
      /* First, inform the client that we need some job initialisation done */
      if ($uploadingState == 'Init')
      {
        $request->getSession()->set('Uploading', 'SetJobParameters');
        return new Response('{ "Uploading" : "Init", "Status" : {"Part": '.$uploadjob->getPart().', "Step": "'.$uploadjob->getStep().'", "DayCount": '.$uploadjob->getDayCount().', "ExpectedSize": null, "ObtainedSize": '.$uploadjob->getObtainedSize().', '.$p.'}', Response::HTTP_OK, array('content-type' => 'application/json'));
      }
      /* Then, retrieve parameters sent by the client about the job */
      else if ($uploadingState == 'SetJobParameters')
      {
        //TODO parse response
        //TODO clear up session
        //TODO return Response indicating Ok or Permanent Failure
      }
      
      /* Finally, process packets with the uploaded file data until the job is finished */
      try
      {
        $filename = $uploadjob->getFilename();
        $store = $this->get('upload_store');
        $output = $store->getHandle($filename);
        $input = $this->get("request")->getContent(true);

        /* TODO extract some metadata out of the content at this point */
      
        while (!feof($input))
        {
            $contents = fread($input, 8192);
            fwrite($output, $contents);
        }

        $length = ftell($output);
        fclose($input);
        fclose($output);
      }
      catch (Exception $e)
      {
        return new Response('{ "Uploading" : "Failure", "FailureCause" : "Could not open the file to write your data to. This is a bug in the server.", '.$p.'}', Response::HTTP_OK, array('content-type' => 'application/json'));
      }

      if ($length == 0)
        return new Response('{ "Uploading" : "Failure", "FailureCause" : "No content found inside your request.", '.$p.'}', Response::HTTP_OK, array('content-type' => 'application/json'));
      
      $uploadjob->setObtainedSize($uploadjob->getObtainedSize() + $length);
      $em = $this->getDoctrine()->getManager();
      $em->persist($uploadjob);
      $em->flush($uploadjob);
      
      //TODO inform user properly about our progress and what we learnt from her.
      $p = $this->getParticipantJSONObject(); // Update, since participant taken to next step
      return new Response('{ "Uploading" : "NotComplete", '.$p.'}', Response::HTTP_OK, array('content-type' => 'application/json'));
      
      //TODO if job over, ensure user is beyond the 'running' step and congratulate the user.
    }

    /**
     * @Route("/a/{_part}/upload_direct", name="ucl_study_app_upload_direct",
     *    defaults={"_part" = 1},
     *    requirements={"_part": "\d+"})
     */
    public function uploadDirectAction($_part, Request $request)
    {
      $params = $this->setupParameters($request, true, 'running', $_part); //FIXME use the running status for authentication here!
      $handle = $request->getContent(true);
      $type = $request->getContentType();
 
      try
      {
        $store = $this->get('upload_store');
        list ($filename, $length) = $store->makeFileFromBinary($handle, $request->getContentType(), $this->getUser()->getEmail());
      }
      catch (Exception $e)
      {
        $p = $this->getParticipantJSONObject();
        return new Response('{ "DirectUpload" : "Failure", "FailureCause" : "'.$e->getMessage().'", '.$p.'}', Response::HTTP_OK, array('content-type' => 'application/json'));
      }
      
      if ($length == 0)
        return new Response('{ "DirectUpload" : "Failure", "FailureCause" : "No content found inside your request.", '.$p.'}', Response::HTTP_OK, array('content-type' => 'application/json'));
      
      $this->takeParticipantToNextStep($_part, 'running');
      $p = $this->getParticipantJSONObject(); // Update, since participant taken to next step
      return new Response('{ "DirectUpload" : "Success", "DataLength" : '.$length.', "DataType" : "'.$type.'", '.$p.'}', Response::HTTP_OK, array('content-type' => 'application/json'));
    }

    /**
     * @Route("/a/{_part}/upload", name="ucl_study_app_upload",
     *    defaults={"_part" = 1},
     *    requirements={"_part": "\d+"})
     */
    public function uploadAction($_part, Request $request)
    {
      $params = $this->setupParameters($request, true, 'running', $_part); //FIXME use the running status for authentication here!
      
      $daysCollected = 11; //TODO get from user!$participant->getTaskProgress($this->part, $this->step)->getCollectedDayCount();
      
      /* Fetch the current upload job, or start a new one */
      $repository = $this->getDoctrine()->getRepository('UCLStudyBundle:DataUploadJob');
      $uploadjob = $repository->findOneBy(array("participant" => $this->getUser()->getId(),
                                                "part"        => $this->getUser()->getCurrentPart(),
                                                "step"        => "running"));

      if (!$uploadjob)
      {
        $uploadjob = new DataUploadJob($this->getUser(), $_part, 'running', $daysCollected);
      }
        
      /* Setup page parameters for the Twig template */
      $params['daysCollected'] = $daysCollected;
      $params['daysInCurrentJob'] = $uploadjob->getDayCount();
      $params['obtainedSize'] = $uploadjob->getObtainedSize();
      $params['expectedSize'] = $uploadjob->getExpectedSize();
      $params['resuming'] = $uploadjob->getExpectedSize() > $uploadjob->getObtainedSize() ? true : false;
      $params['completed'] = !$params['resuming'] && $uploadjob->getObtainedSize() > 0;
      // We also want to propose erasing when no new data is available, to take local edits into account
      $params['proposeErasing'] = $params['obtainedSize'] > 0;
      $params['page'] = array('title' => 'Upload your Collected Data');
      // Needed for the "refresh day count" button

      /* Create and handle the form */
      $form = $this->createForm(new DataUploadType(), $uploadjob);
      $params['form'] = $form->createView();
      $form->handleRequest($request);

      if($form->isValid())
      {
        /* We must scrap the existing upload job object, and use a clean one */
        if ($form->get('erasecurrentstartnew')->isClicked())
        {
          if ($params['completed'])
          {
            $this->archiveFile($uploadjob->getFilename());
          }
          else
          {
            $this->deleteFile($uploadjob->getFilename());
          }
          $uploadjob->reset($this->getUser(), $_part, 'running', $daysCollected);
        }
        
        /* Make the new file and set the job's filename */
        try
        {
          $store = $this->get('upload_store');
          $filename = $store->makeFile(null, "studydata", $this->getUser()->getEmail());
          $uploadjob->setFilename($filename);
          
          $em = $this->getDoctrine()->getManager();
          $em->persist($uploadjob);
          $em->flush($uploadjob);
          
          $request->getSession()->set('Uploading', 'Init');
          return $this->redirect($this->generateUrl('ucl_study_app_uploading', array('uploadjob' => $uploadjob, '_part' => $_part, 'request' => $request)));
        }
        catch (Exception $e)
        {
          $request->getSession()->getFlashBag()->add('error', 'The upload was aborted because of an error on the server ('.$e->getMessage().')');
        }
      }
      else
      {
        $iter = $form->getErrors(true, true);
        while($iter->valid())
        {
          $err = $iter->current();
          $request->getSession()->getFlashBag()->add('error', $err->getMessage());
          $iter->next();      
        }
      }

      return $this->render('UCLStudyBundle:App:upload.html.twig', $params);
    }
    
    /**
     * @Route("/a/{_part}/running/update", name="ucl_study_app_running_update",
     *    defaults={"_part" = 1},
     *    requirements={"_part": "\d+"})
     */
    public function runningUpdateAction($_part, Request $request)
    {
      $params = $this->setupParameters($request, true, 'running', $_part);
      
      //TODO analyse crafted request
      //TODO update internal completion stats
        
      return new Response('content: ...', Response::HTTP_NOT_IMPLEMENTED, array('content-type' => 'text/yaml'));
    }

    /**
     * @Route("/a/{_part}/running", name="ucl_study_app_running",
     *    defaults={"_part" = 1},
     *    requirements={"_part": "\d+"})
     */
    public function runningAction($_part, Request $request)
    {
      $params = $this->setupParameters($request, true, 'running', $_part);
      
      //TODO pull out completion stats
        
      return new Response('content: ...', Response::HTTP_NOT_IMPLEMENTED, array('content-type' => 'text/yaml'));
    }

    /**
     * @Route("/a/contact", name="ucl_study_app_contact")
     */
    public function contactAction(Request $request)
    {
      return $this->forward('UCLStudyBundle:Default:contact', array('request' => $request));
    }

    /**
     * @Route("/a/{_part}/information", name="ucl_study_app_information",
     *    defaults={"_part" = 1},
     *    requirements={"_part": "\d+"})
     */
    public function informationAction($_part, Request $request)
    {
      return $this->forward('UCLStudyBundle:StudyPart:infosheet', array('_part' => $_part, 'request' => $request));
    }
}
