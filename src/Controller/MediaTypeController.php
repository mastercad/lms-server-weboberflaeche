<?php
namespace App\Controller;

use App\Entity\Mapping;
use App\Entity\MediaType;
use App\Form\MappingType;
use App\Form\MediaTypeType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MediaTypeController extends AbstractController
{
    /**
     * @Route("/media-types", name="media-types-index")
     *
     * @param Request $request
     *
     * @return Response|JsonResponse
     */
    public function indexAction(Request $request)
    {
        $responseType = $request->get('response-type');
        $mediaTypes = $this->getDoctrine()->getRepository(MediaType::class)->findAll();

        if ('json' === $responseType) {
            return new JsonResponse($mediaTypes);
        }
        return $this->render('media-types/index.html.twig', [
            'mediaTypes' => $mediaTypes
        ]);
    }

    /**
     * @Route("/media-types/show/{id}", name="media-types-show")
     *
     * @param Request $request
     * @param int $id
     *
     * @return Response
     * @return JsonResponse
     */
    public function showAction(Request $request, int $id)
    {
        $responseType = $request->get('response-type');
        $mediaType = $this->getDoctrine()->getRepository(MediaType::class)->find($id);

        if ('json' === $responseType) {
            return new JsonResponse($mediaType);
        }
        return $this->render('media-types/show.html.twig', [
            'id' => $id,
            'mediaType' => $mediaType
        ]);
    }

    /**
     * @Route("/media-types/edit/{id}", name="media-types-edit")
     *
     * @param Request $request
     * @param int $id
     *
     * @return Response
     * @return JsonResponse
     */
    public function editAction(Request $request, int $id)
    {
        $responseType = $request->get('response-type');
        $client = $this->getDoctrine()->getRepository(MediaType::class)->find($id);

        if ('json' === $responseType) {
            return new JsonResponse($client);
        }
        $form = $this->createForm(MediaTypeType::class, $client);
        $form->handleRequest($request);

        return $this->render('media-types/edit.html.twig', [
            'form' => $form->createView(),
            'id' => $id
        ]);
    }

    /**
     * @Route("/media-types/save", name="media-types-save", methods={"POST"})
     */
    public function saveAction(Request $request)
    {
        $responseType = $request->get('response_type');
        $result = ['success' => false];

        $id = $request->get('mapping_type')['id'];
        $entityManager = $this->getDoctrine()->getManager();

        if ($id) {
            $client = $entityManager->getRepository(Mapping::class)->find($id);
        } else {
            $client = new Mapping();
        }

        $form = $this->createForm(MappingType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($client);
            $entityManager->flush();

            $result['success'] = true;

//            return $this->redirectToRoute('task_success');
        }

        if ('json' === $responseType) {
            return new JsonResponse($result);
        }

        return $this->render('client/save.html.twig', [
            'result' => $result
        ]);
    }
}
