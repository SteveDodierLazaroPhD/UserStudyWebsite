<?php
namespace UCL\StudyBundle\Twig;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class UCLStudyExtension extends \Twig_Extension
{
  public function getFilters()
  {
    return array(
      new \Twig_SimpleFilter('price', array($this, 'priceFilter')),
    );
  }

  public function getFunctions()
  {
    return array(
      new \Twig_SimpleFunction('user_is_authenticated', array($this, 'userIsAuthenticatedFunction')),
    );
  }

  public function priceFilter($number, $decimals = 0, $decPoint = '.', $thousandsSep = ',')
  {
    $price = number_format($number, $decimals, $decPoint, $thousandsSep);
    $price = '£'.$price;

    return $price;
  }

  function userIsAuthenticatedFunction (Controller $controller)
  {
    $checker = $controller->get('security.authorization_checker');
    
    return $controller->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED');
  }

  public function getName()
  {
      return 'ucl_study_extension';
  }
}

//FIXME TODO
function regTODO ($controller) {

  $twig = $controller->get("twig");
  $function = new Twig_SimpleFunction('user_is_authenticated', function () {
    return $controller->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED');
  });
  $twig->addFunction($function);

  $function = new Twig_SimpleFunction('user_current_part', function () {
    return 0;
  });
  $twig->addFunction($function);
}

?>
