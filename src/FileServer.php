<?php

namespace GisoStallenberg\FileServing;

use DateTime;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FileServer
{
    /**
     * The extensions that should be served, empty array means all
     *
     * @var array
     */
    private $allowedExtensions = array();
    /**
     * The path to serve from.
     *
     * @var string
     */
    private $from;

    /**
     * The server path to serve.
     *
     * @var string
     */
    private $to;

    /**
     * The request object.
     *
     * @var Request
     */
    private $request;

    /**
     * Constructor.
     *
     * @param string  $from
     * @param string  $to
     * @param Request $request
     */
    public function __construct($from, $to, Request $request = null)
    {
        $this->from = $from;
        $this->to = $to;

        if (is_null($request)) {
            $request = Request::createFromGlobals();
        }
        $this->request = $request;
    }

    /**
     * Creates a new instance.
     *
     * @param string  $from
     * @param string  $to
     * @param Request $request
     *
     * @return \static
     */
    public static function create($from, $to, Request $request = null)
    {
        return new static($from, $to, $request);
    }

    /**
     * Sets the allowed extensions
     *
     * @param array $allowedExtensions
     * @return FileServer
     */
    public function filterExtensions(array $allowedExtensions)
    {
        $this->allowedExtensions = $allowedExtensions;

        return $this;
    }

    /**
     * Creates the response object.
     *
     * @return Response
     */
    public function getResponse()
    {
        $requestPath = parse_url($this->request->server->get('REQUEST_URI'), PHP_URL_PATH);

        $filePath = $this->from.substr($requestPath, strlen($this->to));

        if (!$this->isAllowedExtension(pathinfo($filePath, PATHINFO_EXTENSION))) {
            return $this->getNotFoundResponse();
        }

        if (is_file($filePath)) {

            $file = new File($filePath);
            $response = Response::create()
                ->setExpires(new DateTime('+1 week'))
                ->setLastModified(DateTime::createFromFormat('U', $file->getMTime()));;

            if ($response->isNotModified($this->request)) {

                return $response;
            }

            $this->setContentType($file, $response);
            return $response->setContent(file_get_contents($file->getPathname()));
        }

        return $this->getNotFoundResponse();
    }

    /**
     * Test to see if the extension is allowed
     *
     * @param string $extension
     * @return boolean
     */
    private function isAllowedExtension($extension)
    {
        if (empty($this->allowedExtensions)) {
            return true;
        }
        return in_array($extension, $this->allowedExtensions);
    }

    /**
     * Gives the NOT FOUND Response object
     *
     * @return Response
     */
    private function getNotFoundResponse()
    {
        return Response::create('', Response::HTTP_NOT_FOUND);
    }

    /**
     * Sets the correct content-type
     *
     * @param File $file
     * @param Response $response
     */
    private function setContentType(File $file, Response $response)
    {
        $extension = pathinfo($file->getPathname(), PATHINFO_EXTENSION);

        switch ($extension) {
            case 'css':
                $contentType = 'text/css';
                break;
            case 'js':
                $contentType = 'application/javascript';
                break;
            case 'png':
                $contentType = 'image/png';
                break;
            case 'jpg':
            case 'jpeg':
                $contentType = 'image/jpeg';
                break;
            case 'gif':
                $contentType = 'image/gif';
                break;
            case 'txt':
                $contentType = 'text/plain';
                break;
            default:
                $contentType = $file->getMimeType();
                break;
        }

        $response->headers->set('Content-Type', $contentType);
    }

    /**
     * Serves the file.
     */
    public function serve()
    {
        $this->getResponse()->send();
    }
}
