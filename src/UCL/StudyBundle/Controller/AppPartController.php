<?php

namespace UCL\StudyBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\IOException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use UCL\StudyBundle\Controller\UCLStudyController as UCLStudyController;
use UCL\StudyBundle\Form\Type\UploadType;
use UCL\StudyBundle\Entity\UploadJob;
#use UCL\StudyBundle\Entity\Participant;
#use UCL\StudyBundle\Entity\StepProgress;

/**
 * WARNING: using this class to upload files over 2GB on a 32-bit server is considered undefined behaviour.
 */
class AppPartController extends UCLStudyController
{

    public function __construct()
    {
      $this->space   = 'application_space';
    }
    
    protected function jResponse($string = '', $code = Response::HTTP_OK)
    {
      $p = $this->getParticipantJSON();
      return new Response('{'.$string.', '.$p.'}', $code, array('content-type' => 'application/json'));
    }
    
    protected function getParticipantJSON()
    {
      $user = $this->getUser();
      if (!$user)
      {
        $logged = '"LoggedOut":{}';
        $status = '"Status":{"Part":null,"Step":null}';
      }
      else
      {
        $logged = '"LoggedIn":{"Username":"'.$user->getUsername().'","Email":"'.$user->getEmail().'"}';
        $status = '"Status":{"Part":'.$user->getCurrentPart().',"Step":"'.$user->getCurrentStep().'"}';
      }
      
      return '"Participant":{'.$logged.','.$status.'}';
    }
    
    protected function getUploadJobJSON($job)
    {
      $expectedSize = ($job->getExpectedSize() != 0) ? ''.$job->getExpectedSize().'':'null';
      $checksum = ($job->getChecksum()) ? '"'.$job->getChecksum().'"':'null';
      
      return '"UploadJob":{"Part": '.$job->getPart().', '.
                                '"Step": "'.$job->getStep().'", '.
                                '"DayCount": '.$job->getDayCount().', '.
                                '"ExpectedSize": "'.$expectedSize.'", '.
                                '"ObtainedSize": "'.$job->getObtainedSize().'", '.
                                '"Checksum": '.$checksum.'}';
    }
    
    protected function getStepProgressJSON($progress)
    {
      return '"StepProgress":{"Part": '.$progress->getPart().', '.
                             '"Step": "'.$progress->getStep().'", '.
                             '"Progress": '.$progress->getProgress().'}';
    }
    
    /**
     * @Route("/a/logged_in", name="ucl_study_app_logged_in")
     */
    public function loggedInAction(Request $request)
    {
      return $this->forward('UCLStudyBundle:AppPart:status', array('request' => $request));
    }

    /**
     * @Route("/a/{_part}/install", name="ucl_study_app_install",
     *    defaults={"_part" = 1},
     *    requirements={"_part": "\d+"})
     */
    public function installAction($_part, Request $request)
    {
      /*$params = */$this->setupParameters($request, true, 'install', $_part);
      $this->session->getFlashBag()->add('success', $this->get('translator')->trans('Congratulations! The study application is correctly installed.'));
      $this->takeParticipantToNextStep($_part, 'install');
      return $this->jResponse('"InstallRegistered":"Success"');
    }

    /**
     * @Route("/a/status", name="ucl_study_app_status")
     */
    public function statusAction(Request $request)
    {
      /*$params = */$this->setupParameters($request, true);
      return $this->jResponse('"Status":"Success"');
    }

    /**
     * @Route("/a/showstatus", name="ucl_study_app_show_status")
     */
    public function showStatusAction(Request $request)
    {
      $params = $this->setupParameters($request, true);
      $params['page'] = array('title' => $this->get('translator')->trans('Your Current Progress'));
      return $this->render('UCLStudyBundle:App:status.html.twig', $params);
    }
    
    protected function abortUploadJob(UploadJob $uploadjob, $cause=null, $extraData=null)
    {
      $cause = ($cause===null ? $this->get('translator')->trans('Aborting the job') : $cause);
      $this->removeObject($uploadjob);
      if (empty($extraData))
        return $this->jResponse('"Uploading":"Failure","FailureCause":"'.$cause.'"}');
      else
        return $this->jResponse('"Uploading":"Failure","FailureCause":"'.$cause.','.$extraData.'"}');
    }
    
    protected function parseUploadingInit(UploadJob $uploadjob, $_part, Request $request)
    {
      $request->getSession()->set('Uploading', 'JobParameters');
      return $this->jResponse('"Uploading":"ReadyJobParameters", '.$this->getUploadJobJSON($uploadjob));
    }
    
    protected function parseUploadingJobParameters(UploadJob $uploadjob, $_part, Request $request)
    {
      if (0 === strpos($request->headers->get('Content-Type'), 'application/json'))
      {
        $data = json_decode($request->getContent(), true);

        if (is_array($data) && ($data['Uploading'] == 'JobParameters') && array_key_exists ('UploadJob', $data))
        {
          if (array_key_exists ('ExpectedSize', $data['UploadJob']) && $data['UploadJob']['ExpectedSize'])
            $uploadjob->setExpectedSize(intval($data['UploadJob']['ExpectedSize'], 10));
          else
            return $this->jResponse('"Uploading":"Failure", "FailureCause":"Missing ExpectedSize in JobParameters."');
            
          if (array_key_exists ('Checksum', $data['UploadJob']) && preg_match('/^[a-f0-9]{32}$/', $data['UploadJob']['Checksum']))
            $uploadjob->setChecksum($data['UploadJob']['Checksum']);
          else
            return $this->jResponse('"Uploading":"Failure", "FailureCause":"Missing or invalid Checksum in JobParameters."');

          $this->persistObject($uploadjob);
          $request->getSession()->set('Uploading', 'Uploading');
          return $this->jResponse('"Uploading":"ReadyData", '.$this->getUploadJobJSON($uploadjob));
        }
        else
          return $this->jResponse('"Uploading":"Failure", "FailureCause":"Received data contained a syntax error or was not the expected JobParameters message."');
      }
      else
        return $this->jResponse('"Uploading":"ReadyForContent"');
    }
    
    protected function parseUploadingUploading(UploadJob $uploadjob, $_part, Request $request)
    {
      $translator = $this->get('translator');
      try
      {
        $filename = $uploadjob->getFilename();
        $store = $this->get('upload_store');
        $input = $this->get("request")->getContent(true);
        $output = $store->getHandle($filename);
        
        $separatorOk = true;

        $contents = fread($input, 32);
        $expectedHash = (preg_match('/^[a-f0-9]{32}$/', $contents)) ? $contents : '';
        
        $contents = fread($input, 6);
        $separatorOk &= $contents === '------';
        
        $contents = fread($input, 24);
        $expectedLength = (strlen ($contents) == 24) ? intval($contents, 10) : -1;
        
        $contents = fread($input, 6);
        $separatorOk &= $contents === '------';
        
        if (empty($expectedHash) || $expectedLength == -1 || !$separatorOk)
          return $this->jResponse('"Uploading":"Failure", "FailureCause":"The content block should be prefixed with a md5 checksum (32 bits) and a length (encoded as 24 bits string). The hash, length and content should be separated by six dashes."');
        
        $ctx = hash_init ('md5');
        $length = 0;
        
        while (!feof($input))
        {
          $contents = fread($input, 8192);
          hash_update($ctx, $contents);
          fwrite($output, $contents);
        }
        
        fclose($input);
        $hash = hash_final($ctx);
        $length = ftell($output);
        
        if ($length == 0)
          return $this->jResponse('"Uploading":"Failure", "FailureCause":"No content found inside your request."');
        
        if ($hash != $expectedHash || $length != $expectedLength || ($uploadjob->getObtainedSize() + $length > $uploadjob->getExpectedSize()))
        {
          $logger = $this->get('logger');
          $recovered = ftruncate($output, $uploadjob->getObtainedSize());
          fclose($output);
          $diagnostic = '"ErrorReport":{'.
                          '"LengthOffset":'.($length - $expectedLength).', '.
                          '"HashMismatch":'.(($hash != $expectedHash) ? 'true':'false').', '.
                          '"ExpectedSizeOverflow":'.(($uploadjob->getObtainedSize() + $length > $uploadjob->getExpectedSize()) ? 'true':'false').
                        ' }';
          $logger->error('Error while updating UploadJob for participant '.$this->getParticipantJSON().'; job P'.$uploadjob->getPart().' S'.$uploadjob->getStep().' D'.$uploadjob->getDayCount().'; '.$diagnostic);
          
          if ($recovered)
            return $this->jResponse('"Uploading":"ReadyData", '.$diagnostic.', '.$this->getUploadJobJSON($uploadjob));
          else
            return $this->abortUploadJob($uploadjob, 
                                         $translator->trans('An error occurred while writing the uploaded data, and the error could not be recovered. Aborting the job.'),
                                         $diagnostic);
        }

        $uploadjob->setObtainedSize($uploadjob->getObtainedSize() + $length);
        $this->persistObject($uploadjob);
        fclose($output);
  
        if ($uploadjob->getObtainedSize() == $uploadjob->getExpectedSize())
        {
          $store = $this->get('upload_store');
          $filepath = $store->makeFullPath($uploadjob->getFilename());
          $fileChecksum = hash_file('md5', $filepath);
          
          if ($fileChecksum == $uploadjob->getChecksum())
          {
            $this->takeParticipantToNextStep($_part, 'running');
            return $this->jResponse('"Uploading":"Done", '.$this->getUploadJobJSON($uploadjob));
          }
          else
            return $this->abortUploadJob($uploadjob,
                                         $translator->trans('Final checksum failed on data upload job. This usually happens if the client mismatched some of the parts it sent. Aborting the job.'));
        }
        else
          return $this->jResponse('"Uploading":"ReadyData", '.$this->getUploadJobJSON($uploadjob));
      }
      catch (IOException $e)
      {
        return $this->abortUploadJob($uploadjob,
                                     $translator->trans('Could not open the file to write your data to (%errorMsg%). This is a bug in the server. Aborting the job.', array('%errorMsg%' => $e->getMessage())));
      }

      return $this->jResponse('"Uploading":"Failure", "FailureCause":"'.$translator->trans('Could not determine what to do with the received packet. This is a bug in the server.').'"');
    }
    
    /**
     * @Route("/a/{_part}/uploading", name="ucl_study_app_uploading",
     *    defaults={"_part" = 1},
     *    requirements={"_part": "\d+"})
     */
    public function uploadingAction($_part, Request $request)
    {
      /*$params = */$this->setupParameters($request, true, 'running', $_part);

      $progressService = $this->get('participant_upload_progress');
      $uploadjob = $progressService->getUploadJob($this->getUser(), $_part, 'running');
      $uploadingState = $request->getSession()->get('Uploading', 'Init');
      
      /* First, inform the client that we need some job initialisation done */
      if ($uploadingState == 'Init')
      {
        /* The original state depends on whether we are resuming a job or creating a new one */
        /*$resuming = ($uploadjob->getChecksum() !== null && $uploadjob->getExpectedSize() != 0);
        if ($resuming)
          return $this->jResponse('"Uploading":"ReadyData", "UploadJob":{"Part": '.$uploadjob->getPart().', "Step": "'.$uploadjob->getStep().'", "DayCount": '.$uploadjob->getDayCount().', "ExpectedSize": "'.$uploadjob->getExpectedSize().'", "ObtainedSize": "'.$uploadjob->getObtainedSize().'", "Checksum": "'.$uploadjob->getChecksum().'"}');
        else*/
          return $this->parseUploadingInit($uploadjob, $_part, $request);
      }
      
      /* Then, retrieve parameters sent by the client about the job */
      else if ($uploadingState == 'JobParameters')
        return $this->parseUploadingJobParameters($uploadjob, $_part, $request);
      
      /* Finally, process packets with the uploaded file data until the job is finished */
      else if ($uploadingState == 'Uploading')
      {
        return $this->parseUploadingUploading($uploadjob, $_part, $request);
      }
      
      return $this->jResponse('"Uploading":"Failure", "FailureCause":"'.$this->get('translator')->trans('An unknown error occurred while uploading.').'"');
    }
    
    /**
     * @Route("/a/{_part}/uploadreset", name="ucl_study_app_upload_reset",
     *    defaults={"_part" = 1},
     *    requirements={"_part": "\d+"})
     */
    public function uploadResetAction($_part, Request $request)
    {
      /* $params = */$this->setupParameters($request, true, 'running', $_part);
      $progressService = $this->get('participant_upload_progress');
      $uploadjob = $progressService->getUploadJob($this->getUser(), $_part, 'running');
      $this->removeObject($uploadjob);
      $request->getSession()->remove('Uploading');
      
      return $this->jResponse('"UploadReset":"Success"');
    }
    
    /**
     * @Route("/a/{_part}/upload_direct", name="ucl_study_app_upload_direct",
     *    defaults={"_part" = 1},
     *    requirements={"_part": "\d+"})
     */
    public function uploadDirectAction($_part, Request $request)
    {
      /* $params = */$this->setupParameters($request, true, 'running', $_part);
      $handle = $request->getContent(true);
      $type = $request->getContentType();
 
      try
      {
        $store = $this->get('upload_store');
        list (/* $filename */, $length) = $store->makeFileFromBinary($handle, $request->getContentType(), $this->getUser()->getEmail());
      }
      catch (IOException $e)
      {
        return $this->jResponse('"DirectUpload":"Failure", "FailureCause":"'.$e->getMessage().'"');
      }
      catch (UnexpectedValueException $e)
      {
        return $this->jResponse('"DirectUpload":"Failure", "FailureCause":"'.$e->getMessage().'"');
      }
      
      if ($length == 0)
        return $this->jResponse('"DirectUpload":"ReadyForContent"');
      
      $this->takeParticipantToNextStep($_part, 'running');
      return $this->jResponse('"DirectUpload":"Success", "DataLength":'.$length.', "DataType":"'.$type.'"');
    }

    /**
     * @Route("/a/{_part}/upload", name="ucl_study_app_upload",
     *    defaults={"_part" = 1},
     *    requirements={"_part": "\d+"})
     */
    public function uploadAction($_part, Request $request)
    {
      $params = $this->setupParameters($request, true, 'running', $_part);
      $params['page'] = array('title' => $this->get('translator')->trans('Upload your Collected Data'));
      
      /* Fetch the current upload job, or start a new one */
      $progressService = $this->get('participant_upload_progress');
      $prg = $progressService->getStepProgress($this->getUser(), $_part, 'running');
      $uploadjob = $progressService->getUploadJob($this->getUser(), $_part, 'running');
      
      /* Feed all the job data into our view */
      $progressService->feedCurrentJobIntoParametersCached($params, $prg, $uploadjob);
        
      /* Create and handle the form */
      $form = $this->createForm(new UploadType(), $uploadjob);
      $form->handleRequest($request);
      $params['form'] = $form->createView();

      if($form->isValid())
      {
        $store = $this->get('upload_store');
        /* We must scrap the existing upload job object, and use a clean one */
        if ($form->get('erasecurrentstartnew')->isClicked())
        {
          if ($params['completed'])
          {
            $store->archiveFile($uploadjob->getFilename());
          }
          else
          {
            $store->deleteFile($uploadjob->getFilename());
          }
          $uploadjob->reset($this->getUser(), $_part, 'running', $prg->getProgress());
        }
        
        /* Make the new file and set the job's filename */
        try
        {
          $filename = $store->makeFile(null, "studydata", $this->getUser()->getEmail());
          $uploadjob->setFilename($filename);
          $this->persistObject($uploadjob);
          
          return $this->redirect($this->generateUrl('ucl_study_app_uploading', array('uploadjob' => $uploadjob, '_part' => $_part, 'request' => $request)));
        }
        catch (IOException $e)
        {
          $request->getSession()->getFlashBag()->add('error', $this->get('translator')->trans('The upload was aborted because of an error on the server (%errMsg%)', array('%errMsg%' => $e->getMessage())));
        }
      }
      else if($form->isSubmitted())
      {
        $request->getSession()->getFlashBag()->add('error', $translator->trans("There are errors in the form, please see the messages below."));
      }

      return $this->render('UCLStudyBundle:App:upload.html.twig', $params);
    }
    
    /**
     * @Route("/a/{_part}/reportprogress", name="ucl_study_app_report_progress",
     *    defaults={"_part" = 1},
     *    requirements={"_part": "\d+"})
     */
    public function reportProgressAction($_part, Request $request)
    {
      /* $params = */$this->setupParameters($request, true, null, $_part);
      $content = $request->getContent();
      
      if (0 === strpos($request->headers->get('Content-Type'), 'application/json'))
      {
        $data = json_decode($content, true);

        if (is_array($data) && is_array($data['ReportProgress']))
        {
          if (!array_key_exists ('Step', $data['ReportProgress']) || !is_string($data['ReportProgress']['Step']))
            return $this->jResponse('"ReportProgress":"Failure", "FailureCause":"Missing Step."');
            
          if (!array_key_exists ('Progress', $data['ReportProgress']) || !is_int($data['ReportProgress']['Progress']))
            return $this->jResponse('"ReportProgress":"Failure", "FailureCause":"Missing progress counter."');
            
          $step = $data['ReportProgress']['Step'];
          $progress = $data['ReportProgress']['Progress'];

          $progressService = $this->get('participant_upload_progress');
          $stepprogress = $progressService->getStepProgress($this->getUser(), $_part, $step, $progress);
          $this->persistObject($stepprogress);
          //TODO estimate if it's a good time to email the researcher and participant
          
          return $this->jResponse('"ReportProgress":"ReadyData", '.$this->getStepProgressJSON($stepprogress));
        }
        else
          return $this->jResponse('"ReportProgress":"Failure", "FailureCause":"Received data contained a syntax error or was not the expected ReportProgress message."');
      }
      else
        return $this->jResponse('"ReportProgress":"ReadyForContent"');
    }

    /**
     * @Route("/a/contact", name="ucl_study_app_contact")
     */
    public function contactAction(Request $request)
    {
      return $this->forward('UCLStudyBundle:Default:contact', array('request' => $request));
    }

    /**
     * @Route("/a/{_part}/debriefing", name="ucl_study_app_debriefing",
     *    defaults={"_part" = 1},
     *    requirements={"_part": "\d+"})
     */
    public function debriefingAction($_part, Request $request)
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
