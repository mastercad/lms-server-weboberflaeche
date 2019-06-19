<?php
namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\File;
use Symfony\Component\HttpFoundation\JsonResponse;

class TransferController extends AbstractController 
{
    /**
     * Undocumented function
     * List all Mappings
     * 
     * @Route("/transfer/list/{path}", name="transfer_list_path")
     * 
     * @return JsonResponse
     */
    public function listDirectory(File $fileService, string $path)
    {
        $path = base64_decode($path);
        return new JsonResponse($fileService->loadFolderContent($path));
    }
}