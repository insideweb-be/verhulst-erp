<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class EmailVerifier
{
    public function __construct(
        private readonly VerifyEmailHelperInterface $verifyEmailHelper,
        private readonly MailerInterface $mailer,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendEmailConfirmation(string $verifyEmailRouteName, UserInterface $user, TemplatedEmail $email, array $extraParams = []): void
    {
        /** @var User $user */
        $extraParams = array_merge(['id' => $user->getId()], $extraParams);

        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            $verifyEmailRouteName,
            (string) $user->getId(),
            $user->getEmail(),
            $extraParams,
        );

        $context = $email->getContext();
        $context['signedUrl'] = $signatureComponents->getSignedUrl();
        $context['expiresAtMessageKey'] = $signatureComponents->getExpirationMessageKey();
        $context['expiresAtMessageData'] = $signatureComponents->getExpirationMessageData();

        $email->context($context);

        $this->mailer->send($email);
    }

    /**
     * @throws VerifyEmailExceptionInterface
     */
    public function handleEmailConfirmation(Request $request, UserInterface $user): void
    {
        /* @var User $user */

        $this->verifyEmailHelper->validateEmailConfirmation($request->getUri(), (string) $user->getId(), $user->getEmail());

        $user->setVerified(true)
            ->setEnabled(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
