<?php
namespace UCL\StudyBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\SimpleFormAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/* I put this class together, even though it implements a behaviour achievable
   by default, because I wanted the ability to *debug* my code and see what
   Symfony was doing with my credentials and Symfony form. It's highly
   recommended that you keep it, and hack it to find out what oddities Symfony
   is putting you through */

class StubbornAuthenticator implements SimpleFormAuthenticatorInterface
{
  private $encoder;

  public function __construct(UserPasswordEncoderInterface $encoder)
  {
    $this->encoder = $encoder;
  }

  public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
  {
    try
    {
      $user = $userProvider->loadUserByUsername($token->getUsername());
    }
    catch (UsernameNotFoundException $e)
    {
      /* We are using email addresses, not usernames, but Symfony as a
         framework that *demands* we call our identifying field "_username" */
      throw new UsernameNotFoundException('This email address does not exist in our database, or your account has not been activated yet.');
    }

    $passwordValid = $this->encoder->isPasswordValid($user, $token->getCredentials());

    if ($passwordValid)
    {
      return new UsernamePasswordToken(
        $user,
        $user->getPassword(),
        $providerKey,
        $user->getRoles()
      );
    }

    throw new AuthenticationException('The password / participant code is incorrect. Please contact us if the problem persists.');
  }

  public function supportsToken(TokenInterface $token, $providerKey)
  {
    return $token instanceof UsernamePasswordToken
      && $token->getProviderKey() === $providerKey;
  }

  public function createToken(Request $request, $username, $password, $providerKey)
  {
    return new UsernamePasswordToken($username, $password, $providerKey);
  }
}
?>
