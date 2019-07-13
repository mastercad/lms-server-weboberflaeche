<?php
namespace App\Service;

use Symfony\Component\Routing\Exception\InvalidParameterException;

class DirectoryValidator
{
    private $mediaRootPath;

    /**
     * CTOR
     *
     * @param string $mediaRootPath
     */
    public function __construct(string $mediaRootPath)
    {
        $this->mediaRootPath = $mediaRootPath;
    }

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
        if (0 === \strpos($path, $this->mediaRootPath)) {
            return true;
        }
        throw new InvalidParameterException('Unbekannter Datei Pfad!', 404);
    }
}