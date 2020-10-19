<?php

declare(strict_types=1);

namespace Sylius\Bundle\ApiBundle\Serializer;

use ApiPlatform\Core\Api\IriConverterInterface;
use Sylius\Bundle\ApiBundle\Provider\CustomerProviderInterface;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\User\Model\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class AddressNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    /** @var NormalizerInterface  */
    private $decorated;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var CustomerProviderInterface */
    private $customerProvider;

    /** @var IriConverterInterface */
    private $iriConverter;

    public function __construct(
        NormalizerInterface $decorated,
        TokenStorageInterface $tokenStorage,
        CustomerProviderInterface $customerProvider,
        IriConverterInterface $iriConverter
    ) {
        if (!$decorated instanceof DenormalizerInterface) {
            throw new \InvalidArgumentException(sprintf('The decorated normalizer must implement the %s.', DenormalizerInterface::class));
        }

        $this->tokenStorage = $tokenStorage;
        $this->customerProvider = $customerProvider;
        $this->decorated = $decorated;
        $this->iriConverter = $iriConverter;
    }

    public function supportsNormalization($data, $format = null)
    {
        return $this->decorated->supportsNormalization($data, $format);
    }

    public function normalize($object, $format = null, array $context = [])
    {
        /** @var AddressInterface $address */
        $address = $this->decorated->normalize($object, $format, $context);

        $token = $this->tokenStorage->getToken();
        if ($token === null) {
            throw new TokenNotFoundException();
        }

        /** @var UserInterface $loggedUser */
        $loggedUser = $token->getUser();

        if ($loggedUser instanceof UserInterface) {
            $customer = $this->customerProvider->provide($loggedUser->getEmail());

            $address->setFirstName($customer->getFirstName());
            $address->setLastName($customer->getLastName());
            $address->setCustomer($customer);
        }

        return $address;
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return $this->decorated->supportsDenormalization($data, $type, $format);
    }

    public function denormalize($data, $class, $format = null, array $context = [])
    {
        return $this->decorated->denormalize($data, $class, $format, $context);
    }

    public function setSerializer(SerializerInterface $serializer)
    {
        if($this->decorated instanceof SerializerAwareInterface) {
            $this->decorated->setSerializer($serializer);
        }
    }
}
