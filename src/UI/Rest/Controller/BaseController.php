<?php

namespace App\UI\Rest\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\User\UserInterface;

class BaseController extends AbstractController{
    protected function getCurrentUserId(): ?string{
        $user = $this->getLoggedInUser();

        return $user?->getUserIdentifier();
    }

    protected function getLoggedInUser(): ?UserInterface{
        return $this->getUser();
    }
}
