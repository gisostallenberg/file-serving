<?php

use GisoStallenberg\FileServing\FileServer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ServerBag;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for the file server
 *
 * @author Giso Stallenberg
 */
class FileServerTest extends TestCase
{
    /**
     * Test to see that files are found as expected.
     *
     * @param string  $from
     * @param string  $to
     * @param string  $filename
     * @param string  $url
     * @param int     $expectedStatusCode
     * @param string  $expectedContent
     *
     * @dataProvider provideFiles
     */
    public function testFilesFound($from, $to, $filename, $url, $expectedStatusCode, $expectedContent)
    {
        $request = $this->createMock(Request::class);
        $request->server = $this->createMock(ServerBag::class);
        $request
            ->server
            ->expects($this->any())
            ->method('get')
            ->with('REQUEST_URI')
            ->willReturn($url)
        ;

        $response = FileServer::create($from, $to, $request)
            ->getResponse();

        $this->assertEquals($expectedStatusCode, $response->getStatusCode(), 'Asserting that a file is served with the right status code.');
        $this->assertEquals($expectedContent, $response->getContent(), 'Asserting that the correct file data is served.');
    }

    /**
     * Provide file test data.
     *
     * @return array
     */
    public function provideFiles()
    {
        return [
            [
                'non-existing-path',
                'non-existing-path',
                'Foobar.txt',
                'http://www.example.com/foobar/testfiles/Foobar.txt',
                Response::HTTP_NOT_FOUND,
                '',
            ],
            [
                __DIR__.'/Resources',
                '/foobar',
                'Foobar.txt',
                'http://www.example.com/foobar/testfiles/Foobar.txt',
                Response::HTTP_OK,
                'Foo bar baz garply'.PHP_EOL,
            ],
            [
                __DIR__.'/Resources',
                '/foobar',
                'Foo bar.txt',
                'http://www.example.com/foobar/testfiles/Foo%20bar.txt',
                Response::HTTP_OK,
                'Foo bar baz grault'.PHP_EOL,
            ],
            [
                __DIR__.'/Resources',
                '/foobar',
                'Foo bar.txt',
                'http://www.example.com/foobar/testfiles/Foo+bar.txt',
                Response::HTTP_OK,
                'Foo bar baz grault'.PHP_EOL,
            ],
        ];
    }
}
