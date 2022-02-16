<?php

namespace App\Controller;

use App\Entity\Memo;
use App\Form\MemoType;
use App\Repository\MemoRepository;
use Doctrine\DBAL\Types\DateImmutableType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/")
 */
class MemoController extends AbstractController
{
    /**
     * @Route("/", name="memo_index", methods={"GET"})
     */
    public function index(MemoRepository $memoRepository): Response
    {
        date_default_timezone_set('Europe/Paris');
        $currentTime = date('"Y-m-d H:i:s"');
        return $this->render('memo/index.html.twig', [
            'memos' => $memoRepository->findAll(),
        ]);
    }

    /**
     * @Route("/expired", name="memo_expired")
     */
    public function expired(): Response
    {
        return $this->render('memo/expired.html.twig');
    }

    /**
     * @Route("/new", name="memo_new", methods={"GET", "POST"})
     */
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        date_default_timezone_set('Europe/Paris');
        $memo = new Memo();
        $form = $this->createForm(MemoType::class, $memo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $memo->setCreatedAt(new \DateTimeImmutable('now'));
            $memo->setUpdatedAt(new \DateTimeImmutable('now'));
            $entityManager->persist($memo);
            $entityManager->flush();

            return $this->redirectToRoute('memo_show', ['id' => $memo->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('memo/new.html.twig', [
            'memo' => $memo,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="memo_show", methods={"GET"})
     */
    public function show(Memo $memo): Response
    {
        date_default_timezone_set('Europe/Paris');
        $tempTime = $memo->getExpiredTime();
        $createdAt = $memo->goodFormatTime($memo->getCreatedAt());
        $expiredTime = date("Y-m-d H:i:s", strtotime("$createdAt +$tempTime sec"));
        $currentTime = date("Y-m-d H:i:s");
        echo $expiredTime;
        echo $currentTime;

        if($expiredTime < $currentTime) {
            return $this->redirectToRoute('memo_expired', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('memo/show.html.twig', [
            'memo' => $memo,
            'expiredTime' =>  $expiredTime,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="memo_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Memo $memo, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MemoType::class, $memo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('memo_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('memo/edit.html.twig', [
            'memo' => $memo,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="memo_delete", methods={"POST"})
     */
    public function delete(Request $request, Memo $memo, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$memo->getId(), $request->request->get('_token'))) {
            $entityManager->remove($memo);
            $entityManager->flush();
        }

        return $this->redirectToRoute('memo_index', [], Response::HTTP_SEE_OTHER);
    }
}
