<?php
namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\DirectoryValidator;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use App\Entity\Client;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\RequestOptions;
use Doctrine\DBAL\Exception\ServerException;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Psr7\Response;

class TransferController extends AbstractController
{
    /**
     * List all Files in given path
     * 
     * @Route("/transfer/list/{path}", name="transfer_list_path")
     * 
     * @return JsonResponse
     */
    public function listDirectory(DirectoryValidator $directoryValidator, File $fileService, string $path)
    {
        $path = $_ENV['MEDIA_PATH'].'/'.base64_decode($path);
        try {
            $directoryValidator->validate($path);

            return new JsonResponse($fileService->loadFolderContent($path));
        } catch (InvalidParameterException $exception) {
            return new JsonResponse(['code' => $exception->getCode(), 'content' => $exception->getMessage()]);
        }
    }

    /**
     * Action to Send file to client with clientside provided token.
     *
     * @Route("/transfer/send", name="transfer-send")
     *
     * @param Request $request
     *
     * @return Response
     * @return JsonResponse
     */
    public function sendAction(LoggerInterface $logger, Request $request)
    {
        $responseType = $request->get('response_type');
        $mapping = $request->get('mapping');
        $token = $request->get('token');
        $clientId = $request->get('client_id');

        $mapping['children'] = $clientId;

        $lmsClient = $this->getDoctrine()->getRepository(Client::class)->findOneBy(['id' => $clientId]);

        try {
#            $domain = 'byte-artist.de';
#            $values = ['PHP_SESSION_UPLOAD_PROGRESS' => $token];
##            $values = ['upload_progress_'.$token];
#            $cookieJar = CookieJar::fromArray($values, $domain);

            $httpClient = new GuzzleHttpClient([
#                'base_uri' => $lmsClient->getIp(),
#                'cookies'  => $cookieJar
                'cookies'  => true,
                'debug' => false,
#                'decode_content' => false,
                'exceptions' => false,
                'verify' => false
            ]);

            /** @var Response $currentResponse */
            $currentResponse = $httpClient->post(
                $lmsClient->getIp().'/api/transfer/store',
                [
#                    RequestOptions::HEADERS => [
#                        'Accept' => 'application/json'
#                    ],
                    RequestOptions::MULTIPART => [
                        [
                            'name' => 'token',
                            'contents' => $token
                        ],
                        [
                            'name' => 'upload_progress_'.$token,
                            'contents' => 'mappings'
                        ],
                        [
                            'name' => 'upload',
                            'contents' => fopen($_ENV['MEDIA_PATH'].$mapping['lms_path'], 'rb'),
//                            'contents' => file_get_contents($mapping['lms_path']),
                            'filename' => basename($mapping['lms_path'])
                        ]
                    ]
                ]
            );
        } catch (ServerException $exception) {
            $logger->debug($exception->getResponse()->getBody()->getContents());
        }

        $logger->info("Response nach Send: ".print_r($currentResponse, true));
        $response = [
            'code' => $currentResponse->getStatusCode(),
            'content' => 200 === $currentResponse->getStatusCode() ? "file transfer finished" : $currentResponse->getBody()->getContents()
        ];

        if ('json' === $responseType) {
            return new JsonResponse($response);
        }
        return $this->render('transfer/send.html.twig');
    }

    /**
     * Make handshake between server and given client. Result is Token.
     *
     * @Route("/transfer/handshake", name="transfer-handshake")
     *
     * @param Request $request
     *
     * @return Response
     * @return JsonResponse
     */
    public function handshakeAction(LoggerInterface $logger, Request $request)
    {
        $responseType = $request->get('response_type');
        $mapping = $request->get('mapping');
        $clientId = $request->get('client_id');

        $mapping['client'] = $clientId;
        $mapping['media_type'] = 1;
        
        $client = $this->getDoctrine()->getRepository(Client::class)->findOneBy(['id' => $clientId]);

        try {
            $httpClient = new GuzzleHttpClient();
            
            /** Handle Token with client */
            $currentResponse = $httpClient->post(
                $client->getIp().'/api/transfer/handshake',
                [
                    RequestOptions::FORM_PARAMS => [
                        'mapping' => $mapping,
                        'file_size' => filesize($_ENV['MEDIA_PATH'].$mapping['lms_path'])
                    ],
                    RequestOptions::HEADERS => [
                        'Accept' => 'application/x-www-form-urlencoded',
                        'Content-type' => 'application/x-www-form-urlencoded'
                    ]
                ]
            );
            
            $token = json_decode($currentResponse->getBody())->token;
        } catch (ServerException $exception) {
            $logger->debug($exception->getResponse()->getBody()->getContents());
        }

        $responseType = $request->get('response_type');
        $response = ['success' => true, 'token' => $token];

        if ('json' === $responseType) {
            return new JsonResponse($response);
        }
        return $this->render('transfer/handshake.html.twig');
    }

    /**
     * Recives current progress for given token from given client.
     *
     * @Route("/transfer/progress/{token}/{clientId}", name="transfer-progress")
     *
     */
    public function progressAction(Request $request, $token, $clientId)
    {
#        session_write_close();
        $responseType = $request->get('response_type');
        $client = $this->getDoctrine()->getRepository(Client::class)->findOneBy(['id' => $clientId]);
        $httpClient = new GuzzleHttpClient();
        
        /** Handle Token with client */
        $response = $httpClient->get($client->getIp().'/api/transfer/progress/'.$token);

        if ('json' === $responseType) {
            return new JsonResponse(json_decode($response->getBody()->getContents()));
        }

    }

    /**
     * Recives current progress for given token from given client.
     *
     * @Route("/transfer/progress-send", name="transfer-progress-send")
     *
     */
    public function progressSendAction()
    {
        return $this->render('transfer/progress-send.html.twig');
    }
}