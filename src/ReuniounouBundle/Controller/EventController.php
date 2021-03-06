<?php

namespace ReuniounouBundle\Controller;

use ReuniounouBundle\Entity\Evenement;
use ReuniounouBundle\Repository\EvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;


class EventController extends Controller
{
    /**
     * @Route("/event/create", name="create_event")
     */
    public function createAction(Request $request) {
        $error = null;
        $event = new Evenement();
        $session = $request->getSession();
        $id = $session->get('id');
        $formBuilder = $this->get('form.factory')->createBuilder(FormType::class, $event);

        $formBuilder
            ->add('titre', TextType::class, ['label'=> 'Titre', 'attr' => ['class' => "form-control", 'placeholder' => "Titre"]])
            ->add('description', TextareaType::class, ['label'=> 'Description', 'attr' => ['class' => "form-control"]])
            ->add('date', DateTimeType::class, ['label' => 'Date', 'attr' => ['class' => 'form-control']])
            ->add('adresse', TextType::class, ['label'=> 'Adresse', 'attr' => ['class' => "form-control", 'placeholder' => "Adresse"]])
            ->add('ville', TextType::class, ['label'=> 'Ville', 'attr' => [ 'class' => "form-control", 'placeholder' => "Ville"]])
            ->add('private', CheckboxType::class, ['label' => 'Privé', 'attr' => ['required' => 'false', 'class' => "form-control", 'placeholder' => "Ville"]])
            ->add('Ajouter l\'événement', SubmitType::class, ['attr' => ['class'=> 'btn btn-primary']]);
        $form = $formBuilder->getForm();
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $event = $form->getData();
            $manager = $this->getDoctrine()->getManager();
            $repositoryUsers = $manager->getRepository('ReuniounouBundle:Utilisateur');
            $user = $repositoryUsers->findOneById($id);
            $event->setUtilisateur($user);
            $token = $this->generateRandomString(10);
            $event->setTokenInvitation($token);
            $manager->persist($event);

            try
            {
                $manager->flush();
            }
            catch (PDOException $e)
            {
                $error = "UniqueConstraintViolationException";
            }
            catch (UniqueConstraintViolationException $e)
            {
                $error = "UniqueConstraintViolationException";
            }
        }
        return $this->render('@Reuniounou/Event/create.html.twig', ['form'=> $form->createView(), 'id' => $id, 'error'=> $error]);
    }

    /**
     * @Route("/event/{token}", name="show_event")
     */
    public function showAction(Request $request, $token) {
        $session = $request->getSession();
        $id = $session->get('id');
        $manager = $this->getDoctrine()->getManager();
        $repositoryEvents = $manager->getRepository('ReuniounouBundle:Evenement');
        $event = $repositoryEvents->findOneByTokenInvitation($token);
        if ($event !== null) {
            $visibility = ($event->getPrivate()) ? 'privé' : 'public';
        }
        return $this->render('@Reuniounou/Event/show.html.twig', [
            'event' => $event,
            'visibility' => $visibility,
            'id' => $id
        ]);
    }

    private function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * @Route("/event", name="manage_event")
     */
    public function manageAction(Request $request) {
        $session = $request->getSession();
        $id =$session->get('id');
        $manager = $this->getDoctrine()->getManager();
        $repositoryEvents = $manager->getRepository('ReuniounouBundle:Evenement');
        $events = $repositoryEvents->findByUtilisateur($session->get('id'));
        return $this->render('@Reuniounou/Event/manage.html.twig', [
            'events' => $events,
            'id' => $id
        ]);
    }
}
