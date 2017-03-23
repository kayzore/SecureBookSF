<?php

namespace SB\Bundle\ActivityBundle\Controller;

use SB\Bundle\ActivityBundle\Entity\Activity;
use SB\Bundle\ActivityBundle\Entity\Comment;
use SB\Bundle\ActivityBundle\Entity\Likes;
use SB\Bundle\ActivityBundle\Form\Type\ActivityType;
use SB\Bundle\NotificationBundle\Entity\Notification;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ActivityController extends Controller
{
    public function addActivityAction(Request $request)
    {
        $activityService = $this->container->get('sb_activity.activity');
        $activity = new Activity();
        $form = $activityService->getForm($activity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $this->container->get('sb_activity.activity')->addActivity($this->getUser(), $activity);

            return $this->redirectToRoute('sb_core_homepage');
        }
        return $this->createAccessDeniedException('Acces Denied');
    }

    public function addActivityOnProfilAction(Request $request)
    {
        $activityService = $this->container->get('sb_activity.activity');
        $activity = new Activity();
        $form = $activityService->getForm($activity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();

            $activityService->addActivity($user, $activity);

            return $this->redirectToRoute('sb_user_profil', array('slugUsername' => $user->getSlug()));
        }
        return $this->createAccessDeniedException('Acces Denied');
    }

    public function addLikeAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $id_activity = $request->request->get('id_activity');
            $user = $this->getUser();

            $em = $this->getDoctrine()->getManager();
            $activity = $em->getRepository('SBActivityBundle:Activity')->findOneBy(array('id' => $id_activity));

            $like = $em->getRepository('SBActivityBundle:Likes')->findOneBy(array(
                'user' => $user,
                'activity' => $activity
            ));

            if (is_null($like)) {
                $activity->addOneLike();

                $likes = new Likes();
                $likes->setActivity($activity);
                $likes->setUser($user);

                if ($user->getUsername() != $activity->getUser()->getUsername()) {
                    $faye = $this->container->get('sb_realtime.faye.client');
                    $channel = '/' . $activity->getUser()->getUsername();
                    $data    = array('type' => 'like', 'text' => $user->getUsername() . ' aime une de vos actualité');
                    $faye->send($channel, $data);
                    $notification = new Notification();
                    $notification->setUserFrom($user);
                    $notification->setUserTo($activity->getUser());
                    $notification->setActivity($activity);
                    $notification->setType('like');
                    $em->persist($notification);
                }
                $em->persist($likes);
                $em->persist($activity);
                $em->flush();
            }
            $activity_likes = $activity->getNbLikes();
            return new JsonResponse(array('activity_likes' => $activity_likes));
        }
        return $this->createAccessDeniedException('Acces Denied');
    }

    public function dislikeAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $id_activity = $request->request->get('id_activity');
            $user = $this->getUser();

            $em = $this->getDoctrine()->getManager();
            $activity = $em->getRepository('SBActivityBundle:Activity')->findOneBy(array('id' => $id_activity));

            $like = $em->getRepository('SBActivityBundle:Likes')->findOneBy(array(
                'user' => $user,
                'activity' => $activity
            ));

            if (!is_null($like)) {
                $activity->removeOneLike();

                $em->remove($like);
                $em->persist($activity);
                $em->flush();
            }
            $activity_likes = $activity->getNbLikes();
            return new JsonResponse(array('activity_likes' => $activity_likes));
        }
        return $this->createAccessDeniedException('Acces Denied');
    }

    public function addCommentAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $comment_text = $request->request->get('comment_text');
            $activity_id = $request->request->get('id_activity');
            $user = $this->getUser();

            $em = $this->getDoctrine()->getManager();
            $activity = $em->getRepository('SBActivityBundle:Activity')->findOneBy(array('id' => $activity_id));

            $activity->addOneComment();

            $comment = new Comment();
            $comment->setActivity($activity);
            $comment->setUser($user);
            $comment->setText($comment_text);

            $faye = $this->container->get('sb_realtime.faye.client');
            $channel = '/' . $activity->getUser()->getUsername();
            $data    = array('type' => 'comment', 'text' => $user->getUsername() . ' à commenté une de vos actualité');
            $faye->send($channel, $data);
            $notification = new Notification();
            $notification->setUserFrom($user);
            $notification->setUserTo($activity->getUser());
            $notification->setActivity($activity);
            $notification->setType('comment');

            $em->persist($notification);
            $em->persist($activity);
            $em->persist($comment);
            $em->flush();

            $activity_comments = $activity->getNbComments();
            return new JsonResponse(array('activity_comments' => $activity_comments));
        }
        return $this->createAccessDeniedException('Acces Denied');
    }

    public function activityGetCommentAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $activity_id = $request->request->get('id_activity');

            $em = $this->getDoctrine()->getManager();
            $comments = $em->getRepository('SBActivityBundle:Comment')->fetchAll($activity_id, 3);

            return $this->render('SBActivityBundle:comments:comment.html.twig', array(
                'comments' => $comments
            ));
        }
        return $this->createAccessDeniedException('Acces Denied');
    }

    public function getActivityAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $activity_last_id = (int)$request->request->get('id_last_activity');

            if (is_int($activity_last_id)) {
                $loadmore = $this->container->get('sb_activity.loadmore');
                $activity = $loadmore->loadOlderActivity($this->getUser(), 3, $activity_last_id, true);

                return $this->render('SBActivityBundle:activity:activity.html.twig', array(
                    'list_activity' => $activity
                ));
            }
        }
        return $this->createAccessDeniedException('Acces Denied');
    }

    public function getMyActivityAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $activity_last_id = (int)$request->request->get('id_last_activity');

            if (is_int($activity_last_id)) {
                $loadmore = $this->container->get('sb_activity.loadmore');
                $activity = $loadmore->loadOlderActivity($this->getUser(), 3, $activity_last_id);

                return $this->render('SBActivityBundle:activity:activity.html.twig', array(
                    'list_activity' => $activity
                ));
            }
        }
        return $this->createAccessDeniedException('Acces Denied');
    }
}