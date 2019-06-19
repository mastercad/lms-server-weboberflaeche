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

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Doctrine\Migrations\Tools\Console\Exception\DirectoryDoesNotExist;

class File 
{
    /**
     * Lädt eine Liste von Dateien und Ordnern ab dem übergebenen Pfad.
     *
     * @param string  $path      Pfad, welcher geladen werden soll
     * @param boolean $recursive rekursiv laden, oder nur der aktuelle level
     * 
     * @return array
     * 
     * @throws DirectoryDoesNotExist
     * @throws FileException
     */
    public function loadFolderContent($path, $recursive = false) : array
    {
        if (!is_dir($path)) {
            throw new DirectoryDoesNotExist('Path '.$path.' invalid or does not exists!');
        }

        $directoryIterator = new \DirectoryIterator($path);
        $dirContent = [];

        for ($directoryIterator->rewind(); $directoryIterator->valid(); $directoryIterator->next()) {
            /** @var $file \DirectoryIterator **/
            $file = $directoryIterator->current();
            if ("." === $file->getBasename()[0]) {
                continue;
            }
            if ($file->isDir()) {
                if ($recursive) {
                    $dirContent["d-".$file->getBasename()] = $this->loadFolderContent($file->getPathname(), $recursive);
                } else {
                    $dirContent["d-".$file->getBasename()] = $file->getBasename();
                }
            } else {
                $dirContent["f-".$file->getBasename()] = $file->getBasename();
            }
        }
        ksort($dirContent);

        return $dirContent;
    }
}