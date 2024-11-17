<?php

namespace App\Domain\User\Event\Subscriber;

use App\Domain\User\Event\UserCreated;
use App\Domain\User\Repository\UserRepositoryInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
final readonly class UserCreatedEmailNotifier{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private MailerInterface         $mailer,
    ){}

    public function __invoke(UserCreated $event,): void{
        $user = $this->userRepository->findById($event->userId());

        if(null === $user){
            return;
        }

        $email = (new Email())->from('no-reply@example.com')
                              ->to(
                                  $user->email()
                                       ->toString()
                              )
                              ->subject('Welcome to our platform!')
                              ->html('<p>Thank you for registering!</p>');

        $this->mailer->send($email);
    }
}
