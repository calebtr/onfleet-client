<?php

namespace OnFleet\Tests;

use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\History;
use GuzzleHttp\Subscriber\Mock;
use OnFleet\Administrator;
use OnFleet\Client;
use OnFleet\Organization;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    /**
     * @var Client
     */
    protected $subject;

    /**
     * @var Mock
     */
    protected $mockedResponses;

    /**
     * @var History
     */
    protected $history;

    public function setUp()
    {
        $this->subject = new Client(null);
        $this->mockedResponses = new Mock();
        $this->history = new History();

        $this->subject->getEmitter()->attach($this->history);
        $this->subject->getEmitter()->attach($this->mockedResponses);
    }

    /**
     * @covers OnFleet\Client::getMyOrganization
     */
    public function testGettingMyOrganization()
    {
        // Arrange
        $this->mockedResponses->addResponse(new Response(200, ['Content-type' => 'application/json'], Stream::factory('
        {
            "id": "yAM*fDkztrT3gUcz9mNDgNOL",
            "timeCreated": 1454634415000,
            "timeLastModified": 1455048510514,
            "name": "Onfleet Fine Eateries",
            "email": "fe@onfleet.com",
            "image": "5cc28fc91d7bc5846c6ce9c1",
            "timezone": "America/Los_Angeles",
            "country": "US",
            "delegatees": [
                "cBrUjKvQQgdRp~s1qvQNLpK*"
            ]
        }')));

        // Act
        $organization = $this->subject->getMyOrganization();

        // Assert
        $this->assertInstanceOf(Organization::class, $organization);
        $this->assertEquals('yAM*fDkztrT3gUcz9mNDgNOL', $organization->getId());
        $this->assertEquals(\DateTime::createFromFormat('U', 1454634415), $organization->getTimeCreated());
        $this->assertEquals(\DateTime::createFromFormat('U', 1455048510), $organization->getTimeLastModified());

        $expectedDelegatees = [
            'cBrUjKvQQgdRp~s1qvQNLpK*'
        ];
        $this->assertEquals($expectedDelegatees, $organization->getDelegatees());
    }

    /**
     * @covers OnFleet\Client::getOrganization
     */
    public function testGettingDelegateeOrganization()
    {
        // Arrange
        $this->mockedResponses->addResponse(new Response(200, ['Content-type' => 'application/json'], Stream::factory('
        {
            "id": "cBrUjKvQQgdRp~s1qvQNLpK*",
            "name": "Onfleet Engineering",
            "email": "dev@onfleet.com",
            "timezone": "America/Los_Angeles",
            "country": "US"
        }')));

        // Act
        $organization = $this->subject->getOrganization('cBrUjKvQQgdRp~s1qvQNLpK*');

        // Assert
        $this->assertInstanceOf(Organization::class, $organization);
        $this->assertEquals('cBrUjKvQQgdRp~s1qvQNLpK*', $organization->getId());
        $this->assertEquals('dev@onfleet.com', $organization->getEmail());
        $this->assertEquals('America/Los_Angeles', $organization->getTimezone());
        $this->assertEquals('US', $organization->getCountry());
    }

    /**
     * @covers OnFleet\Client::createAdministrator
     */
    public function testCreatingAdministrator()
    {
        // Arrange
        $this->mockedResponses->addResponse(new Response(200, ['Content-type' => 'application/json'], Stream::factory('
        {
            "id": "8AxaiKwMd~np7I*YP2NfukBE",
            "timeCreated": 1455156651000,
            "timeLastModified": 1455156651779,
            "organization": "yAM*fDkztrT3gUcz9mNDgNOL",
            "email": "dispatcher@example.com",
            "type": "standard",
            "name": "Admin Dispatcher",
            "isActive": false,
            "metadata": []
        }')));

        $adminData = [
            'name'  => 'Admin Dispatcher',
            'email' => 'dispatcher@example.com',
        ];

        // Act
        $administrator = $this->subject->createAdministrator($adminData);

        // Assert
        $request = $this->history->getLastRequest();
        $this->assertEquals('application/json', $request->getHeader('Content-type'));
        $this->assertEquals('https://onfleet.com/api/v2/admins', $request->getUrl());
        $this->assertEquals('POST', $request->getMethod());

        $this->assertInstanceOf(Administrator::class, $administrator);
        $this->assertEquals('yAM*fDkztrT3gUcz9mNDgNOL', $administrator->getOrganization());
        $this->assertEquals('dispatcher@example.com', $administrator->getEmail());
        $this->assertEquals('Admin Dispatcher', $administrator->getName());
        $this->assertEquals('Admin Dispatcher', $administrator->getName());
        $this->assertFalse($administrator->isActive());
    }
}