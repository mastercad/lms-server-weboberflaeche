<?php
/**
 * Hauptklasse.
 *
 * PHP Version 7
 *
 * @category   PHP
 * @package    LmsClient
 * @subpackage Controller
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2019 Andreas Kempe
 * @license    GPL http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version    GIT:
 * @link       http://lms-client.byte-artist.de
 */
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * Klasse für den Haupteinsprung des Frontends.
 *
 * @category   PHP
 * @package    LmsClient
 * @subpackage Controller
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @license    GPL http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link       http://lms-client.byte-artist.de
 */
class IndexController extends AbstractController
{
    /**
     * Haupteinsprung des Frontends, hier wird das grundlegende Layout geladen.
     *
     * @return Response
     *
     * @Route("/", name="home")
     */
    public function indexAction() 
    {
        return $this->render('base.html.twig');
    }
}
