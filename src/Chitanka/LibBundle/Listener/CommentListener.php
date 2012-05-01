<?php
namespace Chitanka\LibBundle\Listener;

use Doctrine\ORM\EntityManager;
use FOS\CommentBundle\Event\CommentEvent;
use Chitanka\LibBundle\Service\Notifier;
use Chitanka\LibBundle\Entity\Comment;

class CommentListener
{
	private $mailer;
	private $em;

    public function __construct(\Swift_Mailer $mailer, EntityManager $em)
    {
        $this->mailer = $mailer;
		$this->em = $em;
    }

	public function onPostPersist(CommentEvent $event)
	{
		/* @var $comment Comment */
		$comment = $event->getComment();
		if ($comment->isForWorkEntry()) {
			$notifier = new Notifier($this->mailer);
			$notifier->sendMailByNewWorkroomComment($comment, $comment->getTarget($this->em));
		}
	}
}