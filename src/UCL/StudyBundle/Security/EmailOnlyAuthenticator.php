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

/* This class does not verify the password typed by the user, only their
   identity, which in UCL Study Bundles correspond to users' email addresses.
 */

class EmailOnlyAuthenticator implements SimpleFormAuthenticatorInterface
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

    return new UsernamePasswordToken(
        $user,
        $user->getPassword(),
        $providerKey,
        $user->getRoles());
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

