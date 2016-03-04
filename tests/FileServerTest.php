<?php

use GisoStallenberg\FileServing\FileServer;
use Symfony\Component\HttpFoundation\Response;
/**
 * Unit test for the file server
 *
 * @author Giso Stallenberg
 */
class FileServerTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test to see if non-existing files are not found
     */
    public function testNonExistingFileIsNotFound()
    {
        $response = FileServer::create('non-existing-path', 'non-existing-path')
            ->getResponse();

        $this->assertEquals($response->getStatusCode(), Response::HTTP_NOT_FOUND);
    }
}