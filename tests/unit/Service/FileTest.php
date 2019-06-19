<?php
namespace Test\Unit\Service;

use PHPUnit\Framework\TestCase;
use App\Service\File;
use Doctrine\Migrations\Tools\Console\Exception\DirectoryDoesNotExist;

class FileTest extends TestCase
{
    private $basePath = __DIR__.'/../../../var/tmp';

    public function testInstanciateFileClass()
    {
        $this->assertInstanceOf(File::class, new File(), 'File Class not exists!');
    }

    public function testLoadFolderContentMethodExists()
    {
        $fileService = new File();
        $this->assertTrue(is_array($fileService->loadFolderContent('/opt/')));
    }

    public function testThrowExceptionIfPathNotExists() 
    {
        $this->expectException(DirectoryDoesNotExist::class);
        $this->expectExceptionMessage('Path test invalid or does not exists!');

        $fileService = new File();
        $fileService->loadFolderContent('test');
    }

    public function testFlatPathScan()
    {
        $fileService = new File();
        $structure = [
            'test' => 'test',
            'testFolder' => [
                'test' => 'test',
                '1Test' => '1Test'
            ]
        ];
        $expectation = [
            'f-test' => 'test',
            'd-testFolder' => 'testFolder'
        ];

        $this->prepareDirectory($structure)
            ->assertEquals($expectation, $fileService->loadFolderContent($this->basePath));

        $this->removeDirectory($structure);
    }

    /**
     * @depends testFlatPathScan
     */
    public function testRecursivePathScan()
    {
        $fileService = new File();
        $structure = [
            'test' => 'test',
            'testFolder' => [
                'test' => 'test',
                '1Test' => '1Test'
            ]
        ];
        $expectation = [
            'f-test' => 'test',
            'd-testFolder' => [
                'f-test' => 'test',
                'f-1Test' => '1Test'
            ]
        ];

        $this->prepareDirectory($structure)
            ->assertEquals($expectation, $fileService->loadFolderContent($this->basePath, true));

        $this->removeDirectory($structure);
    }

    /**
     * Creates given Directory Structure recursive.
     *
     * @param [string] $structure
     * @param string $startFolder
     * 
     * @return FileTest
     */
    private function prepareDirectory($structure, $startFolder = '')
    {
        $absolutePath = $this->basePath.$startFolder.'/';
        @\mkdir($absolutePath, 0777, true);
        foreach ($structure as $fileName => $content) {
            if (is_array($content)) {
                \mkdir($absolutePath.$fileName, 0777, true);
                $this->prepareDirectory($content, $startFolder.'/'.$fileName);
            } else {
                touch($absolutePath.$fileName);
            }
        }
        return $this;
    }

    /**
     * Removes given Directory Structure recursive.
     *
     * @param [string] $structure
     * @param string $startFolder
     * 
     * @return FileTest
     */
    private function removeDirectory($structure, $startFolder = '')
    {
        $absolutePath = $this->basePath.$startFolder.'/';
        foreach ($structure as $folderName => $content) {
            if (is_array($content)) {
                $this->removeDirectory($content, $startFolder.'/'.$folderName);
            } else {
                \unlink($absolutePath.$folderName);
            }
        }
        rmdir($absolutePath);
        return $this;
    }
}
