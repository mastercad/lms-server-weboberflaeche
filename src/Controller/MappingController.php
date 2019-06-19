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
     * Action to Sync given Mappings to given Clients.
     *
     * @Route("/mapping/sync", name="mapping-sync")
     *
     * @param Request $request
     *
     * @return Response
     * @return JsonResponse
     */
    public function syncAction(Request $request) {
        $mappings = $request->get('mappings');
        $clients = $request->get('clients');
        $responseType = $request->get('response_type');
        $response = ['success' => true];

        if ('json' === $responseType) {
            return new JsonResponse($response);
        }
        return $this->render('mapping/sync.html.twig');
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

        if ($id && !$mapping) {
            throw $this->createNotFoundException('Kein Mapping mit ID '.$id.' gefunden!');
        } else if (!$id && !$mapping) {
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
    public function saveAction(Request $request) {

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

//            return $this->redirectToRoute('task_success');
        } else {
            $content = preg_replace('/^.*?<div class="modal/is', '<div class="modal', $this->render('mapping/edit.html.twig', [
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
}
