<?php
/**
 * Klasse für die Verarbeitung von Clients
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

use App\Entity\Client;
use App\Form\ClientType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\NetworkScanner;
use Symfony\Component\HttpClient\Exception\TransportException;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response as GuzzleHttpResponse;
use GuzzleHttp\RequestOptions;

/**
 * Klasse für alle notwendigen Frontend Interaktionen für einen Client.
 *
 * @category   PHP
 * @package    LmsClient
 * @subpackage Controller
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @license    GPL http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link       http://lms-client.byte-artist.de
 */
class ClientController extends AbstractController
{
    /**
     * Lädt eine Übersicht aller bekannten Clients.
     *
     * @param Request $request Der gesendete Request vom Frontend.
     *
     * @access public
     *
     * @return Response|JsonResponse
     *
     * @Route("/clients", name="client-index")
     */
    public function indexAction(Request $request)
    {
        $responseType = $request->get('response_type');
        $clients = $this->getDoctrine()->getRepository(Client::class)->findAll();

        if ('json' === $responseType) {
            $serializer = $this->container->get('serializer');
            $clients = $serializer->serialize($clients, 'json');
            return new JsonResponse(\json_decode($clients));
        }
        return $this->render(
            'client/index.html.twig',
            [
                'clients' => $clients
            ]
        );
    }

    /**
     * List all Available Clients in Network
     *
     * @param NetworkScanner $networkScanner Service for scanning network.
     *
     * @Route("/clients/online", name="clients-online-index")
     *
     * @return JsonResponse
     */
    public function indexOnlineAction(NetworkScanner $networkScanner)
    {
        if (1 == $_ENV['SCAN_ACTIVE']
            || "true" === $_ENV['SCAN_ACTIVE']
        ) {
            return new JsonResponse($networkScanner->scan());
        }

        if (!isset($_ENV['CLIENTS'])) {
            return new JsonResponse(['code' => 500, 'content' => 'Keine Clients hinterlegt und Scan deaktiviert!']);
        }

        try {
            $clients = \json_decode($_ENV['CLIENTS']);
            if (is_array($clients)) {
                return new JsonResponse($clients);
            }
        } catch (\InvalidArgumentException $exception) {
            return new JsonResponse(
                ['code' => 500, 'content' => 'Scan deaktiviert, aber Clients im falschen Format hinterlegt!']
            );
        }
    }

    /**
     * @todo wenn ich mit testen und abstimmen fertig bin, muss das hier alles in
     * einen service ausgelagert werden!
     *
     * Load Details from given online Client.
     *
     * @Route("/client/detail/{url}", name="client-online-details")
     *
     * @return JsonResponse
     */
    public function loadOnlineClientDetails($url)
    {
        $client = new GuzzleHttpClient();
        
        try {
            $response = $client->request('GET', base64_decode($url).'/api/client/detail');
            $code = $response->getStatusCode();
            $content = (string) $response->getBody()->getContents();

            if ($code >= 200
                && $code < 400
                && 0 < strlen($content)
            ) {
                return new JsonResponse(
                    [
                        'code' => $code,
                        'content' => json_decode($content)
                    ]
                );
            }
        } catch (RequestException $exception) {
            /** @var GuzzleHttpResponse $response */
            $response = $exception->getResponse();

            if ($response) {
                $code = $response->getStatusCode();
                $content = $response->getBody();

                $jsonContent = json_decode($content);

                if (!$jsonContent instanceof \stdClass) {
                    return new JsonResponse(
                        [
                            'code' => 500,
                            'content' => "no lms client!"
                        ]
                    );
                }
                $repository = $this->getDoctrine()->getRepository(Client::class);
                $clientEntity = $repository->findOneBy(['ip' => $jsonContent->ip]);

                // @TODO wenn client nicht gefunden wurde mir IP, nach namen suchen
                // wird der gefunden, soll eine meldung ans frontend gegeben werden
                // dass der gefundene name bereits vergeben ist und ob die daten
                // überschrieben werden sollen.

                // aktuell wird es einfach überschrieben, weil die anforderung ist,
                // dass die daten von den client prämisse haben
                if (!$clientEntity) {
                    $clientEntity = $repository->findOneBy(['name' => $jsonContent->name]);
                }

                if (!$clientEntity) {
                    $clientEntity = new Client();
                }

                // client in die datenbank schreiben oder vorhandenen eintrag updaten
                if ($content) {
                    $clientEntity->setIp($jsonContent->ip);
                    $clientEntity->setName($jsonContent->name);
                    $clientEntity->setMacAddress($jsonContent->macAddress);

                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($clientEntity);
                    $entityManager->flush();

                    // neuen eintrag an den client senden
                    if (!property_exists($jsonContent, 'id')) {
                        $client = new GuzzleHttpClient();
                        $response = $client->post(
                            base64_decode($url).'/api/client',
                            [
                                RequestOptions::FORM_PARAMS => [
                                    'client' => [
                                        'id'          => $clientEntity->getId(),
                                        'ip'          => $clientEntity->getIp(),
                                        'mac_address' => $clientEntity->getMacAddress(),
                                        'name'        => $clientEntity->getName()
                                    ]
                                ],
                                RequestOptions::HEADERS => [
                                    'Accept' => 'application/x-www-form-urlencoded',
                                    'Content-type' => 'application/x-www-form-urlencoded'
                                ]
                            ]
                        );
                    }

                    return new JsonResponse(
                        [
                            'code' => 200,
                            'content' => json_decode($response->getBody()->getContents())
                        ]
                    );
                } else {
                    return new JsonResponse(
                        [
                            'code' => 500,
                            'content' => "Client not found!"
                        ]
                    );
                }
            }
            
            return new JsonResponse(
                [
                    'code' => 500,
                    'content' => $exception->getMessage()
                ]
            );
        } catch (TransportException $exception) {
            return new JsonResponse(
                [
                    'code' => 500,
                    'content' => $exception->getMessage()
                ]
            );
        }
        return new JsonResponse(
            [
                'code' => 500,
                'content' => $content
            ]
        );
    }

    /**
     * Lädt den modal Inhalt für die Client Auswahl.
     *
     * @return Response
     *
     * @access public
     *
     * @Route("/client/select-dialog", name="client-select-dialog")
     */
    public function selectDialogAction() : Response
    {
        $clients = $this->getDoctrine()->getRepository(Client::class)->findAll();

        return $this->render(
            'client/select-dialog.html.twig',
            [
                'clients' => $clients
            ]
        );
    }

    /**
     * Lädt die Detailansicht zu einem bestimmten Client.
     *
     * @param Request $request Der gesendete Request vom Frontend.
     * @param int     $id      Die Id des gewünschten DB Eintrages zum Client.
     *
     * @return Response|JsonResponse
     *
     * @access public
     *
     * @Route("/client/show/{id<\d+>}", name="client-show", requirements={"id"="\d+"})
     */
    public function showAction(Request $request, int $id) : Response
    {
        $responseType = $request->get('response_type');
        $client = $this->getDoctrine()->getRepository(Client::class)->find($id);

        if ('json' === $responseType) {
            return new JsonResponse($client);
        }
        return $this->render(
            'client/show.html.twig', 
            [
                'id' => $id,
                'client' => $client
            ]
        );
    }

    /**
     * Lädt die Ansicht zum Bearbeiten eines Clients.
     *
     * @param Request $request Der gesendete Request vom Frontend.
     * @param int     $id      Die Id des gewünschten DB Eintrages zum Client.
     *
     * @return Response|JsonResponse
     *
     * @access public
     *
     * @Route("/client/edit/{id<\d+>}", name="client-edit", requirements={"id"="\d+"})
     */
    public function editAction(Request $request, int $id) : Response
    {
        $responseType = $request->get('response_type');
        $client = $this->getDoctrine()->getRepository(Client::class)->find($id);

        if ('json' === $responseType) {
            return new JsonResponse($client);
        }
        $form = $this->createForm(ClientType::class, $client);

        return $this->render(
            'client/edit.html.twig', 
            [
                'form' => $form->createView(),
                'id' => $id
            ]
        );
    }

    /**
     * Action zum Speichern des bearbeiteten Clients.
     *
     * @param Request $request Der gesendete Request vom Frontend.
     *
     * @return Response|JsonResponse
     *
     * @access public
     *
     * @Route("/client/save", name="client-save", methods={"POST"})
     */
    public function saveAction(Request $request) : Response
    {
        $responseType = $request->get('response_type');
        $result = ['success' => false];

        $id = $request->get('client')['id'];
        $entityManager = $this->getDoctrine()->getManager();

        if ($id) {
            $client = $entityManager->getRepository(Client::class)->find($id);
        } else {
            $client = new Client();
        }

        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($client);
            $entityManager->flush();

            $result['success'] = true;
        } else {
            $content = preg_replace(
                '/^.*?<div class="modal/is',
                '<div class="modal',
                $this->render(
                    'client/edit.html.twig',
                    [
                        'form' => $form->createView(),
                        'id' => $id
                    ]
                )
            );

            $result['content'] = $content;
        }

        if ('json' === $responseType) {
            return new JsonResponse($result);
        }

        return $this->render(
            'client/save.html.twig',
            [
                'result' => $result
            ]
        );
    }

    /**
     * Action zum Löschen eines übergebenen Eintrages.
     *
     * @param Request $request Die gesendete Anfrage vom Frontend.
     * @param int     $id      Id des zu löschenden Client Eintrages.
     *
     * @return Response|JsonResponse
     *
     * @access public
     *
     * @Route("/client/delete/{$id<\d+>}", name="client-delete", requirements={"id"="\d+"})
     */
    public function deleteAction(Request $request, int $id) : Response
    {
        $responseType = $request->get('response_type');
        $id = $request->get('client')['id'];
        $entityManager = $this->getDoctrine()->getManager();
        $client = $entityManager->getRepository(Client::class)->find($id);
        
        $entityManager->remove($client);
        $entityManager->flush();

        $result['success'] = true;

        if ('json' === $responseType) {
            return new JsonResponse($result);
        }

        return $this->render(
            'client/save.html.twig', 
            [
                'result' => $result
            ]
        );
    }
}
