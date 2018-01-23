<?php

namespace Clifton\ClothesBuilderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Clifton\ClothesBuilderBundle\Handler\RegistrationFormHandler;

class SecurityController extends Controller
{
    public function loginAction()
    {
        throw $this->createNotFoundException();
    }

    /**
     * Call after oauth authentication
     *
     */
    public function afterOAuthConnectAction()
    {
        return array();
    }

    /**
     * Get authenticate status
     *
     * @return JsonResponse
     */
    public function loginCheckAction()
    {
        $hasUser = $this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED');
        if (!$hasUser) {
            return new JsonResponse(array('success' => false));
        }
        return new JsonResponse(array('success' => true));
    }

    public function renderFormAction()
    {
        $form = $this->container->get('sonata.user.registration.form');
        return $this->render(
            '@CliftonClothesBuilder/Modals/_registrationModal.html.twig',
            [
                'form' => $form->createView()
            ]
        );
    }

    public function registerAction()
    {
        $user = $this->getUser();

        if ($user instanceof UserInterface) {
            $this->container->get('session')->getFlashBag()->set('sonata_user_error', 'sonata_user_already_authenticated');
            $url = $this->container->get('router')->generate('sonata_user_profile_show');

            return new RedirectResponse($url);
        }

        $form = $this->container->get('sonata.user.registration.form');
        $formHandler = $this->container->get('sonata.user.registration.form.handler');
        $confirmationEnabled = $this->container->getParameter('fos_user.registration.confirmation.enabled');

        $process = $formHandler->process($confirmationEnabled);
        if ($process) {
            return new JsonResponse(['success'=> true]);
        }

        $this->container->get('session')->set('sonata_user_redirect_url', $this->container->get('request')->headers->get('referer'));

        return $this->container->get('templating')->renderResponse('FOSUserBundle:Registration:register.html.'.$this->getEngine(), array(
            'form' => $form->createView(),
        ));
    }

    protected function getEngine()
    {
        return $this->container->getParameter('fos_user.template.engine');
    }

    protected function authenticateUser(UserInterface $user, Response $response)
    {
        try {
            $this->container->get('fos_user.security.login_manager')->loginUser(
                $this->container->getParameter('fos_user.firewall_name'),
                $user,
                $response);
        } catch (AccountStatusException $ex) {}
    }

    public function confirmedAction()
    {
        $user = $this->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        return $this->container->get('templating')->renderResponse('AcmeUserBundle:Registration:confirmed.html.'.$this->getEngine(), array(
            'user' => $user,
        ));
    }

    /**
     * Confirming registration and activate account
     *
     * @param $token
     * @return RedirectResponse
     */
    public function confirmAction($token)
    {
        $user = $this->container->get('fos_user.user_manager')->findUserByConfirmationToken($token);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with confirmation token "%s" does not exist', $token));
        }

        $user->setConfirmationToken(null);
        $user->setEnabled(true);
        $user->setLastLogin(new \DateTime());

        $this->container->get('fos_user.user_manager')->updateUser($user);
        $response = new RedirectResponse($this->container->get('router')->generate('fos_user_registration_confirmed'));
        $this->authenticateUser($user, $response);

        return $response;
    }
}