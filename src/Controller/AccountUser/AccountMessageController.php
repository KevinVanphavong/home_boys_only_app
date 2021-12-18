<?php

namespace App\Controller\AccountUser;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\Partygoer;
use App\Repository\ConversationRepository;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * @Route("my-account", name="my_account")
 */
class AccountMessageController extends AbstractController
{
    // my-account/party/creation
    // my-account/party/edition/{event.id}/{event.name}

    // ----------

    // my-account/notifications         (page des notifications)
    // my-account/direct-messages       (page des messages privées avec un organisateur de soirée)

    // my-account/profil/edition        (page des informations personnelles modifiables)
    // my-account/party/dashboard       (page deu dashboard)

    // my-account/favlist-invitations   (page des favlist et invitations)
    // my-account/last-events           (page des dernières soirées)

    /**
     * @Route("/direct-messages", name="_direct_messages")
     */
    public function getDirectMessagesPage(ConversationRepository $conversationRepository): Response
    {
        $partygoer = $this->getUser()->getPartygoer();

        $convs = $conversationRepository->findAllTheConversation($partygoer);

        $arrayPathFolder = explode('/', $this->getParameter('profile_pictures'));
        $publicFolderProfilePicture = $arrayPathFolder[count($arrayPathFolder) - 2] . '/' . $arrayPathFolder[count($arrayPathFolder) - 1]; 

        return $this->render('account_user/direct-messages.html.twig', [
            'convs' => $convs,
            'partygoer' => $partygoer,
            'publicFolderProfile' => $publicFolderProfilePicture,
        ]);
    }

    /**
     * @Route("/send-message/{authorId}/{convId}", name="_send_message", methods={"GET", "POST"})
     * @ParamConverter("author", options={"id" = "authorId"})
     * @ParamConverter("conversation", options={"id" = "convId"})
     */
    public function sendMessage(Request $request, Partygoer $author, Conversation $conversation)
    {
        $messageContent = $request->getContent();

        if($conversation->getUserGuest() == $author){
            $recipient = $conversation->getUserPlanner();
        } else {
            $recipient = $conversation->getUserGuest();
        }
        
        if($request && $messageContent){
            $dateTime = new DateTime();
            $message = new Message();
            $message->setAuthor($author);
            $message->setRecipient($recipient);
            $message->setContent($messageContent);
            $message->setCreatedAt($dateTime);
            $message->setSendAt($dateTime);
            $message->setConversation($conversation);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($message);
            $entityManager->flush();

            return $this->json([
                'messageContent' => $messageContent,
                'recipientFirstname' => $recipient->getFirstname(),
                'recipientLastname' => $recipient->getLastname(),
                'messageSendAt' => $dateTime->format('d-m-Y H:i')
            ]);
        } else {
            return $this->json([
                '500' => "La requête n'a pas été récupéré",
            ]);
        }

    }
}