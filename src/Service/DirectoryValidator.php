<?php
namespace App\Service;

use Symfony\Component\Routing\Exception\InvalidParameterException;

class DirectoryValidator
{
    /**
     * Validates given path against MEDIA_PATH in environment.
     *
     * @param string $path
     * 
     * @return true
     * 
     * @throws InvalidParameterException
     */
    public function validate($path)
    {
        if (0 === \strpos($path, $_ENV['MEDIA_PATH'])) {
            return true;
        }
        throw new InvalidParameterException('Unbekannter Datei Pfad!', 404);
    }
}