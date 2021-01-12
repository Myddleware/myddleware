<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    /**
     * @var UserRepository
     */
    private $userRepository;
    /**
     * @var EncoderFactoryInterface
     */
    private $encoder;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        EncoderFactoryInterface $encoder,
        UserRepository $userRepository
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->encoder = $encoder;
        $this->userRepository = $userRepository;
    }

    /**
     * @Route("/", name="login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser() instanceof User) {
            return $this->redirectToRoute('regle_panel');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        $this->calculBan($lastUsername);

        $attempt = ((isset($_SESSION['myddleware']['secure'][$lastUsername]['attempt'])) ? $_SESSION['myddleware']['secure'][$lastUsername]['attempt'] : 0);
        $remaining = ((isset($_SESSION['myddleware']['secure'][$lastUsername]['remaining'])) ? $_SESSION['myddleware']['secure'][$lastUsername]['remaining'] : 0);

        // If we are on platform.sh, we check that the password has been changed because the first user is always admin/admin
        $passwordMessage = false;
        $platformSh = false;
        if (isset($_ENV['PLATFORM_RELATIONSHIPS'])) {
            $platformSh = true;
            // Get the admin user
            $userAdmin = $this->userRepository->loadUserByUsername('admin');
            if (!empty($userAdmin)) {
                $encoder = $this->encoder->getEncoder($userAdmin);
                // Compare password with admin encoded
                if ($encoder->encodePassword('admin', $userAdmin->getSalt()) == $userAdmin->getPassword()) {
                    $passwordMessage = true;
                }
            }
        }

        return $this->render('Login/index.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'attempt' => $attempt,
            'remaining' => $remaining,
            'password_message' => $passwordMessage,
            'platform_sh' => $platformSh,
        ]);
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logout(): Response
    {
        // Ignored by the system of logout @see security.yaml
        return $this->redirectToRoute('login');
    }

    private function calculBan($lastUsername)
    {
        if (isset($_SESSION['myddleware']['secure'][$lastUsername]['time'])) {
            if (time() > $_SESSION['myddleware']['secure'][$lastUsername]['time']) {
                $_SESSION['myddleware']['secure'][$lastUsername]['attempt'] = 1;
            } else {
                // RESTE X MINUTES AVANT LA FUTUR CONNEXION
                $date1 = time();
                $date2 = $_SESSION['myddleware']['secure'][$lastUsername]['time'];
                $diff = abs($date1 - $date2);

                $diff = abs($date1 - $date2); // abs pour avoir la valeur absolute, ainsi éviter d'avoir une différence négative
                $remaining = [];

                $tmp = $diff;
                $remaining['second'] = $tmp % 60;

                $tmp = floor(($tmp - $remaining['second']) / 60);
                $remaining['minute'] = $tmp % 60;

                $tmp = floor(($tmp - $remaining['minute']) / 60);
                $remaining['hour'] = $tmp % 24;

                $tmp = floor(($tmp - $remaining['hour']) / 24);
                $remaining['day'] = $tmp;

                $_SESSION['myddleware']['secure'][$lastUsername]['remaining'] = $remaining;
            }
        }
    }

    public function verifAccountAction(Request $request)
    {
        try {
            if ($request->isMethod('POST')) {
                $lastUsername = trim($request->request->get('login'));

                // contrôle des tentatives
                // si le nombre de tentative n'existe pas on affecte 0
                if (!isset($_SESSION['myddleware']['secure'][$lastUsername]['attempt'])) {
                    $_SESSION['myddleware']['secure'][$lastUsername]['attempt'] = 1;
                } else { // si existe on ajoute +1
                    ++$_SESSION['myddleware']['secure'][$lastUsername]['attempt'];
                }

                // si le nombre de tentative est supérieur à 5 alors on ajoute une date de contrôle
                if ($_SESSION['myddleware']['secure'][$lastUsername]['attempt'] > 4) {
                    if (!isset($_SESSION['myddleware']['secure'][$lastUsername]['time'])) {
                        $_SESSION['myddleware']['secure'][$lastUsername]['time'] = strtotime('+15 minutes', time());
                    } else {
                        $this->calculBan($lastUsername);
                    }
                }

                return new Response(1);
            }

            return new Response(0);
        } catch (Exception $e) {
            return new Response(0);
        }
    }

    /**
     * Reset user password.
     *
     * @param mixed $token
     */
    public function resetAction(Request $request, $token)
    {
        /** @var $formFactory \FOS\UserBundle\Form\Factory\FactoryInterface */
        $formFactory = $this->get('fos_user.resetting.form.factory');
        /** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */
        $userManager = $this->get('fos_user.user_manager');
        /** @var $dispatcher \Symfony\Component\EventDispatcher\EventDispatcherInterface */
        $dispatcher = $this->get('event_dispatcher');

        $user = $userManager->findUserByConfirmationToken($token);

        if (null === $user) {
            return $this->redirect($this->generateUrl('regle_panel')); // Rev 1.1.1
            //throw new NotFoundHttpException(sprintf('The user with "confirmation token" does not exist for value "%s"', $token));
        }

        $event = new GetResponseUserEvent($user, $request);
        $dispatcher->dispatch(FOSUserEvents::RESETTING_RESET_INITIALIZE, $event);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        $form = $formFactory->createForm();
        $form->setData($user);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $event = new FormEvent($form, $request);
            $dispatcher->dispatch(FOSUserEvents::RESETTING_RESET_SUCCESS, $event);

            $userManager->updateUser($user);

            if (null === $response = $event->getResponse()) {
                $url = $this->generateUrl('fos_user_profile_show');
                $response = new RedirectResponse($url);
            }

            $dispatcher->dispatch(FOSUserEvents::RESETTING_RESET_COMPLETED, new FilterUserResponseEvent($user, $request, $response));

            //return $response;
            return $this->redirect($this->generateUrl('regle_panel')); // Rev 1.1.1
        }

        return $this->render('FOSUserBundle:Resetting:reset.html.twig', [
            'token' => $token,
            'form' => $form->createView(),
        ]);
    }
}
