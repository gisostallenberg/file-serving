<?php

use GisoStallenberg\FileServing\FileServer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit\Framework\Attributes\DataProvider;
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
     */
    #[DataProvider('provideFiles')]
    public function testFilesFound($from, $to, $filename, $url, $expectedStatusCode, $expectedContent)
    {
        $response = FileServer::create($from, $to, $this->createRequestForUrl($url))
            ->getResponse();

        $this->assertEquals($expectedStatusCode, $response->getStatusCode(), 'Asserting that a file is served with the right status code.');
        $this->assertEquals($expectedContent, $response->getContent(), 'Asserting that the correct file data is served.');
    }

    /**
     * Test that the extension filter only serves allowed extensions.
     */
    public function testExtensionFilterServesAllowedExtension()
    {
        $request = $this->createRequestForUrl('http://www.example.com/foobar/testfiles/Foobar.txt');

        $response = FileServer::create(__DIR__.'/Resources', '/foobar', $request)
            ->filterExtensions(['txt'])
            ->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode(), 'Asserting that an allowed extension is served.');
        $this->assertEquals('Foo bar baz garply'.PHP_EOL, $response->getContent());
    }

    /**
     * Test that the extension filter blocks extensions not in the allow list.
     */
    public function testExtensionFilterBlocksOtherExtension()
    {
        $request = $this->createRequestForUrl('http://www.example.com/foobar/testfiles/Foobar.txt');

        $response = FileServer::create(__DIR__.'/Resources', '/foobar', $request)
            ->filterExtensions(['css'])
            ->getResponse();

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode(), 'Asserting that a disallowed extension gives a 404.');
    }

    /**
     * Test that a file is found when serving from multiple directories.
     */
    public function testMultipleDirectories()
    {
        $request = $this->createRequestForUrl('http://www.example.com/foobar/testfiles/Foobar.txt');

        $response = FileServer::create([__DIR__.'/non-existing-path', __DIR__.'/Resources'], '/foobar', $request)
            ->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode(), 'Asserting that a file is found in a later directory.');
        $this->assertEquals('Foo bar baz garply'.PHP_EOL, $response->getContent());
    }

    /**
     * Builds a mocked request that returns the given url for REQUEST_URI.
     *
     * @param string $url
     *
     * @return Request
     */
    private function createRequestForUrl($url)
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', $url);

        return $request;
    }

    /**
     * Provide file test data.
     *
     * @return array
     */
    public static function provideFiles()
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
