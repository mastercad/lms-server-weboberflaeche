<?php
/**
 * Klasse für das Verarbeiten von Datei Transfers
 * 
 * PHP Version 7
 * 
 * @category   PHP
 * @package    LmsClient
 * @subpackage Service
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2019 Andreas Kempe
 * @license    GPL http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version    GIT: 
 * @link       http://lms-client.byte-artist.de
 */
namespace App\Service;

class Transfer 
{
    /**
     * Erlaubte Dateitypen zum versenden
     *
     * @var array
     */
    private $allowedFileTypes = [
        'dir' => '/',
        'mp3' => '*.mp3'
    ];

    

}