<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sylius\Behat\Context\Api\Shop;

use ApiPlatform\Core\Api\IriConverterInterface;
use Behat\Behat\Context\Context;
use Sylius\Behat\Client\ApiClientInterface;
use Sylius\Behat\Client\ResponseCheckerInterface;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Repository\AddressRepositoryInterface;
use Webmozart\Assert\Assert;

final class AddressBookContext implements Context
{
    /** @var ApiClientInterface */
    private $client;

    /** @var ResponseCheckerInterface */
    private $responseChecker;

    /** @var AddressRepositoryInterface */
    private $addressRepository;

    /** @var IriConverterInterface */
    private $iriConverter;

    public function __construct(
        ApiClientInterface $client,
        ResponseCheckerInterface $responseChecker,
        AddressRepositoryInterface $addressRepository,
        IriConverterInterface $iriConverter
    ) {
        $this->client = $client;
        $this->responseChecker = $responseChecker;
        $this->addressRepository = $addressRepository;
        $this->iriConverter = $iriConverter;
    }

    /**
     * @Given I am editing the address of :fullName
     */
    public function iAmEditingTheAddressOf($fullName)
    {
        $address = $this->getAddressOf($fullName);

        $this->client->buildUpdateRequest((string) $address->getId());
    }

    /**
     * @When I want to add a new address to my address book
     */
    public function iWantToAddANewAddressToMyAddressBook()
    {
        $this->client->buildCreateRequest();
    }

    /**
     * @When /^I specify the (address as "([^"]+)", "([^"]+)", "([^"]+)", "([^"]+)", "([^"]+)", "([^"]+)")$/
     */
    public function iSpecifyTheAddressAs(AddressInterface $address)
    {
        $this->client->setContent(
            [
                'countryCode' => $address->getCountryCode(),
                'street' => $address->getStreet(),
                'city' => $address->getCity(),
                'postcode' => $address->getPostcode(),
                'provinceName' => $address->getProvinceName(),
            ]
        );
    }

    /**
     * @When I add it
     */
    public function iAddIt()
    {
        $this->client->create();
    }

    /**
     * @When I leave every field empty
     */
    public function iLeaveEveryFieldEmpty()
    {
        $this->client->setContent([]);
    }

    /**
     * @When I choose :countryName as my country
     */
    public function iChooseAsMyCountry($countryName)
    {
        $this->client->addRequestData('country', 'US');
    }

    /**
     * @When I do not specify province
     */
    public function iDoNotSpecifyProvince()
    {
        $this->client->addRequestData('provinceName', null);
        $this->client->addRequestData('provinceCode', null);
    }

    /**
     * @When I remove the street
     */
    public function iRemoveTheStreet()
    {
        $this->client->addRequestData('street', null);
    }

    /**
     * @When I save my changed address
     */
    public function iSaveMyChangedAddress()
    {
        $this->client->update();
    }

    /**
     * @Then I should be notified that the address has been successfully added
     */
    public function iShouldBeNotifiedThatTheAddressHasBeenSuccessfullyAdded()
    {
        Assert::true($this->responseChecker->isCreationSuccessful($this->client->getLastResponse()));
    }

    /**
     * @Then /^(address "[^"]+", "[^"]+", "[^"]+", "[^"]+", "[^"]+"(?:|, "[^"]+")) should(?:| still) be marked as my default address$/
     */
    public function addressShouldBeMarkedAsMyDefaultAddress(AddressInterface $address)
    {
        $response = json_decode($this->client->getLastResponse()->getContent(), true);

        /** @var CustomerInterface $customer */
        $customer = $this->iriConverter->getItemFromIri($response['customer']);

        /** @var AddressInterface $defaultAddress */
        $defaultAddress = $customer->getDefaultAddress();

        Assert::same($address->getCity(), $defaultAddress->getCity());
        Assert::same($address->getStreet(), $defaultAddress->getStreet());
        Assert::same($address->getCountryCode(), $defaultAddress->getCountryCode());
        Assert::same($address->getPostcode(), $defaultAddress->getPostcode());
        Assert::same($address->getProvinceCode(), $defaultAddress->getProvinceCode());
        Assert::same($address->getProvinceName(), $defaultAddress->getProvinceName());
    }

    /**
     * @Then I should still be on the address addition page
     */
    public function iShouldStillBeOnTheAddressAdditionPage()
    {
        // Intentionally left empty
    }

    /**
     * @Then /^I should be notified about errors$/
     */
    public function iShouldBeNotifiedAboutErrors()
    {
        $response = json_decode($this->client->getLastResponse()->getContent(), true);

        Assert::true(sizeof($response['violations']) > 0);
    }

    /**
     * @Then I should be notified that the province needs to be specified
     */
    public function iShouldBeNotifiedThatTheProvinceNeedsToBeSpecified()
    {
        $response = json_decode($this->client->getLastResponse()->getContent(), true);

        Assert::inArray(['propertyPath' => 'provinceName', 'message' => 'This value should not be null.'], $response['violations']);
    }

    /**
     * @Then I should still be on the :fullName address edit page
     */
    public function iShouldStillBeOnTheAddressEditPage($fullName)
    {
        // Intentionally left empty
    }

    /**
     * @Then I should still have :arg1 as my specified province
     */
    public function iShouldStillHaveAsMySpecifiedProvince($arg1)
    {
        Assert::false($this->responseChecker->isUpdateSuccessful($this->client->getLastResponse()));
    }

    /**
     * @Then I should still have :value as my chosen province
     */
    public function iShouldStillHaveAsMyChosenProvince($value)
    {
        // Intentionally left empty
    }

    /**
     * @param string $fullName
     *
     * @return AddressInterface
     */
    private function getAddressOf($fullName)
    {
        [$firstName, $lastName] = explode(' ', $fullName);

        /** @var AddressInterface $address */
        $address = $this->addressRepository->findOneBy(['firstName' => $firstName, 'lastName' => $lastName]);
        Assert::notNull($address);

        return $address;
    }
}
