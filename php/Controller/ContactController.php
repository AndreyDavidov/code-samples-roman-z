<?php

namespace Clifton\ClothesBuilderBundle\Controller;

use Clifton\ClothesBuilderBundle\Entity\Contact;
use Clifton\ClothesBuilderBundle\Services\MailingListManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Clifton\ClothesBuilderBundle\Entity\Request as entityRequest;

class ContactController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function contactAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $em = $this->getDoctrine()->getManager();

            $contactMessage = new Contact();
            $contactMessage->setEmail($request->request->get('email'));
            $contactMessage->setFirstName($request->request->get('firstname'));
            $contactMessage->setSurname($request->request->get('surname', 'Empty'));
            $contactMessage->setPhone($request->request->get('phone'));
            $contactMessage->setMessage($request->request->get('message'));
            $contactMessage->setUrl($request->request->get('url'));
            $contactMessage->setDate(date_create(date('Y-m-d H:m:s')));
            $em->persist($contactMessage);
            $em->flush();

            $file = false;
            $uploaded_file = $request->files->get('file');
            if ($uploaded_file != null) {
                $contactMessage->setFile($uploaded_file[0]);
                $contactMessage->upload();
                $em->flush();
                $file = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . '/img/client_files/' . $contactMessage->getPath();
            }

            /** @var SalesForceController $salesforce */
            $salesforce = $this->get('salesfoce_api');
            $salesforce->addLeadOnContactUs($contactMessage, $file);

            if ((bool)$request->request->get('newsletter') == true) {
                /** @var MailingListManager $mailing_list_manager */
                $mailing_list_manager = $this->container->get('mailing_list_manager');
                $mailing_list_manager->addEmailToList($contactMessage->getEmail());

                $user = $this->getUser();
                $key = '';
                if ($user) {
                    $user->setIsNewsletter(true);
                    $key = $user->setUnsubscribeKey();
                    $em->persist($user);
                    $em->flush();
                }
                $this->get('fos_user.mailer')->sendNewsletterSignUpEmailMessage($contactMessage->getEmail(), $key);
            }
            return new JsonResponse(['success' => true]);
        } else {
            return $this->redirectToRoute('clifton_home');
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function requestAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $requestMessage = new entityRequest();
            $requestMessage->setFirstName($request->request->get('firstname'));
            $requestMessage->setSurname($request->request->get('surname', 'Empty'));
            $requestMessage->setPhone($request->request->get('phone'));
            $requestMessage->setUrl($request->request->get('url'));
            $requestMessage->setText($request->request->get('text'));
            $em = $this->getDoctrine()->getManager();
            $em->persist($requestMessage);
            $em->flush();

            $uploaded_file = $request->files->get('file');
            if ($uploaded_file != null) {
                $requestMessage->setFile($uploaded_file[0]);
                $requestMessage->upload();
                $em->flush();
            }

            /** @var SalesForceController $salesforce */
            $salesforce = $this->get('salesfoce_api');
            $salesforce->addLeadOnRequest($requestMessage);
            return new JsonResponse(['success' => true]);
        } else {
            return $this->redirectToRoute('clifton_home');
        }
    }

    /**
     * Check fields for fill
     *
     * @param $request
     * @return bool
     */
    private function checkFields($request)
    {
        $errors = false;
        foreach ($request as $fieldName) {
            if (!isset($fieldName) || empty($fieldName)) {
                $errors = true;
            }
        }
        return $errors;
    }


}