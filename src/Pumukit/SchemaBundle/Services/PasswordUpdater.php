<?php

namespace Pumukit\SchemaBundle\Services;

use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Util\PasswordUpdaterInterface;
use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

/**
 * Overide FOS\UserBundle\Util\PasswordUpdater to allow disabling the generation of a random user salt,
 * it is required to use PuMuKIT as a CAS user provider.
 */
class PasswordUpdater implements PasswordUpdaterInterface
{
    private $encoderFactory;
    private $genUserSalt;

    public function __construct(EncoderFactoryInterface $encoderFactory, bool $genUserSalt = true)
    {
        $this->encoderFactory = $encoderFactory;
        $this->genUserSalt = $genUserSalt;
    }

    public function hashPassword(UserInterface $user)
    {
        $plainPassword = $user->getPlainPassword();

        if (0 === strlen($plainPassword)) {
            return;
        }

        $encoder = $this->encoderFactory->getEncoder($user);

        if (!$this->genUserSalt || $encoder instanceof BCryptPasswordEncoder) {
            $user->setSalt(null);
        } else {
            $salt = rtrim(str_replace('+', '.', base64_encode(random_bytes(32))), '=');
            $user->setSalt($salt);
        }

        $hashedPassword = $encoder->encodePassword($plainPassword, $user->getSalt());
        $user->setPassword($hashedPassword);
        $user->eraseCredentials();
    }
}
