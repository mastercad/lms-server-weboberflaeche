<?php
namespace App\Controller;

use App\Entity\Client;
use App\Entity\Mapping;
use App\Form\MappingType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Exception\ServerException;

class MappingController extends AbstractController
{
    /**
     * @Route("/mapping", name="mapping-index")
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $clientId = $request->get('client_id');

        $mappings = $this->getDoctrine()->getRepository(Mapping::class)->findBy(['client' => $clientId]);

        return $this->render(
            'mapping/index.html.twig',
            [
                'mappings' => $mappings
            ]
        );
    }

    /**
     * @Route("/mapping/show/{id}", name="mapping-show")
     *
     * @param $id
     *
     * @return Response
     */
    public function showAction(Mapping $mapping)
    {
#        $mapping = $this->getDoctrine()->getRepository(Mapping::class)->find($id);

#        if ($id && !$mapping) {
#            throw $this->createNotFoundException('Kein Mapping mit ID '.$id.' gefunden!');
#        }

        return $this->render('mapping/show.html.twig', [
            'mapping' => $mapping
        ]);
    }

    /**
     * @Route("/mapping/edit/{id}", name="mapping-edit")
     *
     * @param $id
     *
     * @return Response
     */
    public function editAction(Request $request, $id)
    {
        $clientId = $request->get('client');

        /** @var Mapping $mapping */
        $mapping = $this->getDoctrine()->getRepository(Mapping::class)->find($id);

        if ($id
            && !$mapping
        ) {
            throw $this->createNotFoundException('Kein Mapping mit ID '.$id.' gefunden!');
        } elseif (!$id && !$mapping) {
            $mapping = new Mapping();
        }
        if ($clientId) {
            /** @var Client $client */
            $client = $this->getDoctrine()->getRepository(Client::class)->find($clientId);
            $mapping->setClient($client);
        }

        $form = $this->createForm(MappingType::class, $mapping);
        $form->handleRequest($request);

        return $this->render('mapping/edit.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/mapping/save", name="mapping-save", methods={"POST"})
     *
     * @param Request $request
     * @param Mapping $mapping
     *
     * @return Response|JsonResponse
     */
    public function saveAction(Request $request)
    {

        $responseType = $request->get('response_type');
        $result = ['success' => false];

        $id = $request->get('mapping')['id'];
        $entityManager = $this->getDoctrine()->getManager();

        if ($id) {
            $mapping = $entityManager->getRepository(Mapping::class)->find($id);
        } else {
            $mapping = new Mapping();
        }

        $form = $this->createForm(MappingType::class, $mapping);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($mapping);
            $entityManager->flush();

            $result['success'] = true;
        } else {
            $content = preg_replace('/^.*?<div class="modal/is', '<div class="modal', $this->render('mapping/edit.html.twig', 
            [
                'form' => $form->createView(),
                'id' => $id
            ]));

            $result['content'] = $content;
        }

        if ('json' === $responseType) {
            return new JsonResponse($result);
        }

        return $this->render('client/save.html.twig', [
            'result' => $result
        ]);
    }

    /**
     * Action to Sync given Mappings to given Clients.
     *
     * @Route("/mapping/sync", name="mapping-sync")
     *
     * @param Request $request
     *
     * @return Response
     * @return JsonResponse
     */
    public function syncAction(LoggerInterface $logger, Request $request)
    {
        $mappingsRequest = $request->get('mappings');
        $clientsRequest = $request->get('clients');

        $clients = $this->getDoctrine()->getRepository(Client::class)->findBy(['id' => $clientsRequest]);
        $responseSummary = [];

        /** @var Client $lmsClient */
        foreach ($clients as $lmsClient) {
            /** @var Mapping $mapping */
            foreach ($mappingsRequest as $mapping) {
                try {
                    $httpClient = new GuzzleHttpClient();
                    
                    /** Handle Token with client */
                    $currentResponse = $httpClient->post(
                        $lmsClient->getIp().'/api/mapping/sync',
                        [
                            RequestOptions::FORM_PARAMS => [
                                'mapping' => $mapping,
                                'file_size' => filesize($mapping['lms_path'])
                            ],
                            RequestOptions::HEADERS => [
                                'Accept' => 'application/x-www-form-urlencoded',
                                'Content-type' => 'application/x-www-form-urlencoded'
                            ]
                        ],
                        RequestOptions::MULTIPART
                    );
                    
                    $token = json_decode($currentResponse->getBody())->token;
                    
                    $currentResponse = $httpClient->post(
                        $lmsClient->getIp().'/api/transfer/store',
                        [
                            RequestOptions::MULTIPART => [
                                [
                                    'name' => 'upload',
                                    'contents' => fopen($mapping['lms_path'], 'rb'),
                                    'filename' => basename($mapping['lms_path'])
                                ]
                            ]
                        ]
                    );

                    $responseSummary[] = [
                        'clientName' => $lmsClient->getName(),
                        'clientId' => $lmsClient->getId(),
                        'mappingId' => $mapping['id'],
                        'token' => $token,
                        'request' => json_decode($currentResponse->getBody()),
                    ];
                } catch (ServerException $exception) {
                    $logger->debug($exception->getResponse()->getBody()->getContents());
                }
            }
        }

        $responseType = $request->get('response_type');
        $response = ['success' => true, 'content' => json_encode($responseSummary)];

        if ('json' === $responseType) {
            return new JsonResponse($response);
        }
        return $this->render('mapping/sync.html.twig');
    }
}
